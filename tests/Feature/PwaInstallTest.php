<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * التثبيت على الجهاز: المانيفست كامل، والـ Service Worker يُقدَّم بإصدار،
 * وصفحة الدعوة تشرح لكل منصّة طريقتها.
 */
class PwaInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_describes_an_installable_app(): void
    {
        $response = $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $manifest = json_decode($response->getContent(), true);

        $this->assertIsArray($manifest, 'المانيفست يجب أن يكون JSON صالحاً');
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/?src=pwa', $manifest['start_url']);
        $this->assertSame('rtl', $manifest['dir']);
        $this->assertSame('ar', $manifest['lang']);

        // أندرويد يشترط أيقونة 192 وأخرى maskable كي يعرض نافذة التثبيت
        $sizes = array_column($manifest['icons'], 'sizes');
        $purposes = array_column($manifest['icons'], 'purpose');

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', $purposes);

        $this->assertCount(3, $manifest['shortcuts']);
        $this->assertNotEmpty($manifest['screenshots']);
    }

    public function test_every_icon_the_manifest_promises_actually_exists(): void
    {
        $manifest = json_decode($this->get('/manifest.webmanifest')->getContent(), true);

        $promised = array_column($manifest['icons'], 'src');

        foreach ($manifest['shortcuts'] as $shortcut) {
            $promised = array_merge($promised, array_column($shortcut['icons'], 'src'));
        }

        // اللقطات تُعرض في نافذة التثبيت بأندرويد: غيابها يُفقد النافذة غناها
        $promised = array_merge($promised, array_column($manifest['screenshots'], 'src'));

        foreach ($promised as $src) {
            $this->assertFileExists(public_path(ltrim($src, '/')), "الأيقونة {$src} مفقودة");
        }
    }

    public function test_ios_gets_an_opaque_icon_and_its_own_meta(): void
    {
        // سفاري يرسم الشفافية سوداء: أيقونة iOS لا بدّ أن تكون معتمة
        $icon = imagecreatefrompng(public_path('icons/apple-touch-icon.png'));

        $this->assertNotFalse($icon);
        $this->assertSame(0, imagecolorsforindex($icon, imagecolorat($icon, 1, 1))['alpha']);

        $this->get('/')
            ->assertOk()
            ->assertSee('apple-touch-icon.png', false)
            ->assertSee('apple-mobile-web-app-capable', false);
    }

    public function test_the_search_engine_finds_a_real_favicon(): void
    {
        // كان هذا الملف صفر بايت، فعرض جوجل أيقونة عامّة بدل شعار المنصّة
        $ico = public_path('favicon.ico');

        $this->assertFileExists($ico);
        $this->assertGreaterThan(500, filesize($ico), 'favicon.ico فارغ أو ناقص');

        // ترويسة ICO: محجوز 0، النوع 1، ثم عدد المقاسات
        $header = unpack('vreserved/vtype/vcount', (string) file_get_contents($ico, length: 6));

        $this->assertSame(0, $header['reserved']);
        $this->assertSame(1, $header['type']);
        $this->assertGreaterThanOrEqual(3, $header['count']);

        foreach (['48', '96', '192'] as $size) {
            $this->assertFileExists(public_path("icons/favicon-{$size}.png"));
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="icon" href="/favicon.ico"', false)
            ->assertSee('sizes="48x48"', false);
    }

    public function test_the_service_worker_is_served_uncached_and_versioned(): void
    {
        $response = $this->get('/sw.js')->assertOk();

        // متصفّح يخزّن الـ SW نفسه لا يرى تحديثاً أبداً
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $body = $response->getContent();

        $this->assertStringContainsString("const VERSION = '", $body);
        $this->assertStringContainsString('skip-waiting', $body, 'التحديث يجب أن ينتظر إذن المستخدم');
        $this->assertStringNotContainsString('.then(() => self.skipWaiting())', $body);
    }

    public function test_the_install_page_explains_every_platform(): void
    {
        $this->get(route('install'))
            ->assertOk()
            ->assertSee('بَهْجَة في جيبكم')
            ->assertSee('إضافة إلى الشاشة الرئيسية')   // خطوات iOS
            ->assertSee('ثبّتوا التطبيق الآن')          // زر أندرويد
            ->assertSee('الضغط المطوّل على أيقونة بَهْجَة');
    }

    public function test_the_footer_points_families_to_the_app_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('install'), false)
            ->assertSee('تطبيق بَهْجَة على هاتفكم');
    }

    public function test_private_pages_are_never_cached_by_the_service_worker(): void
    {
        // هاتف واحد تتشاركه عائلة: صفحة حساب محفوظة قد تُعرض لغير صاحبها
        $body = $this->get('/sw.js')->getContent();

        $this->assertStringContainsString("'/account'", $body);
        $this->assertStringContainsString('PRIVATE_PATHS', $body);
        $this->assertStringContainsString('isPrivate(', $body);
    }
}
