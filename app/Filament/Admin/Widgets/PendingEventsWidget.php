<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\Actions\EventActions;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingEventsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('فعاليات بانتظار الاعتماد')
            ->query(
                Event::query()
                    ->with(['team', 'area'])
                    ->where('status', EventStatus::Pending)
                    ->orderBy('start_date')
            )
            ->columns([
                TextColumn::make('title')
                    ->label('الفعالية')
                    ->limit(40)
                    ->url(fn (Event $record): string => EventResource::getUrl('view', ['record' => $record])),
                TextColumn::make('team.name')->label('الفريق'),
                TextColumn::make('area.name')->label('المنطقة'),
                TextColumn::make('start_date')->label('التاريخ')->date('Y/m/d'),
            ])
            ->recordActions([
                EventActions::approve(),
                EventActions::reject(),
            ])
            ->paginated([5])
            ->emptyStateHeading('لا توجد فعاليات معلقة')
            ->emptyStateDescription('كل الفعاليات المرسلة تمت مراجعتها.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
