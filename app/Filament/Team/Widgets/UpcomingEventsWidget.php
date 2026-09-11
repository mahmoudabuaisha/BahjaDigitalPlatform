<?php

namespace App\Filament\Team\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingEventsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('فعالياتكم القادمة')
            ->query(
                Event::query()
                    ->with(['area', 'shelterCenter'])
                    ->where('team_id', auth()->user()->team_id)
                    ->whereIn('status', [EventStatus::Approved, EventStatus::Pending])
                    ->whereDate('start_date', '>=', today())
                    ->orderBy('start_date')
                    ->orderBy('start_time')
            )
            ->columns([
                TextColumn::make('title')
                    ->label('الفعالية')
                    ->limit(40),
                TextColumn::make('area.name')
                    ->label('المنطقة')
                    ->description(fn (Event $record): ?string => $record->shelterCenter?->name),
                TextColumn::make('start_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->description(fn (Event $record): string => substr($record->start_time, 0, 5)),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->paginated([5])
            ->emptyStateHeading('لا فعاليات قادمة')
            ->emptyStateDescription('أنشئوا فعالية جديدة من قسم "فعالياتنا".')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
