<?php

namespace App\Filament\Admin\Resources\ShelterCenters\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShelterCenterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('area_id')
                    ->label('المنطقة')
                    ->relationship('area', 'name')
                    ->required(),
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
}
