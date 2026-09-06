<?php

namespace App\Filament\Admin\Resources\Teams\Schemas;

use App\Enums\OrgType;
use App\Models\Area;
use App\Models\TeamApplication;
use App\Services\ImageService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الفريق')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('اسم الفريق')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('slug')
                                ->label('المعرّف اللاتيني (للرابط)')
                                ->helperText('أحرف إنجليزية صغيرة وأرقام وشرطات فقط، مثال: basmat-amal')
                                ->required()
                                ->maxLength(60)
                                ->unique(ignoreRecord: true)
                                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('org_type')
                                ->label('نوع الجهة')
                                ->options(OrgType::options()),
                            Select::make('area_id')
                                ->label('المحافظة')
                                ->options(fn () => Area::orderBy('name')->pluck('name', 'id')),
                            TextInput::make('base_location')
                                ->label('المقر / منطقة التواجد')
                                ->maxLength(160),
                        ]),
                        Textarea::make('description')
                            ->label('نبذة عن الفريق')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('شعار الفريق')
                            ->image()
                            ->disk('public')
                            ->directory('teams')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth(512)
                            ->maxSize(2048)
                            // إعادة الترميز على الخادم تمسح EXIF/GPS قبل التخزين
                            ->saveUploadedFileUsing(fn ($file): string => app(ImageService::class)->store($file, 'teams', 'logo_path')),
                    ]),

                Section::make('الإمكانيات الميدانية')
                    ->columnSpanFull()
                    ->schema([
                        CheckboxList::make('activities')
                            ->label('الأنشطة المتقنة')
                            ->options(TeamApplication::ACTIVITIES)
                            ->columns(3),
                        CheckboxList::make('coverage_areas')
                            ->label('محافظات القدرة على الوصول')
                            ->options(fn () => Area::orderBy('name')->pluck('name', 'id'))
                            ->columns(3),
                        Grid::make(3)->schema([
                            TextInput::make('coverage_details')
                                ->label('مراكز الإيواء / المخيمات المغطاة')
                                ->maxLength(160),
                            TextInput::make('volunteers_count')
                                ->label('عدد المتطوعين')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('capacity_per_event')
                                ->label('الاستيعاب في النشاط الواحد')
                                ->numeric()
                                ->minValue(1),
                        ]),
                    ]),

                Section::make('التواصل والحالة')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('contact_name')
                                ->label('اسم مسؤول الميدان')
                                ->maxLength(255),
                            TextInput::make('whatsapp_phone')
                                ->label('واتساب (بالصيغة الدولية)')
                                ->placeholder('970599999999')
                                ->tel()
                                ->maxLength(20),
                            TextInput::make('emergency_phone')
                                ->label('هاتف الطوارئ')
                                ->tel()
                                ->maxLength(30),
                        ]),
                        Toggle::make('is_active')
                            ->label('فريق معتمد ونشط')
                            ->helperText('الفرق غير المعتمدة لا تستطيع الدخول للوحتها ولا تظهر فعالياتها'),
                    ]),
            ]);
    }
}
