<?php

namespace App\Filament\Admin\Resources\Areas\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShelterCentersRelationManager extends RelationManager
{
    protected static string $relationship = 'shelterCenters';

    protected static ?string $title = 'مراكز الإيواء والمخيمات';

    protected static ?string $modelLabel = 'مركز';

    protected static ?string $pluralModelLabel = 'المراكز';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المركز')
                    ->required()
                    ->maxLength(150),
                Select::make('type')
                    ->label('النوع')
                    ->options([
                        'shelter' => 'مركز إيواء',
                        'camp' => 'مخيم',
                        'school' => 'مدرسة',
                        'clinic' => 'عيادة/مركز صحي',
                        'other' => 'أخرى',
                    ]),
                TextInput::make('address')
                    ->label('العنوان')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('المركز')->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'shelter' => 'مركز إيواء',
                        'camp' => 'مخيم',
                        'school' => 'مدرسة',
                        'clinic' => 'عيادة',
                        default => 'أخرى',
                    }),
                TextColumn::make('events_count')->label('الفعاليات')->counts('events'),
                IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة مركز'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
