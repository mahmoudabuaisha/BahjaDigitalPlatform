<?php

namespace App\Filament\Admin\Resources\Teams\RelationManagers;

use App\Filament\Admin\Resources\Events\EventResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $relatedResource = EventResource::class;

    protected static ?string $title = 'فعاليات الفريق';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('الفعالية')->limit(40),
                TextColumn::make('start_date')->label('التاريخ')->date('Y/m/d')->sortable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('actual_children')->label('حضور الأطفال')->placeholder('—'),
            ])
            ->defaultSort('start_date', 'desc')
            ->headerActions([])
            ->recordActions([]);
    }
}
