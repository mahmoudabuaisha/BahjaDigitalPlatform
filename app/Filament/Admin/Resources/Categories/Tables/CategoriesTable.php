<?php

namespace App\Filament\Admin\Resources\Categories\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('التصنيف')
                    ->badge()
                    ->color(fn ($record): string => $record->color ?? 'gray')
                    ->icon(fn ($record): ?string => $record->icon),
                TextColumn::make('events_count')
                    ->label('عدد الفعاليات')
                    ->counts('events'),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
