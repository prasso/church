<?php

namespace Prasso\Church\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Prasso\Church\Filament\Widgets\ChurchOverview;
use Prasso\Church\Filament\Widgets\ChurchMembershipGrowth;
use Prasso\Church\Filament\Widgets\ChurchQuickActions;
use Prasso\Church\Filament\Widgets\ChurchRecentActivity;
use Prasso\Church\Filament\Widgets\SmsPrayerRequestsWidget;
use Prasso\Church\Filament\Pages\SmsPrayerRequests;
use Prasso\Church\Filament\Resources\PrayerRequestResource;
use Prasso\Church\Filament\Resources\VolunteerPositionResource;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register widgets
        Filament::registerWidgets([
            ChurchOverview::class,
            ChurchMembershipGrowth::class,
            ChurchQuickActions::class,
            ChurchRecentActivity::class,
            SmsPrayerRequestsWidget::class,
        ]);
        
        // Register pages
        Filament::registerPages([
            SmsPrayerRequests::class,
        ]);
        
        // Register resources
        Filament::registerResources([
            PrayerRequestResource::class,
            VolunteerPositionResource::class,
        ]);
    }
}
