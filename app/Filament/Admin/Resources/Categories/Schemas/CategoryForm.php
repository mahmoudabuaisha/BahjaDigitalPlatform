<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('image_path')
                    ->label('صورة مخصصة للبطاقة (اختيارية)')
                    ->helperText('عند رفعها تحل محل الرسمة في بطاقة الفئة بالواجهة الرئيسية. تُعالَج تلقائياً: تصغير ومسح بيانات الكاميرا والموقع. يفضَّل مقاس عرضي 8:5')
                    ->image()
                    ->disk('public')
                    ->directory('categories')
                    // الرفع يمر بمعالج الصور: تصغير ومسح EXIF ونسخة بطاقة webp
                    ->saveUploadedFileUsing(fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): string => app(\App\Services\ImageService::class)->store($file, 'categories'))
                    ->maxSize(6144)
                    ->imageEditor()
                    ->imageEditorAspectRatios(['8:5'])
                    ->deletable(),
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
