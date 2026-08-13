<?php

namespace App\Filament\Admin\Pages\ImpactWidgets;

use App\Filament\Admin\Pages\ImpactReport;
use App\Models\Feedback;
use App\Support\Settings;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImpactStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $query = ImpactReport::filteredQuery($this->pageFilters);

        $eventsCount = (clone $query)->count();
        $children = (int) (clone $query)->sum('actual_children');
        $caregivers = (int) (clone $query)->sum('actual_caregivers');
        $centers = (clone $query)->whereNotNull('shelter_center_id')->distinct('shelter_center_id')->count('shelter_center_id');
        $views = (int) (clone $query)->sum('views_count');

        $eventIds = (clone $query)->pluck('id');
        $avgRating = Feedback::whereIn('event_id', $eventIds)->whereNotNull('rating')->avg('rating');

        $targetChildren = (int) Settings::get('target_children');
        $targetIndirect = (int) Settings::get('target_indirect');

        return [
            Stat::make('فعاليات منفَّذة', number_format($eventsCount))
                ->icon('heroicon-o-flag')
                ->color('info'),

            Stat::make('أطفال مستفيدون', number_format($children))
                ->description($targetChildren > 0
                    ? 'الهدف: '.number_format($targetChildren).' — الإنجاز: '.round($children / max($targetChildren, 1) * 100).'%'
                    : null)
                ->icon('heroicon-o-face-smile')
                ->color('success'),

            Stat::make('مرافقون (غير مباشر)', number_format($caregivers))
                ->description($targetIndirect > 0
                    ? 'الهدف: '.number_format($targetIndirect)
                    : null)
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('مراكز إيواء مخدومة', number_format($centers))
                ->icon('heroicon-o-home-modern')
                ->color('warning'),

            Stat::make('مشاهدات صفحات الفعاليات', number_format($views))
                ->icon('heroicon-o-eye')
                ->color('gray'),

            Stat::make('متوسط تقييم العائلات', $avgRating ? number_format($avgRating, 1).' / 5' : '—')
                ->icon('heroicon-o-star')
                ->color('warning'),
        ];
    }
}
