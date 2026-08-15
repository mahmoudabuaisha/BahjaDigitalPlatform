<?php

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** بطاقات صفحة «إدارة المستخدمين» — تُعرض فوق الجدول */
class UserStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $byRole = User::query()
            ->selectRaw('role, COUNT(*) AS total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $of = fn (UserRole ...$roles): int => collect($roles)
            ->sum(fn (UserRole $role): int => (int) ($byRole[$role->value] ?? 0));

        return [
            Stat::make('إجمالي المستخدمين', number_format(User::count()))
                ->description('كل من له حساب على المنصّة')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('أولياء الأمور', number_format($of(UserRole::Family)))
                ->description('يحجزون مقاعد لأطفالهم')
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('المنظِّمون', number_format($of(UserRole::TeamManager)))
                ->description('مسؤولو الفرق التطوعية')
                ->icon('heroicon-o-briefcase')
                ->color('success'),

            Stat::make('مدراء النظام', number_format($of(UserRole::Admin, UserRole::SuperAdmin)))
                ->description('صلاحيات الإشراف الكاملة')
                ->icon('heroicon-o-shield-check')
                ->color('warning'),
        ];
    }
}
