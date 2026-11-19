<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\DeliveryMixChart;
use App\Filament\Widgets\FoundationOverviewWidget;
use App\Filament\Widgets\MonthlyRevenueChart;
use App\Filament\Widgets\RecentOperationsWidget;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdministratorMfa;
use App\Http\Middleware\EnsureUserHasRole;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Ecohydrotech ETMS Admin')
            ->colors([
                'primary' => Color::hex('#0F4C81'),
                'info' => Color::hex('#0D9488'),
                'success' => Color::hex('#059669'),
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('Overview'),
                NavigationGroup::make('Training & Catalogue'),
                NavigationGroup::make('Physical Sessions & Delivery'),
                NavigationGroup::make('Commerce & Finance'),
                NavigationGroup::make('Users & Access'),
                NavigationGroup::make('Communications & Reports'),
                NavigationGroup::make('System & Governance'),
            ])
            ->userMenuItems([
                Action::make('switch_to_portal')
                    ->label('Switch to Trainee Portal')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (): string => route('portal.dashboard'))
                    ->visible(fn (): bool => Auth::user()?->isCustomer() ?? false),
                Action::make('switch_to_instructor')
                    ->label('Switch to Instructor Portal')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->url(fn (): string => route('instructor.dashboard'))
                    ->visible(fn (): bool => Auth::user()?->isInstructor() ?? false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FoundationOverviewWidget::class,
                MonthlyRevenueChart::class,
                DeliveryMixChart::class,
                RecentOperationsWidget::class,
            ])
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
                EnsureAccountIsActive::class,
                EnsureUserHasRole::class.':administrator',
                EnsureAdministratorMfa::class,
            ]);
    }
}
