<?php

namespace App\Filament\Team\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeamStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $teamId = auth()->user()->team_id;
        $base = Event::query()->where('team_id', $teamId);

        $childrenReached = (int) (clone $base)->where('status', EventStatus::Completed)->sum('actual_children');
        $needsAttendance = (clone $base)->where('status', EventStatus::Completed)->whereNull('actual_children')->count();

        return [
            Stat::make('فعاليات قادمة معتمدة', (clone $base)
                ->where('status', EventStatus::Approved)
                ->whereDate('start_date', '>=', today())
                ->count())
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('بانتظار اعتماد الإدارة', (clone $base)
                ->where('status', EventStatus::Pending)
                ->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('أطفال أسعدهم فريقكم', number_format($childrenReached))
                ->description('من الفعاليات المنفَّذة الموثقة')
                ->icon('heroicon-o-face-smile')
                ->color('info'),

            Stat::make('بانتظار تسجيل الحضور', $needsAttendance)
                ->description($needsAttendance > 0 ? 'سجّلوا الحضور لتوثيق أثركم' : 'كل شيء موثق')
                ->icon('heroicon-o-clipboard-document-check')
                ->color($needsAttendance > 0 ? 'danger' : 'success'),
        ];
    }
}
