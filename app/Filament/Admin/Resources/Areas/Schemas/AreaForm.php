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
                    ->label('المعرّف في الرابط')
                    ->helperText('بالعربية أو اللاتينية — كلمات تفصلها شُرَط بلا مسافات، مثل: شمال-غزة أو north-gaza')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    // المسافات تتحول شُرَطاً تلقائياً قبل التحقق
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : trim(preg_replace('/[\s-]+/u', '-', trim($state)), '-'))
                    ->regex('/^[\p{Arabic}a-z0-9]+(?:[\s-]+[\p{Arabic}a-z0-9]+)*$/u'),
                TextInput::make('sort_order')
                    ->label('ترتيب العرض')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
