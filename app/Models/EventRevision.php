<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نسخة تعديل على فعالية منشورة: تبقى النسخة المنشورة كما هي
 * حتى تعتمد الإدارة هذه النسخة فتُطبَّق حقولها على الفعالية.
 */
class EventRevision extends Model
{
    protected $fillable = [
        'event_id',
        'version',
        'payload',
        'status',
        'submitted_by',
        'reviewed_by',
        'decision_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => RevisionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** الحقول التي يجوز أن تحملها النسخة وتُطبَّق عند الاعتماد */
    public const REVISABLE = [
        'title', 'category_id', 'audience', 'description', 'terms',
        'start_date', 'start_time', 'end_time',
        'area_id', 'shelter_center_id', 'location_details', 'directions',
        'age_min', 'age_max', 'expected_children', 'fee',
        'registration_mode', 'image_path',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RevisionStatus::Pending);
    }

    /** «قبل/بعد» للحقول المتغيّرة فقط — لصفحة المقارنة في اللوحة */
    public function changedFields(): array
    {
        $changes = [];

        foreach ($this->payload as $field => $proposed) {
            $current = $this->event->getAttribute($field);

            if ($field === 'start_date') {
                $current = $current?->toDateString();
            }

            if ((string) $current !== (string) $proposed) {
                $changes[$field] = ['before' => $current, 'after' => $proposed];
            }
        }

        return $changes;
    }
}
