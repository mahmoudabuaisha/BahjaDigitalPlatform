<?php

namespace App\Filament\Admin\Pages;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use App\Models\UserNotification;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** رسالة عامة تصل العائلات إشعاراً داخل المنصّة — بلا بريد ولا رسائل نصية */
class BroadcastMessage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $title = 'رسالة عامة للعائلات';

    protected static ?string $navigationLabel = 'رسالة عامة';

    protected static string|UnitEnum|null $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.pages.broadcast-message';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('نص الرسالة')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('العنوان')
                            ->placeholder('مثال: تعطّل فعاليات اليوم بسبب الطقس')
                            ->required()
                            ->maxLength(160),
                        Textarea::make('body')
                            ->label('التفاصيل')
                            ->rows(3)
                            ->maxLength(400),
                        Select::make('area_id')
                            ->label('لمن تُرسل؟')
                            ->options(fn () => ['' => 'كل العائلات'] + Area::orderBy('sort_order')->pluck('name', 'id')->all())
                            ->default('')
                            ->helperText('اختيار محافظة يقصر الرسالة على عائلاتها المسجَّلة فيها'),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $recipients = User::query()
            ->where('role', UserRole::Family)
            ->where('is_active', true)
            ->when(filled($data['area_id'] ?? null), fn ($query) => $query->where('area_id', $data['area_id']))
            ->pluck('id');

        foreach ($recipients as $userId) {
            UserNotification::send($userId, 'admin_message', $data['title'], $data['body'] ?? null);
        }

        $this->form->fill();

        Notification::make()
            ->title('أُرسلت الرسالة إلى '.$recipients->count().' عائلة')
            ->success()
            ->send();
    }
}
