<?php

namespace Prasso\Church\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Prasso\Church\Http\Middleware\AuthenticateWithSanctum;
use Prasso\Church\Models\Pledge;
use Prasso\Church\Policies\PledgePolicy;

class ChurchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        
        // Publish config file
        $this->publishes([
            __DIR__.'/../../config/church.php' => config_path('church.php'),
        ], 'church-config');
        
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        
        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('church.auth', \Prasso\Church\Http\Middleware\AuthenticateWithSanctum::class);
        
        // Publish assets
        $this->publishes([
            __DIR__.'/../../resources/assets' => public_path('vendor/church'),
        ], 'church-assets');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register your commands here
            ]);
        }
    }
    
    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        // Web routes
        Route::group($this->webRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        });

        // API routes
        Route::group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        });
    }
    
    /**
     * Register middleware.
     */
    protected function registerMiddleware()
    {
        $this->app['router']->aliasMiddleware('church.auth', AuthenticateWithSanctum::class);
    }

    /**
     * Get the web route group configuration array.
     */
    protected function webRouteConfiguration()
    {
        return [
            'prefix' => config('church.route_prefix', 'church'),
            'middleware' => 'web',
            'as' => 'church.',
        ];
    }
    
    /**
     * Get the API route group configuration array.
     */
    protected function apiRouteConfiguration()
    {
        return [
            'prefix' => 'api/' . config('church.route_prefix', 'church'),
            'middleware' => ['api', 'auth:sanctum'],
            'as' => 'church.api.',
        ];
    }


    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/church.php', 'church'
        );
        
        // Register policies
        Gate::policy(Pledge::class, PledgePolicy::class);
        
        // Register the main class to use with the facade
        $this->app->singleton('church', function ($app) {
            return new \Prasso\Church\Church($app);
        });
        
        // Register the messaging service
        $this->app->singleton('church.messaging', function ($app) {
            return new \Prasso\Church\Services\ChurchMessagingService();
        });
        
        // Register the SMS prayer request service
        $this->app->singleton('church.sms_prayer', function ($app) {
            return new \Prasso\Church\Services\SmsPrayerRequestService();
        });
        
        // Register the financial service
        $this->app->singleton('church.financial', function ($app) {
            return new \Prasso\Church\Services\FinancialService();
        });
        
        // Register service providers
        $this->app->register(EventServiceProvider::class);
        $this->app->register(MessagingIntegrationServiceProvider::class);
        $this->app->register(FilamentServiceProvider::class);
        
        // Register view namespace
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'church');
    }
}
