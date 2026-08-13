<?php

namespace App\Filament\Admin\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المنطقة')
                    ->required()
                    ->maxLength(100),
                TextInput::make('slug')
                    ->label('المعرّف اللاتيني')
                    ->helperText('مثال: north-gaza')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                TextInput::make('sort_order')
                    ->label('ترتيب العرض')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
