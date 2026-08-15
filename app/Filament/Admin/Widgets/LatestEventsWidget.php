<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestEventsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('أحدث الفعاليات المضافة')
            ->headerActions([
                Action::make('all')
                    ->label('عرض جميع الفعاليات')
                    ->link()
                    ->url(EventResource::getUrl()),
            ])
            ->query(Event::query()->with(['category', 'area'])->latest())
            ->columns([
                TextColumn::make('title')
                    ->label('الفعالية')
                    ->limit(30)
                    ->description(fn (Event $record): ?string => $record->category?->name)
                    ->url(fn (Event $record): string => EventResource::getUrl('view', ['record' => $record])),
                TextColumn::make('start_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->description(fn (Event $record): ?string => $record->area?->name),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->paginated([5])
            ->emptyStateHeading('لا فعاليات بعد')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
