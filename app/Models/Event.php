<?php

namespace App\Models;

use App\Enums\Audience;
use App\Enums\EventStatus;
use App\Enums\RegistrationMode;
use App\Enums\RegistrationStatus;
use App\Observers\EventObserver;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(EventObserver::class)]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'category_id',
        'area_id',
        'shelter_center_id',
        'audience',
        'title',
        'description',
        'terms',
        'location_details',
        'start_date',
        'start_time',
        'end_time',
        'status',
        'rejection_reason',
        'expected_children',
        'fee',
        'registration_mode',
        'age_min',
        'age_max',
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
            'registration_mode' => RegistrationMode::class,
            'audience' => Audience::class,
            'fee' => 'decimal:2',
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

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /** بداية الفعالية كلحظة كاملة — تُستعمل في مهلة الإلغاء */
    public function startsAt(): Carbon
    {
        return $this->start_date->copy()->setTimeFromTimeString($this->start_time);
    }

    /** المقاعد المشغولة — الحجوزات قيد المراجعة والمقبولة */
    public function seatsTaken(): int
    {
        return (int) $this->registrations()
            ->whereIn('status', RegistrationStatus::holdingSeat())
            ->sum('children_count');
    }

    /** المقاعد المتبقية، أو null حين لم يحدّد الفريق عدداً متوقعاً (بلا حدّ) */
    public function seatsRemaining(): ?int
    {
        if (! $this->expected_children) {
            return null;
        }

        return max(0, $this->expected_children - $this->seatsTaken());
    }

    public function isFull(): bool
    {
        return $this->seatsRemaining() === 0;
    }

    /** هل يقبل الحجز الآن؟ فعالية ظاهرة، لم يمض موعدها، وفيها متّسع */
    public function acceptsRegistrations(): bool
    {
        return $this->status->isPubliclyVisible()
            && ! $this->hasEnded()
            && ! $this->isFull();
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', EventStatus::publiclyVisible());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('start_date', '>=', today());
    }

    /** «من 4 إلى 10 سنوات» — أو null حين لم يحدّد الفريق الفئة العمرية */
    public function ageLabel(): ?string
    {
        if ($this->age_min && $this->age_max) {
            return 'من '.$this->age_min.' إلى '.$this->age_max.' سنوات';
        }

        if ($this->age_min) {
            return $this->age_min.' سنوات فأكثر';
        }

        if ($this->age_max) {
            return 'حتى '.$this->age_max.' سنوات';
        }

        return null;
    }

    /** «مجاناً» أو «10 شيكل» — الرسوم اختيارية، والأصل أن تكون الفعالية مجانية */
    public function feeLabel(): string
    {
        return $this->fee > 0
            ? rtrim(rtrim(number_format((float) $this->fee, 2), '0'), '.').' شيكل'
            : 'مجاناً';
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
