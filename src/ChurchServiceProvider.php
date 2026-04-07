<?php

namespace Prasso\Church;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Prasso\Church\Providers\EventServiceProvider;
use Prasso\Church\Filament\FilamentServiceProvider;
use Prasso\Church\Providers\PastoralCareServiceProvider;
use Livewire\Livewire;

class ChurchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/church.php', 'church'
        );
        
        // Register the event service provider
        $this->app->register(EventServiceProvider::class);
        
        // Register the pastoral care service provider
        $this->app->register(PastoralCareServiceProvider::class);
        
        // Register the Filament service provider
        $this->app->register(FilamentServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/church.php' => config_path('church.php'),
        ], 'church-config');
        
        // Load API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load Web routes (ensure 'web' middleware is applied)
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'church');
        
        // Register Livewire components
        Livewire::component('prasso.church.member-dashboard-widget', \Prasso\Church\Livewire\MemberDashboardWidget::class);
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'church-migrations');
        
        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/church'),
        ], 'church-views');
        
        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/church'),
        ], 'church-assets');
    }
}
