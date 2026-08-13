<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Observers\EventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(EventObserver::class)]
class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'category_id',
        'area_id',
        'shelter_center_id',
        'title',
        'description',
        'location_details',
        'start_date',
        'start_time',
        'end_time',
        'status',
        'rejection_reason',
        'expected_children',
        'actual_children',
        'actual_caregivers',
        'image_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'status' => EventStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function shelterCenter(): BelongsTo
    {
        return $this->belongsTo(ShelterCenter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', EventStatus::publiclyVisible());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('start_date', '>=', today());
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** هل انتهى موعد الفعالية؟ (تُستخدم لإظهار التقييم وتسجيل الحضور) */
    public function hasEnded(): bool
    {
        return $this->start_date->isPast() && ! $this->start_date->isToday();
    }

    public function shortUrl(): string
    {
        return route('events.short', $this);
    }
}
