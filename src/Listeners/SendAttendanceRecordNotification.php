<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\AttendanceRecorded;
use Prasso\Church\Events\AttendanceUpdated;
use Prasso\Church\Notifications\AttendanceRecordedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAttendanceRecordNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\AttendanceRecorded|\Prasso\Church\Events\AttendanceUpdated  $event
     * @return void
     */
    public function handle($event)
    {
        $record = $event->record;
        $notification = null;

        if ($event instanceof \Prasso\Church\Events\AttendanceRecorded) {
            $notification = new AttendanceRecordedNotification($record);
        } elseif ($event instanceof \Prasso\Church\Events\AttendanceUpdated) {
            // Only send update notifications if certain fields changed
            $changedFields = array_keys($event->original);
            $importantFields = ['status', 'check_in_time', 'check_out_time'];
            
            if (count(array_intersect($importantFields, $changedFields)) > 0) {
                $notification = new \Prasso\Church\Notifications\AttendanceUpdatedNotification(
                    $record,
                    $event->original
                );
            }
        }

        if ($notification) {
            // Notify the member if they have a user account
            if ($record->member && $record->member->user) {
                $record->member->user->notify($notification);
            }

            // Notify family members if this is a family record
            if ($record->family) {
                $familyMembers = $record->family->members()->with('user')->get();
                foreach ($familyMembers as $member) {
                    if ($member->user) {
                        $member->user->notify($notification);
                    }
                }
            }

            // Notify event creator if different from current user
            if ($record->event->creator && $record->event->creator->id !== auth()->id()) {
                $record->event->creator->notify($notification);
            }

            // Notify group leaders if this is a group event
            if ($record->event->group) {
                $groupLeaders = $record->event->group->leaders()->get();
                Notification::send($groupLeaders, $notification);
            }

            // Notify ministry leaders if this is a ministry event
            if ($record->event->ministry) {
                $ministryLeaders = $record->event->ministry->leaders()->get();
                Notification::send($ministryLeaders, $notification);
            }

            // Notify administrators
            $admins = \App\Models\User::role('admin')->get();
            Notification::send($admins, $notification);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Prasso\Church\Events\AttendanceRecorded|\Prasso\Church\Events\AttendanceUpdated  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed($event, $exception)
    {
        // Log the failure
        \Log::error('Failed to send attendance record notification', [
            'record_id' => $event->record->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
