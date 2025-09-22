<?php

namespace Prasso\Church\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Prasso\Church\Events\PrayerRequestCreated;
use Prasso\Church\Events\PastoralVisitAssigned;
use Prasso\Church\Events\PastoralVisitCompleted;
use Prasso\Church\Events\AttendanceEventCreated;
use Prasso\Church\Events\AttendanceEventUpdated;
use Prasso\Church\Events\AttendanceEventDeleted;
use Prasso\Church\Events\AttendanceRecorded;
use Prasso\Church\Events\AttendanceUpdated;
use Prasso\Church\Events\AttendanceDeleted;
use Prasso\Church\Events\InboundMessageReceived;
use Prasso\Church\Listeners\SendPrayerRequestNotification;
use Prasso\Church\Listeners\SendPastoralVisitAssignedNotification;
use Prasso\Church\Listeners\SendPastoralVisitCompletedNotification;
use Prasso\Church\Listeners\SendAttendanceEventNotification;
use Prasso\Church\Listeners\SendAttendanceRecordNotification;
use Prasso\Church\Listeners\UpdateAttendanceSummaries;
use Prasso\Church\Listeners\ProcessPrayerRequestMessage;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Prayer Request Events
        PrayerRequestCreated::class => [
            SendPrayerRequestNotification::class,
        ],
        
        // Pastoral Visit Events
        PastoralVisitAssigned::class => [
            SendPastoralVisitAssignedNotification::class,
        ],
        PastoralVisitCompleted::class => [
            SendPastoralVisitCompletedNotification::class,
        ],
        
        // Attendance Event Events
        AttendanceEventCreated::class => [
            SendAttendanceEventNotification::class,
            UpdateAttendanceSummaries::class,
        ],
        AttendanceEventUpdated::class => [
            SendAttendanceEventNotification::class,
            UpdateAttendanceSummaries::class,
        ],
        AttendanceEventDeleted::class => [
            UpdateAttendanceSummaries::class,
        ],
        
        // Attendance Record Events
        AttendanceRecorded::class => [
            SendAttendanceRecordNotification::class,
            UpdateAttendanceSummaries::class,
        ],
        AttendanceUpdated::class => [
            SendAttendanceRecordNotification::class,
            UpdateAttendanceSummaries::class,
        ],
        AttendanceDeleted::class => [
            UpdateAttendanceSummaries::class,
        ],
        
        // SMS Prayer Request Events
        InboundMessageReceived::class => [
            ProcessPrayerRequestMessage::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
