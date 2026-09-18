<?php

namespace Tests\Unit;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * سكربت ضبط .env على الاستضافة: يُستبدل الموجود ويُلحَق الغائب، وتُقتبس القيم
 * الخاصة بحيث يقرأها Laravel كما كُتبت — كلمة مرور فيها رموز أو اسم عربي.
 */
class SetEnvScriptTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->file, "APP_NAME=\"بهجة\"\nMAIL_MAILER=log\nMAIL_HOST=127.0.0.1\n# تعليق\nQUEUE_CONNECTION=database\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        parent::tearDown();
    }

    /** @param  array<string, string>  $env */
    private function runScript(array $arguments, array $env = []): Process
    {
        $process = new Process([PHP_BINARY, dirname(__DIR__, 2).'/deploy/set-env.php', '--file='.$this->file, ...$arguments], null, $env);
        $process->run();

        return $process;
    }

    /** @return array<string, string> */
    private function parsed(): array
    {
        return Dotenv::createArrayBacked(dirname($this->file), basename($this->file))->load();
    }

    public function test_it_replaces_existing_keys_and_appends_missing_ones(): void
    {
        $process = $this->runScript(['MAIL_MAILER=smtp', 'MAIL_HOST=smtp.hostinger.com', 'MAIL_PORT=465']);

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $parsed = $this->parsed();
        $this->assertSame('smtp', $parsed['MAIL_MAILER']);
        $this->assertSame('smtp.hostinger.com', $parsed['MAIL_HOST']);
        $this->assertSame('465', $parsed['MAIL_PORT']);
        $this->assertSame('database', $parsed['QUEUE_CONNECTION']);
        $this->assertSame('بهجة', $parsed['APP_NAME']);

        // لا تكرار للمفتاح، والتعليق باقٍ
        $contents = file_get_contents($this->file);
        $this->assertSame(1, substr_count($contents, "\nMAIL_MAILER="));
        $this->assertStringContainsString('# تعليق', $contents);
    }

    public function test_secrets_come_from_the_environment_and_special_characters_survive(): void
    {
        $password = 'p@ss #word $1 "quoted" \\ back';

        $process = $this->runScript(['MAIL_PASSWORD', 'MAIL_FROM_NAME=بَهْجَة', "MAIL_EXTRA=it's ok"], ['MAIL_PASSWORD' => $password]);

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertStringNotContainsString($password, $process->getOutput());

        $parsed = $this->parsed();
        $this->assertSame($password, $parsed['MAIL_PASSWORD']);
        $this->assertSame('بَهْجَة', $parsed['MAIL_FROM_NAME']);
        $this->assertSame("it's ok", $parsed['MAIL_EXTRA']);
    }

    public function test_it_refuses_bad_keys_and_missing_secrets(): void
    {
        $this->assertFalse($this->runScript(['mail_host=x'])->isSuccessful());
        $this->assertFalse($this->runScript(['MAIL_PASSWORD'])->isSuccessful());
        $this->assertSame('log', $this->parsed()['MAIL_MAILER']);
    }
}
