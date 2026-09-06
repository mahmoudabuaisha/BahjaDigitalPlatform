<?php

namespace App\Models;

use App\Enums\OrgType;
use App\Enums\TeamApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** طلب انضمام فريق تطوعي — لا تشغيل قبل الاعتماد */
class TeamApplication extends Model
{
    /** الأنشطة المتقنة كما تظهر في نموذج التسجيل — المفتاح آلي والتسمية للعرض */
    public const ACTIVITIES = [
        'coloring' => 'تلوين ورسم',
        'games' => 'ألعاب ومرح',
        'theater' => 'مسرح ودمى',
        'storytelling' => 'حكواتي وقصص',
        'music' => 'أناشيد وموسيقى',
        'sports' => 'رياضة وحركة',
        'psychosocial' => 'دعم نفسي اجتماعي باللعب',
    ];

    protected $fillable = [
        'team_name',
        'org_type',
        'area_id',
        'base_location',
        'contact_name',
        'contact_email',
        'contact_phone',
        'emergency_phone',
        'description',
        'geographic_scope',
        'coverage_areas',
        'activities',
        'volunteers_count',
        'capacity_per_event',
        'pledge_accepted_at',
        'status',
        'decision_reason',
        'reviewed_by',
        'reviewed_at',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamApplicationStatus::class,
            'org_type' => OrgType::class,
            'coverage_areas' => 'array',
            'activities' => 'array',
            'pledge_accepted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** تسميات الأنشطة المختارة للعرض: «تلوين ورسم، مسرح ودمى» */
    public function activityLabels(): array
    {
        return collect($this->activities ?? [])
            ->map(fn (string $key) => self::ACTIVITIES[$key] ?? $key)
            ->all();
    }

    /** أسماء محافظات التغطية المختارة */
    public function coverageAreaNames(): array
    {
        if (! $this->coverage_areas) {
            return [];
        }

        return Area::whereIn('id', $this->coverage_areas)->orderBy('name')->pluck('name')->all();
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
