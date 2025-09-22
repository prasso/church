<?php

namespace Prasso\Church\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\Location;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Family;

use Prasso\Church\Events\AttendanceEventCreated;
use Prasso\Church\Events\AttendanceEventUpdated;
use Prasso\Church\Events\AttendanceEventDeleted;
use Prasso\Church\Events\AttendanceRecorded;
use Prasso\Church\Events\AttendanceUpdated;
use Prasso\Church\Events\AttendanceDeleted;
use Prasso\Church\Services\AttendanceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class UpdateAttendanceSummaries
 * 
 * This listener handles attendance summary updates when attendance-related events occur.
 * It updates event, ministry, and group summaries, as well as member and family stats.
 */
class UpdateAttendanceSummaries implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Update member attendance statistics.
     *
     * @param int $memberId
     * @return void
     */
    protected function updateMemberStats($memberId)
    {
        $member = Member::find($memberId);
        if ($member && method_exists($member, 'updateAttendanceStats')) {
            $member->updateAttendanceStats();
        }
    }

    /**
     * Update family attendance statistics.
     *
     * @param int $familyId
     * @return void
     */
    protected function updateFamilyStats($familyId)
    {
        $family = Family::find($familyId);
        if ($family && method_exists($family, 'updateAttendanceStats')) {
            $family->updateAttendanceStats();
        }
    }

    /**
     * The attendance service instance.
     *
     * @var \Prasso\Church\Services\AttendanceService
     */
    protected $attendanceService;

    /**
     * Create the event listener.
     *
     * @param  \Prasso\Church\Services\AttendanceService  $attendanceService
     * @return void
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handle attendance event created.
     *
     * @param  \Prasso\Church\Events\AttendanceEventCreated  $event
     * @return void
     */
    public function handleAttendanceEventCreated(AttendanceEventCreated $event)
    {
        $eventModel = $event->event;
        $this->attendanceService->updateEventSummary($eventModel);
        
        if ($eventModel->ministry_id) {
            $this->attendanceService->updateMinistrySummary(
                $eventModel->ministry_id,
                $eventModel->start_time->copy()->startOfDay(),
                $eventModel->start_time->copy()->endOfDay()
            );
        }
        
        if ($eventModel->group_id) {
            $this->attendanceService->updateGroupSummary(
                $eventModel->group_id,
                $eventModel->start_time->copy()->startOfDay(),
                $eventModel->start_time->copy()->endOfDay()
            );
        }
    }

    /**
     * Handle attendance event updated.
     *
     * @param  \Prasso\Church\Events\AttendanceEventUpdated  $event
     * @return void
     */
    public function handleAttendanceEventUpdated(AttendanceEventUpdated $event)
    {
        $original = $event->original;
        $event = $event->event;
        
        // If the event date or time changed, update summaries for both old and new dates
        if (isset($original['start_time']) && $original['start_time'] != $event->start_time) {
            // Update summary for the old date
            $originalDate = Carbon::parse($original['start_time']);
            $this->attendanceService->generateSummaryForDateRange(
                $originalDate->copy()->startOfDay(),
                $originalDate->copy()->endOfDay(),
                ['event_id' => $event->id]
            );
            
            if ($event->ministry_id) {
                $this->attendanceService->updateMinistrySummary(
                    $event->ministry_id,
                    $originalDate->copy()->startOfDay(),
                    $originalDate->copy()->endOfDay()
                );
            }
            
            if ($event->group_id) {
                $this->attendanceService->updateGroupSummary(
                    $event->group_id,
                    $originalDate->copy()->startOfDay(),
                    $originalDate->copy()->endOfDay()
                );
            }
        }
        
        // Update summary for the current date
        $this->handleAttendanceEventCreated(new AttendanceEventCreated($event));
    }

    /**
     * Handle attendance event deleted.
     *
     * @param  \Prasso\Church\Events\AttendanceEventDeleted  $event
     * @return void
     */
    public function handleAttendanceEventDeleted(AttendanceEventDeleted $event)
    {
        $event = $event->event;
        $eventDate = $event->start_time;
        
        // Update summaries for the event date
        $this->attendanceService->generateSummaryForDateRange(
            $eventDate->copy()->startOfDay(),
            $eventDate->copy()->endOfDay(),
            ['event_id' => $event->id]
        );
        
        if ($event->ministry_id) {
            $this->attendanceService->updateMinistrySummary(
                $event->ministry_id,
                $eventDate->copy()->startOfDay(),
                $eventDate->copy()->endOfDay()
            );
        }
        
        if ($event->group_id) {
            $this->attendanceService->updateGroupSummary(
                $event->group_id,
                $eventDate->copy()->startOfDay(),
                $eventDate->copy()->endOfDay()
            );
        }
    }

    /**
     * Handle attendance recorded.
     *
     * @param  \Prasso\Church\Events\AttendanceRecorded  $event
     * @return void
     */
    public function handleAttendanceRecorded(AttendanceRecorded $event)
    {
        $record = $event->record;
        $eventModel = $record->event;
        $eventDate = $eventModel->start_time;
        
        // Update summaries
        $this->attendanceService->updateEventSummary($eventModel);
        
        if ($eventModel->ministry_id) {
            $this->attendanceService->updateMinistrySummary(
                $eventModel->ministry_id,
                $eventDate->copy()->startOfDay(),
                $eventDate->copy()->endOfDay()
            );
        }
        
        if ($eventModel->group_id) {
            $this->attendanceService->updateGroupSummary(
                $eventModel->group_id,
                $eventDate->copy()->startOfDay(),
                $eventDate->copy()->endOfDay()
            );
        }
        
        // Update member/family attendance stats
        if ($record->member_id) {
            $this->updateMemberStats($record->member_id);
        }
        
        if ($record->family_id) {
            $this->updateFamilyStats($record->family_id);
        }
    }

    /**
     * Handle attendance updated.
     *
     * @param  \Prasso\Church\Events\AttendanceUpdated  $event
     * @return void
     */
    public function handleAttendanceUpdated(AttendanceUpdated $event)
    {
        $original = $event->original;
        $record = $event->record;
        $eventModel = $record->event;
        $eventDate = $eventModel->start_time;
        
        // If the event or status changed, we need to update summaries
        if (isset($original['event_id']) || isset($original['status'])) {
            // Update summaries for the current event
            $this->attendanceService->updateEventSummary($eventModel);
            
            if ($eventModel->ministry_id) {
                $this->attendanceService->updateMinistrySummary(
                    $eventModel->ministry_id,
                    $eventDate->copy()->startOfDay(),
                    $eventDate->copy()->endOfDay()
                );
            }
            
            if ($eventModel->group_id) {
                $this->attendanceService->updateGroupSummary(
                    $eventModel->group_id,
                    $eventDate->copy()->startOfDay(),
                    $eventDate->copy()->endOfDay()
                );
            }
            
            // If the event changed, also update the old event's summary
            if (isset($original['event_id']) && $original['event_id'] != $eventModel->id) {
                $oldEvent = \Prasso\Church\Models\AttendanceEvent::find($original['event_id']);
                if ($oldEvent) {
                    $this->attendanceService->updateEventSummary($oldEvent);
                }
            }
        }
        
        // Update member/family attendance stats if needed
        if (isset($original['status']) || isset($original['member_id']) || isset($original['family_id'])) {
            if ($record->member_id) {
                $this->updateMemberStats($record->member_id);
            }
            
            if ($record->family_id) {
                $this->updateFamilyStats($record->family_id);
            }
            
            // If member/family changed, update the old member's/family's stats too
            if (isset($original['member_id']) && $original['member_id'] != $record->member_id) {
                $this->updateMemberStats($original['member_id']);
            }
            
            if (isset($original['family_id']) && $original['family_id'] != $record->family_id) {
                $this->updateFamilyStats($original['family_id']);
            }
        }
    }

    /**
     * Handle attendance deleted.
     *
     * @param  \Prasso\Church\Events\AttendanceDeleted  $event
     * @return void
     */
    public function handleAttendanceDeleted(AttendanceDeleted $event)
    {
        $record = $event->record;
        $eventModel = $record->event;
        $eventDate = $eventModel->start_time;
        
        // Update summaries
        $this->attendanceService->updateEventSummary($eventModel);
        
        if ($eventModel->ministry_id) {
            $this->attendanceService->updateMinistrySummary(
                $eventModel->ministry_id,
                $eventDate->copy()->startOfDay(),
                $eventDate->copy()->endOfDay()
            );
        }
        
        if ($eventModel->group_id) {
            $this->attendanceService->updateGroupSummary(
                $eventModel->group_id,
                $eventDate->copy()->startOfDay(),
                $eventDate->copy()->endOfDay()
            );
        }
        
        // Update member/family attendance stats
        if ($record->member_id) {
            $this->updateMemberStats($record->member_id);
        }
        
        if ($record->family_id) {
            $this->updateFamilyStats($record->family_id);
        }
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $method = 'handle' . class_basename($event);
        
        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  object  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed($event, $exception)
    {
        $eventClass = get_class($event);
        $eventId = $event->event->id ?? $event->record->id ?? 'unknown';
        
        // Log the failure
        Log::error("Failed to update attendance summaries for {$eventClass}", [
            'event_id' => $eventId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
