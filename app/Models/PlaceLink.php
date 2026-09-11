<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * صلة مشي بين مكانين: كم دقيقة يستغرق الانتقال بينهما سيراً.
 * تُخزَّن مرة واحدة لكل زوج وتُقرأ في الاتجاهين — فالمسافة لا تتغيّر بالاتجاه.
 */
class PlaceLink extends Model
{
    protected $fillable = ['from_center_id', 'to_center_id', 'walk_minutes', 'note'];

    protected function casts(): array
    {
        return ['walk_minutes' => 'integer'];
    }

    /**
     * الزوج يُخزَّن دائماً بالمعرّف الأصغر أولاً، فيصير الفهرس الفريد
     * حارساً حقيقياً ضد تكرار الصلة نفسها مقلوبة.
     */
    protected static function booted(): void
    {
        static::saving(function (PlaceLink $link): void {
            if ($link->from_center_id !== null
                && $link->to_center_id !== null
                && $link->from_center_id > $link->to_center_id) {
                [$link->from_center_id, $link->to_center_id] = [$link->to_center_id, $link->from_center_id];
            }
        });
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(ShelterCenter::class, 'from_center_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(ShelterCenter::class, 'to_center_id');
    }

    /**
     * صلات مكان بعينه من أي اتجاه.
     *
     * @param  Builder<PlaceLink>  $query
     */
    public function scopeTouching(Builder $query, int $centerId): void
    {
        $query->where(fn (Builder $inner) => $inner
            ->where('from_center_id', $centerId)
            ->orWhere('to_center_id', $centerId));
    }

    /**
     * الأماكن التي يُمشى إليها من هذا المكان خلال المدّة المعطاة — ومعها هو.
     *
     * @return array<int, int>
     */
    public static function withinWalk(?int $centerId, int $maxMinutes): array
    {
        if ($centerId === null) {
            return [];
        }

        $linked = static::query()
            ->touching($centerId)
            ->where('walk_minutes', '<=', $maxMinutes)
            ->get(['from_center_id', 'to_center_id'])
            ->map(fn (self $link): int => $link->from_center_id === $centerId
                ? $link->to_center_id
                : $link->from_center_id)
            ->all();

        return array_values(array_unique([$centerId, ...$linked]));
    }

    /** صيغة العدد العربية: المفرد والمثنّى وجمع القلّة ثم التمييز المفرد */
    public static function minutesLabel(int $minutes): string
    {
        return match (true) {
            $minutes === 1 => 'دقيقة واحدة',
            $minutes === 2 => 'دقيقتان',
            $minutes <= 10 => $minutes.' دقائق',
            default => $minutes.' دقيقة',
        };
    }

    /** دقائق المشي بين مكانين، أو null إن لم تُسجَّل صلة بينهما */
    public static function minutesBetween(?int $a, ?int $b): ?int
    {
        if ($a === null || $b === null) {
            return null;
        }

        if ($a === $b) {
            return 0;
        }

        return static::query()
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $inner) => $inner->where('from_center_id', $a)->where('to_center_id', $b))
                ->orWhere(fn (Builder $inner) => $inner->where('from_center_id', $b)->where('to_center_id', $a)))
            ->value('walk_minutes');
    }
}
