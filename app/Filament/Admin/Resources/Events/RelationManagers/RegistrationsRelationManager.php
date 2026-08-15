<?php

namespace App\Filament\Admin\Resources\Events\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** حجوزات العائلات في هذه الفعالية — للتحضير قبل الموعد */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'حجوزات العائلات';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('وليّ الأمر')->searchable(),
                TextColumn::make('user.phone')->label('الجوال')->placeholder('—'),
                TextColumn::make('children_count')->label('عدد الأطفال')->sortable(),
                TextColumn::make('note')->label('ملاحظة')->limit(40)->placeholder('—'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('تاريخ الحجز')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                DeleteAction::make()->label('حذف الحجز'),
            ]);
    }
}
