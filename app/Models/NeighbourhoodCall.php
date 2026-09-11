<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نداء حيّ: عائلة لم تجد فعالية قريبة فرفعت يدها.
 *
 * النداء بيانات عائلة، لا إعلان. لذلك لا يُعرض فرداً لأحد خارج الإدارة،
 * وما تراه الفرق عدد مجمَّع لا يقلّ عن العتبة أدناه — كي لا يدلّ رقم
 * على عائلة بعينها في مكان صغير.
 */
class NeighbourhoodCall extends Model
{
    /** أقلّ عدد نداءات يُعرض للفرق — دونه لا يُذكر المكان إطلاقاً */
    public const TEAM_VISIBILITY_FLOOR = 3;

    /** مدّة صلاحية النداء: بعدها يُعدّ قديماً ولا يُحتسب في الطلب */
    public const FRESH_DAYS = 60;

    /** لا يُقبل نداء جديد من العائلة نفسها قبل انقضاء هذه المدّة */
    public const COOLDOWN_DAYS = 14;

    protected $fillable = [
        'user_id', 'area_id', 'shelter_center_id',
        'age_band', 'note', 'children_count',
        'answered_event_id', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'children_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function shelterCenter(): BelongsTo
    {
        return $this->belongsTo(ShelterCenter::class);
    }

    public function answeredEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'answered_event_id');
    }

    /** النداءات التي لم تُلبَّ بعد */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('answered_at');
    }

    /** النداءات الحديثة وحدها تصف الطلب الحالي */
    public function scopeFresh(Builder $query): void
    {
        $query->where('created_at', '>=', now()->subDays(self::FRESH_DAYS));
    }

    /** الطلب القائم: نداء حديث لم يُلبَّ */
    public function scopeStanding(Builder $query): void
    {
        $query->open()->fresh();
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /**
     * صيغة العدد العربية: المفرد ثم المثنّى ثم جمع القلّة ثم التمييز المفرد.
     * الأرقام تُقرأ كما ينطقها الناس، لا كما يخرجها الجدول.
     */
    public static function arabicCount(int $count, string $one, string $two, string $few, string $many): string
    {
        return match (true) {
            $count === 0 => 'لا '.$few,
            $count === 1 => $one,
            $count === 2 => $two,
            $count <= 10 => $count.' '.$few,
            default => $count.' '.$many,
        };
    }

    public static function familiesLabel(int $count): string
    {
        return self::arabicCount($count, 'عائلة واحدة', 'عائلتان', 'عائلات', 'عائلة');
    }

    public static function callsLabel(int $count): string
    {
        return self::arabicCount($count, 'نداء واحد', 'نداءان', 'نداءات', 'نداءً');
    }

    public static function childrenLabel(int $count): string
    {
        return self::arabicCount($count, 'طفل واحد', 'طفلان', 'أطفال', 'طفلاً');
    }
}
