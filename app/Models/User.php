<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordArabic;
use App\Notifications\VerifyEmailArabic;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'area_id',
        'address',
        'password',
        'role',
        'is_active',
        'team_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    // ── بريد التحقق والاستعادة بالصياغة العربية ──

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailArabic);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordArabic($token));
    }

    // ── MFA (القسم 15.2): سرّ تطبيق المصادقة ورموز الاسترداد ──

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->forceFill(['app_authentication_secret' => $secret])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->forceFill(['app_authentication_recovery_codes' => $codes])->save();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function isFamily(): bool
    {
        return $this->role === UserRole::Family;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->role->isAdministrative(),
            'team' => $this->role === UserRole::TeamManager
                && $this->team !== null
                && $this->team->is_active,
            default => false,
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }
}
