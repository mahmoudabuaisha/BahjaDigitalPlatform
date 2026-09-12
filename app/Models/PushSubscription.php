<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** اشتراك جهاز واحد في إشعارات الدفع */
class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'device',
        'last_sent_at', 'failures',
    ];

    protected $hidden = ['p256dh', 'auth'];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'failures' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashFor(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /** خدمة الدفع تُعرف من أصل العنوان — ونحتاجه في مطالبة VAPID */
    public function origin(): string
    {
        $parts = parse_url($this->endpoint);

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
