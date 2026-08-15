<?php

namespace App\Filament\Admin\Resources\Registrations\Tables;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['event.team', 'user', 'child']))
            ->columns([
                TextColumn::make('child.name')
                    ->label('الطفل')
                    ->placeholder('—')
                    ->description(fn (Registration $record): ?string => $record->user?->name)
                    ->searchable(),
                TextColumn::make('event.title')
                    ->label('الفعالية')
                    ->limit(34)
                    ->description(fn (Registration $record): ?string => $record->event?->team?->name)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('review_note')
                    ->label('سبب الرفض')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(RegistrationStatus::class),
                SelectFilter::make('event')
                    ->label('الفعالية')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->emptyStateHeading('لا تسجيلات بعد')
            ->emptyStateDescription('ستظهر هنا حجوزات العائلات فور وصولها.');
    }
}
