<?php

namespace App\Filament\Admin\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Models\ShelterCenter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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
                Section::make('بيانات الفعالية')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->label('عنوان الفعالية')
                                ->required()
                                ->maxLength(160),
                            Select::make('team_id')
                                ->label('الفريق المنفذ')
                                ->relationship('team', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Textarea::make('description')
                            ->label('وصف الفعالية')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('التصنيف')
                            ->relationship('category', 'name')
                            ->preload(),
                        FileUpload::make('image_path')
                            ->label('صورة الفعالية (اختياري)')
                            ->image()
                            ->disk('public')
                            ->directory('events')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth(1280)
                            ->maxSize(4096),
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
                                ->label('مركز الإيواء / المخيم (اختياري)')
                                ->options(
                                    fn (Get $get): array => ShelterCenter::query()
                                        ->where('is_active', true)
                                        ->when($get('area_id'), fn ($q, $areaId) => $q->where('area_id', $areaId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                )
                                ->searchable()
                                ->helperText('اتركه فارغاً إن كانت الفعالية في موقع غير مدرج'),
                        ]),
                        TextInput::make('location_details')
                            ->label('تفاصيل الموقع')
                            ->placeholder('مثال: الساحة الغربية قرب البوابة الرئيسية')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label('التاريخ')
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

                Section::make('الحالة والأعداد')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('الحالة')
                                ->options(EventStatus::class)
                                ->default(EventStatus::Pending)
                                ->required(),
                            TextInput::make('expected_children')
                                ->label('العدد المتوقع للأطفال')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(65000),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('actual_children')
                                ->label('عدد الأطفال الفعلي (بعد التنفيذ)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(65000),
                            TextInput::make('actual_caregivers')
                                ->label('عدد المرافقين الفعلي (بعد التنفيذ)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(65000),
                        ]),
                    ]),
            ]);
    }
}
