<?php

namespace Prasso\Church\Observers;

use Prasso\Church\Models\AttendanceGroup;
use Prasso\Church\Services\AttendanceService;

class AttendanceGroupObserver
{
    /**
     * The attendance service instance.
     *
     * @var \Prasso\Church\Services\AttendanceService
     */
    protected $attendanceService;

    /**
     * Create a new observer instance.
     *
     * @param  \Prasso\Church\Services\AttendanceService  $attendanceService
     * @return void
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handle the AttendanceGroup "created" event.
     *
     * @param  \Prasso\Church\Models\AttendanceGroup  $group
     * @return void
     */
    public function created(AttendanceGroup $group)
    {
        // Update any existing attendance records that might be affected by this new group
        if ($group->members()->exists() || $group->families()->exists() || $group->groups()->exists()) {
            $this->updateRelatedAttendanceRecords($group);
        }
    }

    /**
     * Handle the AttendanceGroup "updated" event.
     *
     * @param  \Prasso\Church\Models\AttendanceGroup  $group
     * @return void
     */
    public function updated(AttendanceGroup $group)
    {
        // If members, families, or groups were synced, update related attendance records
        if ($group->wasChanged(['members', 'families', 'groups'])) {
            $this->updateRelatedAttendanceRecords($group);
        }
    }

    /**
     * Handle the AttendanceGroup "deleted" event.
     *
     * @param  \Prasso\Church\Models\AttendanceGroup  $group
     * @return void
     */
    public function deleted(AttendanceGroup $group)
    {
        // When a group is deleted, we don't need to do anything special
        // The database constraints will handle the cleanup of related records
    }

    /**
     * Update attendance records related to this group.
     *
     * @param  \Prasso\Church\Models\AttendanceGroup  $group
     * @return void
     */
    protected function updateRelatedAttendanceRecords(AttendanceGroup $group)
    {
        // Get all events associated with this group
        $eventIds = $group->events()->pluck('id');
        
        if ($eventIds->isNotEmpty()) {
            // Update attendance summaries for all events in this group
            foreach ($eventIds as $eventId) {
                $this->attendanceService->updateEventSummary($eventId);
            }
            
            // If this group is associated with a ministry, update ministry summaries
            if ($group->ministry_id) {
                $this->attendanceService->updateMinistrySummary(
                    $group->ministry_id,
                    now()->subYear(),
                    now()
                );
            }
            
            // Update group summary
            $this->attendanceService->updateGroupSummary(
                $group->id,
                now()->subYear(),
                now()
            );
        }
    }
}
