<?php

namespace App\Console\Commands;

use App\Services\Push\WebPushCrypto;
use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:keys';

    protected $description = 'توليد مفاتيح VAPID لإشعارات الدفع (مرة واحدة)';

    public function handle(): int
    {
        if (! WebPushCrypto::isSupported()) {
            $this->error('هذه النسخة من PHP لا تدعم ما يلزم: openssl بمنحنى prime256v1 وaes-128-gcm.');

            return self::FAILURE;
        }

        if (filled(config('push.public_key'))) {
            $this->warn('توجد مفاتيح مضبوطة بالفعل. توليد مفاتيح جديدة يُبطل كل اشتراكات الأجهزة القائمة.');

            if (! $this->confirm('أأتابع؟', false)) {
                return self::SUCCESS;
            }
        }

        $keys = WebPushCrypto::generateVapidKeys();

        $this->newLine();
        $this->info('أضيفوا هذين السطرين إلى ملف .env على الخادم:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['public']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['private']);
        $this->newLine();
        $this->comment('المفتاح الخاص سرّ: لا يُرفع إلى المستودع ولا يُرسل في رسالة.');

        return self::SUCCESS;
    }
}
