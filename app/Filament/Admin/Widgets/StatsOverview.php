<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي المستخدمين', number_format(User::count()))
                ->description($this->monthlyGrowth(User::query()))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-users')
                ->color('primary')
                ->chart($this->weeklySeries(User::query())),

            Stat::make('إجمالي الفعاليات', number_format(Event::count()))
                ->description($this->monthlyGrowth(Event::query()))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->chart($this->weeklySeries(Event::query())),

            Stat::make('الفعاليات قيد المراجعة', number_format(Event::where('status', EventStatus::Pending)->count()))
                ->description($this->sinceYesterday())
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('الفعاليات المنشورة', number_format(Event::whereIn('status', EventStatus::publiclyVisible())->count()))
                ->description($this->monthlyGrowth(Event::whereIn('status', EventStatus::publiclyVisible())))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }

    /** «‏+12% عن الشهر الماضي» — مقارنة آخر 30 يوماً بالثلاثين التي سبقتها */
    private function monthlyGrowth(Builder $query): string
    {
        $current = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        $previous = (clone $query)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        if ($previous === 0) {
            return $current > 0 ? '+'.number_format($current).' هذا الشهر' : 'لا جديد هذا الشهر';
        }

        $change = (int) round(($current - $previous) / $previous * 100);

        return ($change >= 0 ? '+' : '−').abs($change).'% عن الشهر الماضي';
    }

    private function sinceYesterday(): string
    {
        $added = Event::where('status', EventStatus::Pending)
            ->where('created_at', '>=', today()->subDay())
            ->count();

        return $added > 0 ? '+'.$added.' منذ أمس' : 'لا طلبات جديدة';
    }

    /** سلسلة صغيرة لآخر 7 أيام تُرسم داخل البطاقة */
    private function weeklySeries(Builder $query): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo) => (clone $query)->whereDate('created_at', today()->subDays($daysAgo))->count())
            ->all();
    }
}
