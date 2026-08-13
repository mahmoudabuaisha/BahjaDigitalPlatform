<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Team;
use App\Support\Settings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $childrenReached = (int) Event::where('status', EventStatus::Completed)->sum('actual_children');
        $targetChildren = (int) Settings::get('target_children');
        $avgRating = Feedback::whereNotNull('rating')->avg('rating');

        return [
            Stat::make('فعاليات قادمة معتمدة', Event::where('status', EventStatus::Approved)
                ->whereDate('start_date', '>=', today())
                ->count())
                ->description('ستظهر للعائلات في الروزنامة')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('بانتظار الاعتماد', Event::where('status', EventStatus::Pending)->count())
                ->description('فعاليات تحتاج مراجعة')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('أطفال استفادوا', number_format($childrenReached))
                ->description($targetChildren > 0
                    ? 'الهدف: '.number_format($targetChildren).' ('.round($childrenReached / $targetChildren * 100).'%)'
                    : 'من الفعاليات المنفَّذة')
                ->icon('heroicon-o-face-smile')
                ->color('info'),

            Stat::make('الفرق المعتمدة', Team::active()->count())
                ->description($avgRating ? 'متوسط التقييم: '.number_format($avgRating, 1).' من 5' : 'صنّاع الفرح')
                ->icon('heroicon-o-user-group')
                ->color('primary'),
        ];
    }
}
