<?php

namespace App\Providers\Filament;

use App\Filament\Platform\InitialsAvatarProvider;
use App\Filament\Platform\Pages\EditProfile;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\VerifyCsrfToken;
use App\Services\PlatformAudit;
use App\Services\SuperAdminSession;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PlatformPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('platform')
            ->path('platform')
            ->authGuard(SuperAdminSession::GUARD)
            ->login()
            ->profile(EditProfile::class, isSimple: false)
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
            ->brandName('Cinemax Zmeny · Platforma')
            ->colors(['primary' => Color::Sky])
            ->darkMode(true)
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->discoverResources(in: app_path('Filament/Platform/Resources'), for: 'App\Filament\Platform\Resources')
            ->discoverPages(in: app_path('Filament/Platform/Pages'), for: 'App\Filament\Platform\Pages')
            ->discoverWidgets(in: app_path('Filament/Platform/Widgets'), for: 'App\Filament\Platform\Widgets')
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // After Authenticate, which is what switches the default guard to `platform`.
                AuthenticateSession::class,
            ], isPersistent: true);
    }

    public function boot(): void
    {
        Filament::serving(fn () => app(PlatformAudit::class)->listenForModelChanges());
    }
}
