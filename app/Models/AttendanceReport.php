<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * الحضور الفعلي بعد انتهاء الفعالية — أرقام إجمالية فقط،
 * وسجل واحد لكل فعالية، وأرقام الأثر الرسمية تُبنى عليه وحده.
 */
class AttendanceReport extends Model
{
    protected $fillable = [
        'event_id',
        'children_actual',
        'guardians_actual',
        'notes_private',
        'submitted_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }
}
