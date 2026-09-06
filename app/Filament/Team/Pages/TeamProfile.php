<?php

namespace App\Filament\Team\Pages;

use App\Enums\OrgType;
use App\Models\Area;
use App\Models\Team;
use App\Models\TeamApplication;
use App\Services\ImageService;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TeamProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $title = 'ملف الفريق';

    protected static ?string $navigationLabel = 'ملف الفريق';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.team.pages.team-profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getTeam()->only([
            'name', 'org_type', 'area_id', 'base_location', 'description', 'logo_path',
            'contact_name', 'whatsapp_phone', 'emergency_phone',
            'coverage_details', 'coverage_areas', 'activities',
            'volunteers_count', 'capacity_per_event',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الفريق')
                    ->description('تظهر هذه البيانات للعائلات في صفحة فريقكم بالموقع العام')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الفريق')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('description')
                            ->label('نبذة عن الفريق وأنشطته')
                            ->rows(4),
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
                        Grid::make(3)->schema([
                            TextInput::make('contact_name')
                                ->label('اسم مسؤول الميدان')
                                ->maxLength(255),
                            TextInput::make('whatsapp_phone')
                                ->label('واتساب التواصل (بالصيغة الدولية)')
                                ->placeholder('970599999999')
                                ->tel()
                                ->maxLength(20),
                            TextInput::make('emergency_phone')
                                ->label('هاتف الطوارئ')
                                ->tel()
                                ->maxLength(30),
                        ]),
                    ]),

                Section::make('إمكانياتكم الميدانية')
                    ->description('تساعد الإدارة على توجيه التغطية وتظهر ملامحها للعائلات')
                    ->columnSpanFull()
                    ->schema([
                        CheckboxList::make('activities')
                            ->label('الأنشطة التي تتقنونها')
                            ->options(TeamApplication::ACTIVITIES)
                            ->columns(2),
                        CheckboxList::make('coverage_areas')
                            ->label('محافظات القدرة على الوصول')
                            ->options(fn () => Area::orderBy('name')->pluck('name', 'id'))
                            ->columns(2),
                        TextInput::make('coverage_details')
                            ->label('مراكز الإيواء / المخيمات التي تغطونها')
                            ->maxLength(160),
                        Grid::make(2)->schema([
                            TextInput::make('volunteers_count')
                                ->label('عدد المتطوعين')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('capacity_per_event')
                                ->label('كم طفلاً تستوعبون في النشاط الواحد؟')
                                ->numeric()
                                ->minValue(1),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $team = $this->getTeam();

        $this->authorize('update', $team);

        $team->update($this->form->getState());

        Notification::make()
            ->title('تم حفظ ملف الفريق')
            ->success()
            ->send();
    }

    private function getTeam(): Team
    {
        return auth()->user()->team;
    }
}
