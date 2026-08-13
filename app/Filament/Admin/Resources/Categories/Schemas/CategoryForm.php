<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم التصنيف')
                    ->required()
                    ->maxLength(80),
                TextInput::make('slug')
                    ->label('المعرّف اللاتيني')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                TextInput::make('icon')
                    ->label('الأيقونة (Heroicon)')
                    ->placeholder('heroicon-o-puzzle-piece')
                    ->maxLength(50),
                Select::make('color')
                    ->label('اللون')
                    ->options([
                        'primary' => 'أساسي',
                        'success' => 'أخضر',
                        'warning' => 'برتقالي',
                        'danger' => 'أحمر',
                        'info' => 'أزرق',
                        'gray' => 'رمادي',
                    ]),
                TextInput::make('sort_order')
                    ->label('ترتيب العرض')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
