<?php

namespace Prasso\Church\Observers;

use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Events\AttendanceRecorded;
use Prasso\Church\Events\AttendanceUpdated;
use Prasso\Church\Events\AttendanceDeleted;
use Prasso\Church\Services\AttendanceService;

class AttendanceRecordObserver
{
    /**
     * Handle the AttendanceRecord "created" event.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    public function created(AttendanceRecord $record)
    {
        // Update the attendance summary
        $this->updateAttendanceSummary($record);
        
        // Fire event
        event(new AttendanceRecorded($record));
    }

    /**
     * Handle the AttendanceRecord "updated" event.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    public function updated(AttendanceRecord $record)
    {
        // Only update summary if relevant fields changed
        if ($record->wasChanged(['status', 'check_in_time', 'check_out_time', 'guest_count'])) {
            $this->updateAttendanceSummary($record);
        }
        
        // Fire event
        event(new AttendanceUpdated($record));
    }

    /**
     * Handle the AttendanceRecord "deleted" event.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    public function deleted(AttendanceRecord $record)
    {
        // Update the attendance summary
        $this->updateAttendanceSummary($record);
        
        // Fire event
        event(new AttendanceDeleted($record));
    }

    /**
     * Handle the AttendanceRecord "forceDeleted" event.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    public function forceDeleted(AttendanceRecord $record)
    {
        //
    }
    
    /**
     * Update the attendance summary for the record's event.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    protected function updateAttendanceSummary(AttendanceRecord $record)
    {
        $event = $record->event;
        
        if (!$event) {
            return;
        }
        
        $startOfDay = $event->start_time->copy()->startOfDay();
        $endOfDay = $event->start_time->copy()->endOfDay();
        
        // Update summary for the event
        app(AttendanceService::class)->updateEventSummary($event);
        
        // Update ministry summary if applicable
        if ($event->ministry_id) {
            app(AttendanceService::class)->updateMinistrySummary(
                $event->ministry_id, 
                $startOfDay, 
                $endOfDay
            );
        }
        
        // Update group summary if applicable
        if ($event->group_id) {
            app(AttendanceService::class)->updateGroupSummary(
                $event->group_id, 
                $startOfDay, 
                $endOfDay
            );
        }
    }
}
