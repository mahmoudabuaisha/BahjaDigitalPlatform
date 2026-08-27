<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** فحص إعدادات SMTP بعد ضبطها: php artisan mail:test you@example.com */
class SendTestMail extends Command
{
    protected $signature = 'mail:test {to : البريد المستلم}';

    protected $description = 'إرسال رسالة تجريبية فورية للتأكد من إعدادات البريد';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        Mail::raw(
            'هذه رسالة تجريبية من منصّة بَهْجَة — إن وصلتكم فإعدادات البريد تعمل بنجاح.',
            fn ($message) => $message->to($to)->subject('اختبار بريد بَهْجَة ✓'),
        );

        $this->info("أُرسلت الرسالة التجريبية إلى {$to} عبر mailer: ".config('mail.default'));

        return self::SUCCESS;
    }
}
