<?php

namespace App\Providers\Filament;

use App\Filament\InitialsAvatarProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // MFA اختياري بقرار المالك (كان إلزامياً وفق القسم 15.2 فأعاق الدخول اليومي):
            // الدخول بريد وكلمة سر، ومن فعّل تطبيق المصادقة لحسابه يُطالَب برمزه.
            // لإعادته إلزامياً: isRequired: fn (): bool => ! app()->runningUnitTests()
            ->multiFactorAuthentication(AppAuthentication::make()->recoverable())
            ->brandName('بَهْجَة — لوحة الإدارة')
            ->colors([
                // الأزرق نفسه المستعمل في الموقع العام (--color-brand-600)
                'primary' => Color::hex('#2678ca'),
            ])
            ->brandLogo(fn (): ?string => file_exists(public_path('brand/logo.png')) ? asset('brand/logo.png') : null)
            ->brandLogoHeight('2.75rem')
            ->sidebarCollapsibleOnDesktop()
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
