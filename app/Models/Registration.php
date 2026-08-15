<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'child_id',
        'children_count',
        'status',
        'note',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'children_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** الحجوزات التي تحجز مقعداً: قيد المراجعة أو مقبولة */
    public function scopeHoldingSeat(Builder $query): Builder
    {
        return $query->whereIn('status', RegistrationStatus::holdingSeat());
    }

    /** الإلغاء متاح حتى 24 ساعة قبل الموعد — بعدها يعتمد الفريق على العدد */
    public function isCancellable(): bool
    {
        if (! in_array($this->status, RegistrationStatus::holdingSeat(), true)) {
            return false;
        }

        return now()->lt($this->event->startsAt()->subDay());
    }

    /** الحالة المعروضة للعائلة — «مكتمل» تُشتقّ ولا تُخزَّن */
    public function displayStatus(): string
    {
        if ($this->status === RegistrationStatus::Accepted && $this->event->hasEnded()) {
            return 'completed';
        }

        return $this->status->value;
    }
}
