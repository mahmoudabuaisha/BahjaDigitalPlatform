<?php

namespace App\Filament\Admin\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'إعدادات المنصة';

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static string|UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.admin.pages.manage-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Settings::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الموقع')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('site_name')
                            ->label('اسم المنصة')
                            ->required(),
                        TextInput::make('site_whatsapp')
                            ->label('واتساب المبادرة (بالصيغة الدولية)')
                            ->placeholder('970599999999')
                            ->tel(),
                        Textarea::make('about_text')
                            ->label('نبذة عن المبادرة (تظهر في الموقع العام)')
                            ->rows(4),
                        FileUpload::make('og_default_image')
                            ->label('صورة المشاركة الافتراضية (واتساب) — 1200×630')
                            ->image()
                            ->disk('public')
                            ->directory('site'),
                    ]),
                Section::make('أهداف المبادرة (لتقارير الأثر)')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('target_children')
                            ->label('الهدف: عدد الأطفال المستفيدين')
                            ->numeric()
                            ->required(),
                        TextInput::make('target_indirect')
                            ->label('الهدف: المستفيدون غير المباشرين')
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        Settings::setMany($this->form->getState());

        Notification::make()
            ->title('تم حفظ الإعدادات')
            ->success()
            ->send();
    }
}
