<?php

namespace App\Filament\Team\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\ShelterCenter;
use App\Services\ImageService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('rejection_reason')
                    ->label('سبب رفض الإدارة — عدّلوا الفعالية وأعيدوا إرسالها')
                    ->color('danger')
                    ->columnSpanFull()
                    ->visible(fn (?Event $record): bool => $record !== null
                        && $record->status === EventStatus::Rejected
                        && filled($record->rejection_reason)),

                Section::make('بيانات الفعالية')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الفعالية')
                            ->placeholder('مثال: عرض دمى متحركة للأطفال')
                            ->required()
                            ->maxLength(160),
                        Textarea::make('description')
                            ->label('وصف مختصر يظهر للعائلات')
                            ->rows(3),
                        Grid::make(2)->schema([
                            Select::make('category_id')
                                ->label('نوع الفعالية')
                                ->relationship('category', 'name')
                                ->preload()
                                ->required(),
                            TextInput::make('expected_children')
                                ->label('العدد المتوقع للأطفال')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(65000),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('age_min')
                                ->label('العمر من (سنة)')
                                ->helperText('يظهر للعائلات على بطاقة الفعالية')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(18),
                            TextInput::make('age_max')
                                ->label('العمر إلى (سنة)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(18)
                                ->gte('age_min'),
                        ]),
                        FileUpload::make('image_path')
                            ->label('صورة للفعالية (اختياري)')
                            ->image()
                            ->disk('public')
                            ->directory('events')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth(1280)
                            ->maxSize(4096)
                            // إعادة الترميز على الخادم تمسح EXIF/GPS قبل التخزين
                            ->saveUploadedFileUsing(fn ($file): string => app(ImageService::class)->store($file, 'events', 'image_path')),
                    ]),

                Section::make('المكان والزمان')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('area_id')
                                ->label('المحافظة / المنطقة')
                                ->relationship('area', 'name')
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('shelter_center_id', null)),
                            Select::make('shelter_center_id')
                                ->label('مركز الإيواء / المخيم')
                                ->options(
                                    fn (Get $get): array => ShelterCenter::query()
                                        ->where('is_active', true)
                                        ->when($get('area_id'), fn ($q, $areaId) => $q->where('area_id', $areaId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                )
                                ->searchable()
                                ->helperText('اتركوه فارغاً إن كان المكان غير مدرج، واكتبوه في تفاصيل الموقع'),
                        ]),
                        TextInput::make('location_details')
                            ->label('تفاصيل الموقع (يظهر للعائلات)')
                            ->placeholder('مثال: الساحة الغربية قرب البوابة الرئيسية')
                            ->maxLength(255),
                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label('التاريخ')
                                ->minDate(fn (?Event $record) => $record === null ? today() : null)
                                ->required(),
                            TimePicker::make('start_time')
                                ->label('وقت البداية')
                                ->seconds(false)
                                ->required(),
                            TimePicker::make('end_time')
                                ->label('وقت النهاية (اختياري)')
                                ->seconds(false),
                        ]),
                    ]),

                Section::make('تكرار أسبوعي (اختياري)')
                    ->description('لإنشاء نفس الفعالية في نفس اليوم والوقت لعدة أسابيع قادمة')
                    ->columnSpanFull()
                    ->visible(fn (?Event $record): bool => $record === null)
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('repeat_weekly')
                                ->label('تكرار الفعالية أسبوعياً')
                                ->live()
                                ->dehydrated(false),
                            Select::make('repeat_weeks')
                                ->label('عدد الأسابيع (شاملاً هذا الأسبوع)')
                                ->options(array_combine(range(2, 8), range(2, 8)))
                                ->default(2)
                                ->visible(fn (Get $get): bool => (bool) $get('repeat_weekly'))
                                ->dehydrated(false),
                        ]),
                    ]),
            ]);
    }
}
