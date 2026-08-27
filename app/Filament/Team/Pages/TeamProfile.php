<?php

namespace App\Filament\Team\Pages;

use App\Models\Team;
use App\Services\ImageService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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
            'name', 'description', 'logo_path', 'contact_name', 'whatsapp_phone',
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
                        Grid::make(2)->schema([
                            TextInput::make('contact_name')
                                ->label('اسم المنسق')
                                ->maxLength(255),
                            TextInput::make('whatsapp_phone')
                                ->label('واتساب التواصل (بالصيغة الدولية)')
                                ->placeholder('970599999999')
                                ->tel()
                                ->maxLength(20),
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
