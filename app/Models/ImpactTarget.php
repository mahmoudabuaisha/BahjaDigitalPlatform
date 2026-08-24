<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** هدف أثر لفترة زمنية — «1000 طفل و3000 مستفيد غير مباشر» */
class ImpactTarget extends Model
{
    protected $fillable = ['label', 'period_start', 'period_end', 'children_target', 'indirect_target'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereDate('period_start', '<=', today())
            ->whereDate('period_end', '>=', today());
    }
}
