<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('الدور')
                    ->badge(),
                TextColumn::make('team.name')
                    ->label('الفريق')
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('الدور')
                    ->options(UserRole::class),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
