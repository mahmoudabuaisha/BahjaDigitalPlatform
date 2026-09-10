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
                    ->label('المعرّف في الرابط')
                    ->helperText('بالعربية أو اللاتينية — كلمات تفصلها شُرَط بلا مسافات، مثل: العاب أو fun-games')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    // المسافات تتحول شُرَطاً تلقائياً قبل التحقق
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : trim(preg_replace('/[\s-]+/u', '-', trim($state)), '-'))
                    ->regex('/^[\p{Arabic}a-z0-9]+(?:[\s-]+[\p{Arabic}a-z0-9]+)*$/u'),
                TextInput::make('icon')
                    ->label('الأيقونة (Heroicon)')
                    ->placeholder('heroicon-o-puzzle-piece')
                    ->maxLength(50),
                Select::make('scene')
                    ->label('رسمة الفئة')
                    ->helperText('الرسمة المعروضة في بطاقة الفئة والفعاليات بلا صورة — مستقلة عن المعرّف')
                    ->options(\App\Models\Category::SCENES)
                    ->default('default')
                    ->required(),
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
