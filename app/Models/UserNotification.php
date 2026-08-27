<?php

namespace App\Models;

use App\Mail\UserNotificationMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;

class UserNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /** تصنيف الإشعار — تبويبات صفحة الإشعارات تُبنى عليه */
    public const GROUPS = [
        'registrations' => ['registration_submitted', 'registration_accepted', 'registration_rejected', 'registration_received'],
        'events' => ['event_new', 'event_changed', 'event_cancelled', 'event_reminder'],
        'messages' => ['admin_message'],
    ];

    public function group(): string
    {
        foreach (self::GROUPS as $group => $types) {
            if (in_array($this->type, $types, true)) {
                return $group;
            }
        }

        return 'system';
    }

    public function scopeOfGroup(Builder $query, string $group): Builder
    {
        if ($group === 'system') {
            return $query->whereNotIn('type', array_merge(...array_values(self::GROUPS)));
        }

        return $query->whereIn('type', self::GROUPS[$group] ?? []);
    }

    /** إنشاء إشعار — نقطة واحدة كي تبقى الصياغة والروابط متسقة */
    public static function send(User|int $user, string $type, string $title, ?string $body = null, ?string $url = null): self
    {
        $notification = self::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        // الصورة البريدية للإشعار نفسه — وتعثّر البريد لا يُفشِل العملية الأصلية
        rescue(function () use ($user, $notification): void {
            $recipient = $user instanceof User ? $user : User::find($user);

            if ($recipient?->email) {
                Mail::to($recipient->email)->queue(new UserNotificationMail($notification));
            }
        }, report: true);

        return $notification;
    }
}
