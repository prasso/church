<?php

namespace Prasso\Church\Http\Controllers\Api;

use Prasso\Church\Http\Controllers\Controller;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\AttendanceGroup;
use Prasso\Church\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get a listing of attendance events.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function indexEvents(Request $request)
    {
        $query = AttendanceEvent::with(['location', 'eventType', 'ministry', 'group']);
        
        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [
                $request->input('start_date'),
                $request->input('end_date')
            ]);
        }
        
        // Filter by ministry
        if ($request->has('ministry_id')) {
            $query->where('ministry_id', $request->input('ministry_id'));
        }
        
        // Filter by group
        if ($request->has('group_id')) {
            $query->where('group_id', $request->input('group_id'));
        }
        
        // Filter by event type
        if ($request->has('event_type_id')) {
            $query->where('event_type_id', $request->input('event_type_id'));
        }
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $events = $query->orderBy('start_time', 'desc')->paginate($perPage);
        
        return response()->json($events);
    }

    /**
     * Store a newly created attendance event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location_id' => 'nullable|exists:chm_locations,id',
            'event_type_id' => 'required|exists:chm_event_types,id',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_attendance_groups,id',
            'expected_attendance' => 'nullable|integer|min:0',
            'requires_check_in' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'required_if:is_recurring,true|in:daily,weekly,biweekly,monthly',
            'recurrence_details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $event = new AttendanceEvent($request->all());
        $event->created_by = $request->user()->id;
        $event->save();

        // Generate recurring events if needed
        if ($event->is_recurring && $event->recurrence_pattern) {
            $endDate = $request->input('recurrence_end_date') ?: Carbon::parse($event->start_time)->addYear();
            $event->generateRecurringEvents($endDate);
        }

        return response()->json($event->load(['location', 'eventType', 'ministry', 'group']), 201);
    }

    /**
     * Record attendance for an event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $eventId
     * @return \Illuminate\Http\Response
     */
    public function recordAttendance(Request $request, $eventId)
    {
        $event = AttendanceEvent::findOrFail($eventId);
        
        $validator = Validator::make($request->all(), [
            'member_id' => 'nullable|exists:chm_members,id',
            'family_id' => 'nullable|exists:chm_families,id',
            'status' => 'required|string|in:present,late,excused,absent,tardy',
            'guest_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'check_in_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if attendance already recorded for this member/family
        if ($request->has('member_id')) {
            $existing = AttendanceRecord::where('event_id', $eventId)
                ->where('member_id', $request->input('member_id'))
                ->first();
                
            if ($existing) {
                return response()->json([
                    'message' => 'Attendance already recorded for this member',
                    'data' => $existing
                ], 409);
            }
        } elseif ($request->has('family_id')) {
            $existing = AttendanceRecord::where('event_id', $eventId)
                ->where('family_id', $request->input('family_id'))
                ->first();
                
            if ($existing) {
                return response()->json([
                    'message' => 'Attendance already recorded for this family',
                    'data' => $existing
                ], 409);
            }
        }

        $record = new AttendanceRecord([
            'event_id' => $eventId,
            'member_id' => $request->input('member_id'),
            'family_id' => $request->input('family_id'),
            'checked_in_by' => $request->user()->id,
            'check_in_time' => $request->input('check_in_time', now()),
            'status' => $request->input('status', 'present'),
            'guest_count' => $request->input('guest_count', 0),
            'notes' => $request->input('notes'),
            'source' => 'manual',
        ]);
        
        $record->save();

        // Update attendance summary
        $this->updateAttendanceSummary($event);

        return response()->json($record->load(['member', 'family', 'checkedInBy']), 201);
    }

    /**
     * Bulk record attendance for multiple members/families.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $eventId
     * @return \Illuminate\Http\Response
     */
    public function bulkRecordAttendance(Request $request, $eventId)
    {
        $event = AttendanceEvent::findOrFail($eventId);
        
        $validator = Validator::make($request->all(), [
            'attendees' => 'required|array',
            'attendees.*.member_id' => 'nullable|exists:chm_members,id',
            'attendees.*.family_id' => 'nullable|exists:chm_families,id',
            'attendees.*.status' => 'required|string|in:present,late,excused,absent,tardy',
            'attendees.*.guest_count' => 'nullable|integer|min:0',
            'attendees.*.notes' => 'nullable|string',
            'check_in_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $records = [];
        $checkInTime = $request->input('check_in_time', now());
        $userId = $request->user()->id;

        DB::beginTransaction();
        
        try {
            foreach ($request->input('attendees') as $attendee) {
                // Skip if both member_id and family_id are missing
                if (empty($attendee['member_id']) && empty($attendee['family_id'])) {
                    continue;
                }
                
                $recordData = [
                    'event_id' => $eventId,
                    'member_id' => $attendee['member_id'] ?? null,
                    'family_id' => $attendee['family_id'] ?? null,
                    'checked_in_by' => $userId,
                    'check_in_time' => $checkInTime,
                    'status' => $attendee['status'],
                    'guest_count' => $attendee['guest_count'] ?? 0,
                    'notes' => $attendee['notes'] ?? null,
                    'source' => 'bulk_import',
                ];
                
                // Check for existing record
                $existing = AttendanceRecord::where('event_id', $eventId);
                
                if (!empty($attendee['member_id'])) {
                    $existing->where('member_id', $attendee['member_id']);
                } else {
                    $existing->where('family_id', $attendee['family_id']);
                }
                
                $record = $existing->first();
                
                if ($record) {
                    $record->update($recordData);
                } else {
                    $record = AttendanceRecord::create($recordData);
                }
                
                $records[] = $record;
            }
            
            // Update attendance summary
            $this->updateAttendanceSummary($event);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Attendance recorded successfully',
                'data' => $records
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to record attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check out a member/family from an event.
     *
     * @param  int  $recordId
     * @return \Illuminate\Http\Response
     */
    public function checkOut($recordId)
    {
        $record = AttendanceRecord::findOrFail($recordId);
        
        if ($record->check_out_time) {
            return response()->json([
                'message' => 'Already checked out',
                'data' => $record
            ], 400);
        }
        
        $record->check_out_time = now();
        $record->save();
        
        return response()->json([
            'message' => 'Checked out successfully',
            'data' => $record
        ]);
    }

    /**
     * Get attendance statistics for a specific event or date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getStatistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'nullable|exists:chm_attendance_events,id',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_attendance_groups,id',
            'start_date' => 'required_without:event_id|date',
            'end_date' => 'required_with:start_date|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $eventId = $request->input('event_id');
        $ministryId = $request->input('ministry_id');
        $groupId = $request->input('group_id');
        
        // Get or generate the summary
        $summary = AttendanceSummary::generateForDateRange($startDate, $endDate, [
            'event_id' => $eventId,
            'ministry_id' => $ministryId,
            'group_id' => $groupId,
        ]);
        
        // Get trend data
        $trend = AttendanceSummary::getTrend('month', 12, [
            'event_id' => $eventId,
            'ministry_id' => $ministryId,
            'group_id' => $groupId,
        ]);
        
        // Get top attendees
        $topAttendees = AttendanceRecord::select(
                'chm_members.id',
                'chm_members.first_name',
                'chm_members.last_name',
                DB::raw('COUNT(*) as attendance_count')
            )
            ->join('chm_members', 'chm_attendance_records.member_id', '=', 'chm_members.id')
            ->when($eventId, function($query) use ($eventId) {
                return $query->where('event_id', $eventId);
            })
            ->when($ministryId, function($query) use ($ministryId) {
                return $query->whereHas('event', function($q) use ($ministryId) {
                    $q->where('ministry_id', $ministryId);
                });
            })
            ->when($groupId, function($query) use ($groupId) {
                $group = AttendanceGroup::find($groupId);
                if ($group) {
                    $memberIds = $group->getAllMembers()->pluck('id');
                    return $query->whereIn('chm_attendance_records.member_id', $memberIds);
                }
                return $query;
            })
            ->when($startDate && $endDate, function($query) use ($startDate, $endDate) {
                return $query->whereBetween('check_in_time', [$startDate, $endDate]);
            })
            ->groupBy('chm_members.id', 'chm_members.first_name', 'chm_members.last_name')
            ->orderBy('attendance_count', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'summary' => $summary,
            'trend' => $trend,
            'top_attendees' => $topAttendees,
        ]);
    }
    
    /**
     * Update the attendance summary for an event.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    protected function updateAttendanceSummary(AttendanceEvent $event)
    {
        $startOfDay = Carbon::parse($event->start_time)->startOfDay();
        $endOfDay = Carbon::parse($event->start_time)->endOfDay();
        
        AttendanceSummary::generateForDateRange(
            $startOfDay,
            $endOfDay,
            ['event_id' => $event->id]
        );
        
        if ($event->ministry_id) {
            AttendanceSummary::generateForDateRange(
                $startOfDay,
                $endOfDay,
                ['ministry_id' => $event->ministry_id]
            );
        }
        
        if ($event->group_id) {
            AttendanceSummary::generateForDateRange(
                $startOfDay,
                $endOfDay,
                ['group_id' => $event->group_id]
            );
        }
    }
}
