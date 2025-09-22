<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\AttendanceEventCreated;
use Prasso\Church\Events\AttendanceEventUpdated;
use Prasso\Church\Notifications\AttendanceEventCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAttendanceEventNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\AttendanceEventCreated|\Prasso\Church\Events\AttendanceEventUpdated  $event
     * @return void
     */
    public function handle($event)
    {
        $attendanceEvent = $event->event;
        $notification = null;

        if ($event instanceof \Prasso\Church\Events\AttendanceEventCreated) {
            $notification = new AttendanceEventCreatedNotification($attendanceEvent);
        } elseif ($event instanceof \Prasso\Church\Events\AttendanceEventUpdated) {
            // Only send update notifications if certain fields changed
            $changedFields = array_keys($event->original);
            $importantFields = ['name', 'start_time', 'end_time', 'location_id', 'status'];
            
            if (count(array_intersect($importantFields, $changedFields)) > 0) {
                $notification = new \Prasso\Church\Notifications\AttendanceEventUpdatedNotification(
                    $attendanceEvent,
                    $event->original
                );
            }
        }

        if ($notification) {
            // Notify event creator
            if ($attendanceEvent->creator && $attendanceEvent->creator->id !== auth()->id()) {
                $attendanceEvent->creator->notify($notification);
            }

            // Notify ministry leaders if applicable
            if ($attendanceEvent->ministry) {
                $ministryLeaders = $attendanceEvent->ministry->leaders()->get();
                Notification::send($ministryLeaders, $notification);
            }

            // Notify group leaders if applicable
            if ($attendanceEvent->group) {
                $groupLeaders = $attendanceEvent->group->leaders()->get();
                Notification::send($groupLeaders, $notification);
            }

            // Notify administrators
            $admins = \App\Models\User::role('admin')->get();
            Notification::send($admins, $notification);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Prasso\Church\Events\AttendanceEventCreated|\Prasso\Church\Events\AttendanceEventUpdated  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed($event, $exception)
    {
        // Log the failure
        \Log::error('Failed to send attendance event notification', [
            'event_id' => $event->event->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
