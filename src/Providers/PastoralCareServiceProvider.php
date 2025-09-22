<?php

namespace Prasso\Church\Providers;

use Illuminate\Support\ServiceProvider;
use Prasso\Church\Events\PrayerRequestCreated;
use Prasso\Church\Events\PastoralVisitAssigned;
use Prasso\Church\Events\PastoralVisitCompleted;
use Prasso\Church\Listeners\SendPrayerRequestNotification;
use Prasso\Church\Listeners\SendPastoralVisitAssignedNotification;
use Prasso\Church\Listeners\SendPastoralVisitCompletedNotification;
use Illuminate\Support\Facades\Event;

class PastoralCareServiceProvider extends ServiceProvider
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
        // Register event listeners
        Event::listen(
            PrayerRequestCreated::class,
            [SendPrayerRequestNotification::class, 'handle']
        );

        Event::listen(
            PastoralVisitAssigned::class,
            [SendPastoralVisitAssignedNotification::class, 'handle']
        );

        Event::listen(
            PastoralVisitCompleted::class,
            [SendPastoralVisitCompletedNotification::class, 'handle']
        );
        
        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        
        // Register views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'church');
        
        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/church.php' => config_path('church.php'),
        ], 'church-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations')
        ], 'church-migrations');
        
        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/church'),
        ], 'church-views');
        
        // Publish assets
        $this->publishes([
            __DIR__.'/../../resources/assets' => public_path('vendor/church'),
        ], 'church-assets');
    }
}
