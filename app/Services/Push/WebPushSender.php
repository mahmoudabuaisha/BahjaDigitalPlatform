<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * إرسال الإشعار إلى أجهزة العائلة.
 *
 * الإشعار ليس بديلاً عن الإشعار داخل الموقع بل صوته: يصل والهاتف في الجيب
 * وبَهْجَة مغلقة. وحين يرفض المتصفّح العنوان (404/410) يُحذف الاشتراك فوراً
 * كي لا نطرق باباً لم يعد موجوداً.
 */
class WebPushSender
{
    /** أقصى عدد إخفاقات قبل إسقاط الاشتراك */
    private const MAX_FAILURES = 3;

    private const TIMEOUT_SECONDS = 10;

    public function isConfigured(): bool
    {
        return WebPushCrypto::isSupported()
            && filled(config('push.public_key'))
            && filled(config('push.private_key'));
    }

    /**
     * إرسال إلى كل أجهزة المستخدم.
     *
     * @param  array<string, mixed>  $payload
     * @return int عدد الأجهزة التي قبلت الإشعار
     */
    public function sendToUser(User|int $user, array $payload): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        $sent = 0;

        foreach (PushSubscription::where('user_id', $userId)->get() as $subscription) {
            $sent += $this->send($subscription, $payload) ? 1 : 0;
        }

        return $sent;
    }

    /** @param  array<string, mixed>  $payload */
    public function send(PushSubscription $subscription, array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $body = WebPushCrypto::encrypt(
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                WebPushCrypto::base64UrlDecode($subscription->p256dh),
                WebPushCrypto::base64UrlDecode($subscription->auth),
            );

            $headers = WebPushCrypto::vapidHeaders(
                $subscription->origin(),
                config('push.subject'),
                config('push.public_key'),
                config('push.private_key'),
            );

            $response = Http::withHeaders($headers + [
                'Content-Type' => 'application/octet-stream',
                'Content-Encoding' => 'aes128gcm',
                'TTL' => (string) config('push.ttl'),
                'Urgency' => 'normal',
            ])
                ->withBody($body, 'application/octet-stream')
                ->timeout(self::TIMEOUT_SECONDS)
                ->post($subscription->endpoint);
        } catch (Throwable $exception) {
            // شبكة الاستضافة قد تتعثّر: نعدّها إخفاقاً ولا نُسقط العملية
            $this->recordFailure($subscription, $exception->getMessage());

            return false;
        }

        // اشتراك منتهٍ: الجهاز حذف التطبيق أو أُبطل العنوان
        if (in_array($response->status(), [404, 410], true)) {
            $subscription->delete();

            return false;
        }

        if ($response->failed()) {
            $this->recordFailure($subscription, 'HTTP '.$response->status());

            return false;
        }

        $subscription->forceFill(['last_sent_at' => now(), 'failures' => 0])->save();

        return true;
    }

    private function recordFailure(PushSubscription $subscription, string $reason): void
    {
        $failures = $subscription->failures + 1;

        Log::warning('تعذّر إرسال إشعار دفع', [
            'subscription' => $subscription->id,
            'failures' => $failures,
            'reason' => $reason,
        ]);

        if ($failures >= self::MAX_FAILURES) {
            $subscription->delete();

            return;
        }

        $subscription->forceFill(['failures' => $failures])->save();
    }
}
