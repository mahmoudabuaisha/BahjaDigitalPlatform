<?php

namespace App\Models;

use App\Enums\Audience;
use App\Enums\EventStatus;
use App\Enums\RegistrationMode;
use App\Enums\RegistrationStatus;
use App\Enums\RevisionStatus;
use App\Observers\EventObserver;
use App\Services\ImageService;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(EventObserver::class)]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    /** درجات القرب من مرساة مكان العائلة — الأصغر أقرب */
    public const PROXIMITY_SAME_PLACE = 0;

    public const PROXIMITY_SAME_AREA = 1;

    public const PROXIMITY_FAR = 2;

    public const PROXIMITY_UNKNOWN = 3;

    protected $fillable = [
        'public_id',
        'team_id',
        'series_id',
        'category_id',
        'area_id',
        'shelter_center_id',
        'audience',
        'title',
        'description',
        'terms',
        'location_details',
        'directions',
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
            'publish_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // معرّف عام غير قابل للتخمين — تعتمد عليه روابط QR الثابتة
        static::creating(function (Event $event): void {
            $event->public_id ??= (string) Str::ulid();
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(EventSeries::class, 'series_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(EventRevision::class);
    }

    /** نسخة تعديل تنتظر المراجعة — وجودها يعني «تعديلات معلّقة» */
    public function pendingRevision(): HasOne
    {
        return $this->hasOne(EventRevision::class)->where('status', RevisionStatus::Pending);
    }

    public function attendanceReport(): HasOne
    {
        return $this->hasOne(AttendanceReport::class);
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

    /** بداية اليوم بالدقائق — لمقارنة تداخل المواعيد */
    private function startMinutes(): int
    {
        return (int) substr($this->start_time, 0, 2) * 60 + (int) substr($this->start_time, 3, 2);
    }

    /** النهاية بالدقائق — وعند غياب وقت نهاية نفترض ساعتين */
    private function endMinutes(): int
    {
        if ($this->end_time) {
            return (int) substr($this->end_time, 0, 2) * 60 + (int) substr($this->end_time, 3, 2);
        }

        return $this->startMinutes() + 120;
    }

    /**
     * تحذير تداخل المواعيد (بند الخطة التحذيري): فعاليات حيّة لنفس الفريق
     * أو نفس مركز الإيواء في نفس اليوم والوقت. فواصل نصف-مفتوحة —
     * فعالية تنتهي حين تبدأ التالية لا تُحسب تعارضاً.
     *
     * @return array<int, string>
     */
    public function conflictWarnings(): array
    {
        $candidates = static::query()
            ->whereDate('start_date', $this->start_date)
            ->whereNotIn('status', [
                EventStatus::Draft,
                EventStatus::Rejected,
                EventStatus::Cancelled,
                EventStatus::Archived,
            ])
            ->when($this->id, fn (Builder $query) => $query->where('id', '!=', $this->id))
            ->where(function (Builder $query): void {
                $query->where('team_id', $this->team_id);

                if ($this->shelter_center_id) {
                    $query->orWhere('shelter_center_id', $this->shelter_center_id);
                }
            })
            ->get();

        $warnings = [];

        foreach ($candidates as $other) {
            if (! ($this->startMinutes() < $other->endMinutes() && $other->startMinutes() < $this->endMinutes())) {
                continue;
            }

            $when = substr($other->start_time, 0, 5);

            $warnings[] = $other->team_id === $this->team_id
                ? 'فريقكم لديه فعالية أخرى في الوقت نفسه: «'.$other->title.'» الساعة '.$when
                : 'المركز نفسه محجوز بفعالية «'.$other->title.'» الساعة '.$when.' لفريق آخر';
        }

        return array_values(array_unique($warnings));
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

    /** معتمدة لكن ظهورها مجدول لاحقاً (القسم 6.1: approved ≠ published) */
    public function isScheduledForLater(): bool
    {
        return (bool) $this->publish_at?->isFuture();
    }

    /** هل يقبل الحجز الآن؟ فعالية ظاهرة، لم يمض موعدها، وفيها متّسع */
    public function acceptsRegistrations(): bool
    {
        return $this->status->isPubliclyVisible()
            && ! $this->isScheduledForLater()
            && ! $this->hasEnded()
            && ! $this->isFull();
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', EventStatus::publiclyVisible())
            ->where(fn (Builder $inner) => $inner
                ->whereNull('publish_at')
                ->orWhere('publish_at', '<=', now()));
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

    /**
     * نسخة WebP الأخف للبطاقات والقوائم؛ الصور القديمة (قبل تفعيل
     * المعالجة) لا تملك نسخة بطاقة فتعود للأصل.
     */
    public function imageCardUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        foreach (['webp', 'jpg'] as $extension) {
            $card = ImageService::cardPath($this->image_path, $extension);

            if (Storage::disk('public')->exists($card)) {
                return Storage::disk('public')->url($card);
            }
        }

        return $this->imageUrl();
    }

    /** هل انتهى موعد الفعالية؟ (تُستخدم لإظهار التقييم وتسجيل الحضور) */
    public function hasEnded(): bool
    {
        return $this->start_date->isPast() && ! $this->start_date->isToday();
    }

    /**
     * اسم المكان كما يجوز نشره (القسم 15.6): مركز مخفيّ الظهور
     * تُعرض محافظته فقط، حمايةً ميدانية.
     */
    /**
     * ترتيب النتائج بالأقرب إلى مرساة مكان العائلة ثم بالأسبق موعداً.
     * بلا مرساة يبقى الترتيب الزمني كما هو.
     *
     * @param  Builder<Event>  $query
     */
    public function scopeNearestTo(Builder $query, ?User $user): void
    {
        if ($user === null || ! $user->hasLocationAnchor()) {
            return;
        }

        if (! $user->shelter_center_id) {
            $query->orderByRaw('case when area_id = ? then 0 else 1 end', [$user->area_id]);

            return;
        }

        // دقائق المشي المسجَّلة تتقدّم على مجرّد «نفس المحافظة»:
        // مكانكم نفسه، ثم الأماكن الموصولة بالأقرب زمناً، ثم المحافظة، ثم ما بَعُد
        $minutes = PlaceLink::query()
            ->selectRaw('case when from_center_id = ? then to_center_id else from_center_id end as linked_id, walk_minutes', [$user->shelter_center_id])
            ->touching($user->shelter_center_id);

        $query
            ->leftJoinSub($minutes, 'near', 'near.linked_id', '=', 'events.shelter_center_id')
            ->select('events.*')
            ->orderByRaw('case
                    when events.shelter_center_id = ? then 0
                    when near.walk_minutes is not null then 1
                    when events.area_id = ? then 2
                    else 3 end', [$user->shelter_center_id, $user->area_id])
            ->orderByRaw('coalesce(near.walk_minutes, 9999)');
    }

    /**
     * درجة قرب الفعالية من مرساة مكان العائلة — كلما صغرت كانت أقرب.
     * القرب هنا جيرة لا مسافة: لا إحداثيات ولا GPS، بل «نفس المكان الذي
     * تعرفه العائلة» ثم «نفس المحافظة» ثم ما بَعُد.
     */
    public function proximityRank(?User $user): int
    {
        if ($user === null || ! $user->hasLocationAnchor()) {
            return self::PROXIMITY_UNKNOWN;
        }

        if ($user->shelter_center_id && $this->shelter_center_id === $user->shelter_center_id) {
            return self::PROXIMITY_SAME_PLACE;
        }

        return $this->area_id === $user->area_id
            ? self::PROXIMITY_SAME_AREA
            : self::PROXIMITY_FAR;
    }

    /**
     * عبارة القرب كما تُقرأ على البطاقة. العائلات تقيس بالدقائق مشياً لا
     * بالكيلومترات، فإن سُجّلت صلة مشي بين المكانين قُدِّمت على الوصف العام.
     */
    public function proximityLabel(?User $user): ?string
    {
        if ($user === null || ! $user->hasLocationAnchor()) {
            return null;
        }

        if ($this->proximityRank($user) === self::PROXIMITY_SAME_PLACE) {
            return 'في مكانكم نفسه';
        }

        $minutes = PlaceLink::minutesBetween($user->shelter_center_id, $this->shelter_center_id);

        if ($minutes !== null && $minutes > 0) {
            return 'على بُعد '.PlaceLink::minutesLabel($minutes).' مشياً';
        }

        return $this->proximityRank($user) === self::PROXIMITY_SAME_AREA ? 'في محافظتكم' : null;
    }

    public function publicPlaceName(): string
    {
        $visibility = $this->shelterCenter?->visibility;

        if ($this->shelterCenter && ($visibility?->showsName() ?? true)) {
            return $this->shelterCenter->name;
        }

        return $this->area?->name ?? '';
    }

    /** العنوان التفصيلي — يُحجب إن كان ظهور المركز مقيّداً */
    public function publicLocationDetails(): ?string
    {
        $visibility = $this->shelterCenter?->visibility;

        if ($visibility && ! $visibility->showsAddress()) {
            return null;
        }

        return $this->location_details;
    }

    /** الرابط الثابت للمشاركة وQR — لا يتغيّر مهما عُدّل العنوان */
    public function shortUrl(): string
    {
        return $this->public_id
            ? route('events.stable', $this->public_id)
            : route('events.short', $this);
    }
}
