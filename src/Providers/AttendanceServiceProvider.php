<?php

namespace Prasso\Church\Providers;

use Illuminate\Support\ServiceProvider;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\AttendanceGroup;
use Prasso\Church\Models\AttendanceSummary;
use Prasso\Church\Observers\AttendanceEventObserver;
use Prasso\Church\Observers\AttendanceRecordObserver;
use Prasso\Church\Observers\AttendanceGroupObserver;

class AttendanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register any bindings
        $this->app->bind('attendance', function ($app) {
            return new \Prasso\Church\Services\AttendanceService();
        });
        
        // Register attendance service as a singleton
        $this->app->singleton(\Prasso\Church\Services\AttendanceService::class, function ($app) {
            return new \Prasso\Church\Services\AttendanceService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register model observers
        AttendanceEvent::observe(AttendanceEventObserver::class);
        AttendanceRecord::observe(AttendanceRecordObserver::class);
        AttendanceGroup::observe(AttendanceGroupObserver::class);
        
        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        
        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/attendance.php' => config_path('attendance.php'),
        ], 'church-attendance-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations')
        ], 'church-attendance-migrations');
    }
}
