<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AspirasiTotalOverview;
use App\Filament\Widgets\SiswaOverview;
use App\Filament\Widgets\SiswaOverview2;
use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Auth\Login;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Openplain\FilamentShadcnTheme\Color;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
// use Filament\Support\Colors\Color;
use Illuminate\Session\Middleware\StartSession;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Andreia\FilamentNordTheme\FilamentNordThemePlugin;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Swis\Filament\Backgrounds\ImageProviders\MyImages;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->darkMode(false)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('SUARA SKASEN')
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->resources([
                config('filament-logger.activity_resource'),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                AspirasiTotalOverview::class,
                SiswaOverview::class,
                SiswaOverview2::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentBackgroundsPlugin::make()->imageProvider(MyImages::make()->directory('images/assets')),
                BreezyCore::make()
                    ->myProfile(),
                EasyFooterPlugin::make()
                    ->withFooterPosition('footer')
                    ->withSentence('SMK N Kebasen. All rights reserved')
                    ->withBorder(true),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
