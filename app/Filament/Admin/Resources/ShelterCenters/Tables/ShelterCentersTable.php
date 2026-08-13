<?php

namespace App\Filament\Admin\Resources\ShelterCenters\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShelterCentersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('المركز')
                    ->searchable(),
                TextColumn::make('area.name')
                    ->label('المنطقة'),
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'shelter' => 'مركز إيواء',
                        'camp' => 'مخيم',
                        'school' => 'مدرسة',
                        'clinic' => 'عيادة',
                        default => 'أخرى',
                    }),
                TextColumn::make('events_count')
                    ->label('الفعاليات')
                    ->counts('events'),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('المنطقة')
                    ->relationship('area', 'name'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
