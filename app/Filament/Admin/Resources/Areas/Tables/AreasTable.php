<?php

namespace App\Filament\Admin\Resources\Areas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('المنطقة')
                    ->searchable(),
                TextColumn::make('shelter_centers_count')
                    ->label('عدد المراكز')
                    ->counts('shelterCenters'),
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
            ]);
    }
}
