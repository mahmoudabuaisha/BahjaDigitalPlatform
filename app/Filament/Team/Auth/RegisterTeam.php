<?php

namespace App\Filament\Team\Auth;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * تسجيل ذاتي للفرق: ينشئ الفريق (غير معتمد) + حساب المسؤول في خطوة واحدة،
 * ولا يسجّل الدخول — الدخول يصبح ممكناً بعد اعتماد الإدارة للفريق.
 */
class RegisterTeam extends Register
{
    public function getHeading(): string
    {
        return 'انضمام فريق جديد';
    }

    public function getSubheading(): ?string
    {
        return 'سجّلوا فريقكم وسيتواصل معكم فريق بهجة بعد المراجعة';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الفريق')
                    ->schema([
                        TextInput::make('team_name')
                            ->label('اسم الفريق')
                            ->required()
                            ->maxLength(120)
                            ->unique(table: Team::class, column: 'name'),
                        TextInput::make('team_whatsapp')
                            ->label('واتساب الفريق (بالصيغة الدولية)')
                            ->placeholder('970599999999')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                    ]),
                Section::make('بيانات مسؤول الحساب')
                    ->schema([
                        $this->getNameFormComponent()
                            ->label('اسم المسؤول'),
                        $this->getEmailFormComponent()
                            ->label('البريد الإلكتروني'),
                        $this->getPasswordFormComponent()
                            ->label('كلمة المرور'),
                        $this->getPasswordConfirmationFormComponent()
                            ->label('تأكيد كلمة المرور'),
                    ]),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        DB::transaction(function () use ($data): void {
            $team = Team::create([
                'name' => $data['team_name'],
                'slug' => 'team-'.Str::lower(Str::random(8)),
                'whatsapp_phone' => $data['team_whatsapp'],
                'contact_name' => $data['name'],
                'is_active' => false,
            ]);

            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::TeamManager,
                'team_id' => $team->id,
                'is_active' => true,
            ]);
        });

        Notification::make()
            ->title('تم استلام طلب انضمامكم')
            ->body('سيراجع فريق بهجة الطلب ويتواصل معكم عبر واتساب، وبعد الاعتماد يمكنكم تسجيل الدخول.')
            ->success()
            ->persistent()
            ->send();

        $this->redirect(Filament::getLoginUrl());

        return null;
    }
}
