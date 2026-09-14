<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;

/**
 * من يُنبَّه بالبريد عند وصول رسالة تواصل: العنوان المضبوط في الإعدادات،
 * وإلا فبريد المدراء العامّين النشطين. وتعثّر البريد لا يُفشِل إرسال الرسالة.
 */
class ContactMessageNotifier
{
    public static function send(ContactMessage $message): void
    {
        $recipients = self::recipients();

        if ($recipients === []) {
            return;
        }

        rescue(fn () => Mail::to($recipients)->queue(new ContactMessageReceivedMail($message)), report: true);
    }

    /** @return list<string> */
    public static function recipients(): array
    {
        if (! Settings::get('contact_notify_enabled')) {
            return [];
        }

        $configured = trim((string) Settings::get('contact_notify_email'));

        if ($configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_EMAIL) ? [$configured] : [];
        }

        return User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('id')
            ->pluck('email')
            ->all();
    }
}
