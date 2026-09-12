<?php

namespace App\Jobs;

use App\Services\Push\WebPushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * إرسال الإشعار في الطابور: خدمات الدفع بطيئة أحياناً، ولا يجوز أن
 * ينتظرها مسؤول فريق ضغط «إرسال للاعتماد».
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public readonly int $userId,
        public readonly array $payload,
    ) {}

    public function handle(WebPushSender $sender): void
    {
        $sender->sendToUser($this->userId, $this->payload);
    }
}
