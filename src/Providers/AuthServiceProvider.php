<?php

namespace Prasso\Church\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\AttendanceGroup;
use Prasso\Church\Policies\AttendancePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AttendanceEvent::class => AttendancePolicy::class,
        AttendanceRecord::class => AttendancePolicy::class,
        AttendanceGroup::class => AttendancePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
        // Define attendance-related gates
        $this->defineGates();
    }
    
    /**
     * Define the attendance-related gates.
     *
     * @return void
     */
    protected function defineGates()
    {
        // Attendance Permissions
        \Gate::define('view_attendance', function ($user) {
            return $user->hasPermissionTo('view_attendance');
        });
        
        \Gate::define('create_attendance_events', function ($user) {
            return $user->hasPermissionTo('create_attendance_events');
        });
        
        \Gate::define('update_attendance_events', function ($user) {
            return $user->hasPermissionTo('update_attendance_events');
        });
        
        \Gate::define('delete_attendance_events', function ($user) {
            return $user->hasPermissionTo('delete_attendance_events');
        });
        
        \Gate::define('create_attendance_records', function ($user) {
            return $user->hasPermissionTo('create_attendance_records');
        });
        
        \Gate::define('update_attendance_records', function ($user) {
            return $user->hasPermissionTo('update_attendance_records');
        });
        
        \Gate::define('delete_attendance_records', function ($user) {
            return $user->hasPermissionTo('delete_attendance_records');
        });
        
        \Gate::define('view_attendance_groups', function ($user) {
            return $user->hasPermissionTo('view_attendance_groups');
        });
        
        \Gate::define('create_attendance_groups', function ($user) {
            return $user->hasPermissionTo('create_attendance_groups');
        });
        
        \Gate::define('update_attendance_groups', function ($user) {
            return $user->hasPermissionTo('update_attendance_groups');
        });
        
        \Gate::define('delete_attendance_groups', function ($user) {
            return $user->hasPermissionTo('delete_attendance_groups');
        });
        
        \Gate::define('check_in_attendance', function ($user) {
            return $user->hasPermissionTo('check_in_attendance');
        });
        
        \Gate::define('check_out_attendance', function ($user) {
            return $user->hasPermissionTo('check_out_attendance');
        });
        
        \Gate::define('view_attendance_reports', function ($user) {
            return $user->hasPermissionTo('view_attendance_reports');
        });
        
        \Gate::define('export_attendance_data', function ($user) {
            return $user->hasPermissionTo('export_attendance_data');
        });
        
        \Gate::define('manage_attendance_settings', function ($user) {
            return $user->hasPermissionTo('manage_attendance_settings');
        });
    }
}
