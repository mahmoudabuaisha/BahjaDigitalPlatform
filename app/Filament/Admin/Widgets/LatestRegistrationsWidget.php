<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Registrations\RegistrationResource;
use App\Models\Registration;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestRegistrationsWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('آخر التسجيلات')
            ->headerActions([
                Action::make('all')
                    ->label('عرض جميع التسجيلات')
                    ->link()
                    ->url(RegistrationResource::getUrl()),
            ])
            ->query(Registration::query()->with(['event', 'user', 'child'])->latest())
            ->columns([
                TextColumn::make('user.name')
                    ->label('العائلة')
                    ->description(fn (Registration $record): ?string => $record->child?->name),
                TextColumn::make('event.title')
                    ->label('الفعالية')
                    ->limit(28),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('منذ')
                    ->since(),
            ])
            ->paginated([5])
            ->emptyStateHeading('لا تسجيلات بعد')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
