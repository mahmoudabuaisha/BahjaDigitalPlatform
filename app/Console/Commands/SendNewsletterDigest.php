<?php

namespace App\Console\Commands;

use App\Services\NewsletterDigest;
use Illuminate\Console\Command;

/**
 * نشرة الأسبوع البريدية — تُجدوَل كل سبت صباحاً، وتُرسَل يدوياً من اللوحة.
 */
class SendNewsletterDigest extends Command
{
    protected $signature = 'newsletter:send {--force : تجاهل الحماية من التكرار في اليوم نفسه}';

    protected $description = 'نشرة الأسبوع: فعاليات الأيام السبعة القادمة لكل مشترك مؤكَّد';

    public function handle(NewsletterDigest $digest): int
    {
        $result = $digest->send((bool) $this->option('force'));

        $this->info("أُرسلت النشرة إلى {$result['sent']} مشتركاً، وتُخطّي {$result['skipped']}.");

        return self::SUCCESS;
    }
}
