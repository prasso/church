<?php

namespace Prasso\Church\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Prasso\Church\Models\Event;
use Prasso\Church\Models\EventOccurrence;
use Prasso\Church\Models\Attendance;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Ministry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
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
     * Get all events.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'sometimes|string|in:service,meeting,event',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|string|in:upcoming,past,all',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Event::with(['occurrences', 'ministry']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $startDate = $request->start_date;
            $endDate = $request->input('end_date', $startDate);
            
            $query->where(function($q) use ($startDate, $endDate) {
                $q->where('end_date', '>=', $startDate)
                  ->orWhereNull('end_date');
            })->where('start_date', '<=', $endDate);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $now = now()->toDateString();
            
            if ($request->status === 'upcoming') {
                $query->where('start_date', '>=', $now);
            } else if ($request->status === 'past') {
                $query->where('start_date', '<', $now);
            }
        }

        $events = $query->orderBy('start_date')
                       ->orderBy('start_time')
                       ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $events->items(),
            'meta' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:service,meeting,event',
            'location' => 'nullable|string|max:255',
            'image_url' => 'nullable|url',
            'start_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable|date_format:H:i',
            'recurrence_pattern' => 'required|in:none,daily,weekly,monthly,yearly',
            'recurrence_days' => 'required_if:recurrence_pattern,weekly|array',
            'recurrence_days.*' => 'integer|min:0|max:6', // 0-6 for Sunday-Saturday
            'recurrence_interval' => 'required|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean',
            'registration_deadline' => 'nullable|date',
            'ministry_id' => 'nullable|exists:aph_ministries,id',
            'metadata' => 'nullable|array',
        ]);

        $validated['created_by'] = $request->user()->id;
        
        // Set default values
        $validated['status'] = 'published';
        
        // Handle recurrence days for non-weekly patterns
        if ($validated['recurrence_pattern'] !== 'weekly') {
            $validated['recurrence_days'] = null;
        }
        
        // Create the event
        $event = Event::create($validated);
        
        // Generate occurrences for recurring events
        if ($event->isRecurring()) {
            $endDate = $validated['end_date'] ?? now()->addYear();
            $event->generateOccurrences($endDate);
        } else {
            // Create a single occurrence for non-recurring events
            $event->occurrences()->create([
                'date' => $event->start_date,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'location_override' => $event->location,
                'status' => 'scheduled',
            ]);
        }

        return response()->json([
            'message' => 'Event created successfully',
            'data' => $event->load(['occurrences', 'ministry']),
        ], 201);
    }

    /**
     * Get a specific event.
     *
     * @param  \Prasso\Church\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Event $event)
    {
        return response()->json([
            'data' => $event->load(['occurrences', 'ministry', 'creator']),
        ]);
    }

    /**
     * Update an event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|in:service,meeting,event',
            'location' => 'nullable|string|max:255',
            'image_url' => 'nullable|url',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable|date_format:H:i',
            'recurrence_pattern' => 'sometimes|in:none,daily,weekly,monthly,yearly',
            'recurrence_days' => 'required_if:recurrence_pattern,weekly|array',
            'recurrence_days.*' => 'integer|min:0|max:6',
            'recurrence_interval' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:draft,published,cancelled,completed',
            'capacity' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean',
            'registration_deadline' => 'nullable|date',
            'ministry_id' => 'nullable|exists:aph_ministries,id',
            'metadata' => 'nullable|array',
            'update_future_occurrences' => 'boolean', // Whether to update future occurrences
        ]);

        // Handle recurrence days for non-weekly patterns
        if (isset($validated['recurrence_pattern']) && $validated['recurrence_pattern'] !== 'weekly') {
            $validated['recurrence_days'] = null;
        }
        
        $updateFuture = $request->boolean('update_future_occurrences', false);
        
        // If updating future occurrences, we need to handle this specially
        if ($updateFuture && $event->isRecurring()) {
            // Get the current occurrence being edited
            $currentOccurrence = $event->occurrences()
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->first();
                
            if ($currentOccurrence) {
                // Update the event with the new data
                $event->update($validated);
                
                // Regenerate occurrences from the current date forward
                $event->generateOccurrences($validated['end_date'] ?? now()->addYear());
                
                return response()->json([
                    'message' => 'Event and future occurrences updated successfully',
                    'data' => $event->fresh(['occurrences', 'ministry']),
                ]);
            }
        }
        
        // For non-recurring events or when not updating future occurrences
        $event->update($validated);
        
        // If this is a recurring event and we updated the recurrence pattern,
        // regenerate all future occurrences
        if ($event->isRecurring() && $request->has('recurrence_pattern')) {
            $event->generateOccurrences($validated['end_date'] ?? now()->addYear());
        }

        return response()->json([
            'message' => 'Event updated successfully',
            'data' => $event->fresh(['occurrences', 'ministry']),
        ]);
    }

    /**
     * Delete an event.
     *
     * @param  \Prasso\Church\Models\Event  $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Event $event)
    {
        // Delete all occurrences and their attendances
        $event->occurrences()->each(function($occurrence) {
            $occurrence->attendances()->delete();
            $occurrence->delete();
        });
        
        $event->delete();
        
        return response()->json([
            'message' => 'Event deleted successfully',
        ]);
    }

    /**
     * Get occurrences for an event.
     *
     * @param  \Prasso\Church\Models\Event  $event
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function occurrences(Event $event, Request $request)
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:scheduled,cancelled,completed',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $event->occurrences();
        
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $occurrences = $query->orderBy('date')
                            ->orderBy('start_time')
                            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $occurrences->items(),
            'meta' => [
                'total' => $occurrences->total(),
                'per_page' => $occurrences->perPage(),
                'current_page' => $occurrences->currentPage(),
                'last_page' => $occurrences->lastPage(),
            ],
        ]);
    }

    /**
     * Get attendance for an event occurrence.
     *
     * @param  \Prasso\Church\Models\EventOccurrence  $occurrence
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attendance(EventOccurrence $occurrence, Request $request)
    {
        $request->validate([
            'status' => 'sometimes|in:present,absent,excused',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $occurrence->attendances()->with(['member', 'family', 'recordedBy']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $attendances = $query->orderBy('check_in_time', 'desc')
                            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $attendances->items(),
            'meta' => [
                'total' => $attendances->total(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
            ],
        ]);
    }

    /**
     * Record attendance for an event occurrence.
     *
     * @param  \Prasso\Church\Models\EventOccurrence  $occurrence
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordAttendance(EventOccurrence $occurrence, Request $request)
    {
        $request->validate([
            'member_id' => 'required_without:guest_name|exists:aph_members,id',
            'family_id' => 'nullable|exists:aph_families,id',
            'guest_name' => 'required_without:member_id|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'guest_phone' => 'nullable|string|max:20',
            'status' => 'required|in:present,absent,excused',
            'notes' => 'nullable|string',
        ]);

        // Check if this is a guest or member attendance
        if ($request->has('member_id')) {
            $member = Member::findOrFail($request->member_id);
            
            // Check if the member is already recorded for this occurrence
            $existing = $occurrence->attendances()
                ->where('member_id', $member->id)
                ->first();
                
            if ($existing) {
                return response()->json([
                    'message' => 'Attendance already recorded for this member',
                    'data' => $existing,
                ], 409);
            }
            
            $attendance = $occurrence->recordAttendance($member->id, [
                'status' => $request->status,
                'family_id' => $request->family_id,
                'recorded_by' => $request->user()->id,
                'notes' => $request->notes,
            ]);
        } else {
            $attendance = $occurrence->recordGuestAttendance([
                'guest_name' => $request->guest_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'status' => $request->status,
                'family_id' => $request->family_id,
                'recorded_by' => $request->user()->id,
                'notes' => $request->notes,
            ]);
        }
        
        // Update the attendance count on the occurrence
        $occurrence->update([
            'attendance_count' => $occurrence->attendances()->count(),
        ]);

        return response()->json([
            'message' => 'Attendance recorded successfully',
            'data' => $attendance->load(['member', 'family', 'recordedBy']),
        ], 201);
    }

    /**
     * Get attendance statistics for a date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attendanceStats(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_type' => 'nullable|string|in:service,meeting,event',
            'ministry_id' => 'nullable|exists:aph_ministries,id',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        // Get event occurrences in the date range
        $query = EventOccurrence::with(['event', 'attendances'])
            ->whereBetween('date', [$startDate, $endDate]);
            
        if ($request->has('event_type')) {
            $query->whereHas('event', function($q) use ($request) {
                $q->where('type', $request->event_type);
            });
        }
        
        if ($request->has('ministry_id')) {
            $query->whereHas('event', function($q) use ($request) {
                $q->where('ministry_id', $request->ministry_id);
            });
        }
        
        $occurrences = $query->get();
        
        // Calculate statistics
        $stats = [
            'total_events' => $occurrences->count(),
            'total_attendance' => $occurrences->sum('attendance_count'),
            'average_attendance' => $occurrences->avg('attendance_count') ?? 0,
            'by_event_type' => $occurrences->groupBy('event.type')->map(function($events) {
                $count = $events->count();
                $attendance = $events->sum('attendance_count');
                
                return [
                    'event_count' => $count,
                    'total_attendance' => $attendance,
                    'average_attendance' => $count > 0 ? $attendance / $count : 0,
                ];
            }),
            'by_date' => $occurrences->groupBy('date')->map(function($events) {
                return [
                    'event_count' => $events->count(),
                    'total_attendance' => $events->sum('attendance_count'),
                ];
            }),
        ];
        
        // Add top attendees if requested
        if ($request->boolean('include_top_attendees', false)) {
            $topAttendees = Attendance::select('member_id', DB::raw('count(*) as attendance_count'))
                ->whereHas('occurrence', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })
                ->with('member')
                ->groupBy('member_id')
                ->orderBy('attendance_count', 'desc')
                ->limit(10)
                ->get();
                
            $stats['top_attendees'] = $topAttendees;
        }

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get member attendance history.
     *
     * @param  \Prasso\Church\Models\Member  $member
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function memberAttendance(Member $member, Request $request)
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'event_type' => 'nullable|string|in:service,meeting,event',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $member->attendances()
            ->with(['occurrence.event'])
            ->orderBy('check_in_time', 'desc');
            
        if ($request->has('start_date')) {
            $query->whereHas('occurrence', function($q) use ($request) {
                $q->where('date', '>=', $request->start_date);
            });
        }
        
        if ($request->has('end_date')) {
            $query->whereHas('occurrence', function($q) use ($request) {
                $q->where('date', '<=', $request->end_date);
            });
        }
        
        if ($request->has('event_type')) {
            $query->whereHas('occurrence.event', function($q) use ($request) {
                $q->where('type', $request->event_type);
            });
        }
        
        $attendances = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $attendances->items(),
            'meta' => [
                'total' => $attendances->total(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
            ],
        ]);
    }

    /**
     * Check in a member for an event occurrence.
     *
     * @param  \Prasso\Church\Models\EventOccurrence  $occurrence
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIn(EventOccurrence $occurrence, Request $request)
    {
        $request->validate([
            'member_id' => 'required_without:guest_name|exists:aph_members,id',
            'guest_name' => 'required_without:member_id|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'guest_phone' => 'nullable|string|max:20',
            'family_id' => 'nullable|exists:aph_families,id',
        ]);

        // Check if the event occurrence is in the future
        if ($occurrence->isUpcoming() && !$occurrence->isHappeningNow()) {
            return response()->json([
                'message' => 'Cannot check in for future events',
            ], 400);
        }

        // Check if the event is full
        if ($occurrence->isFull()) {
            return response()->json([
                'message' => 'This event is at capacity',
            ], 400);
        }

        // Handle member check-in
        if ($request->has('member_id')) {
            $member = Member::findOrFail($request->member_id);
            
            // Check if already checked in
            $existing = $occurrence->attendances()
                ->where('member_id', $member->id)
                ->first();
                
            if ($existing) {
                return response()->json([
                    'message' => 'Already checked in',
                    'data' => $existing,
                ], 200);
            }
            
            $attendance = $occurrence->recordAttendance($member->id, [
                'status' => 'present',
                'family_id' => $request->family_id,
                'recorded_by' => $request->user()->id,
                'check_in_time' => now(),
            ]);
        } 
        // Handle guest check-in
        else {
            $attendance = $occurrence->recordGuestAttendance([
                'guest_name' => $request->guest_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'status' => 'present',
                'family_id' => $request->family_id,
                'recorded_by' => $request->user()->id,
                'check_in_time' => now(),
            ]);
        }
        
        // Update the attendance count on the occurrence
        $occurrence->update([
            'attendance_count' => $occurrence->attendances()->count(),
        ]);

        return response()->json([
            'message' => 'Checked in successfully',
            'data' => $attendance->load(['member', 'family', 'recordedBy']),
        ], 201);
    }

    /**
     * Check out a member from an event occurrence.
     *
     * @param  \Prasso\Church\Models\EventOccurrence  $occurrence
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOut(EventOccurrence $occurrence, Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:aph_members,id',
        ]);

        $attendance = $occurrence->attendances()
            ->where('member_id', $request->member_id)
            ->firstOrFail();
            
        if ($attendance->isCheckedOut()) {
            return response()->json([
                'message' => 'Already checked out',
                'data' => $attendance,
            ], 200);
        }
        
        $attendance->checkOut($request->user());

        return response()->json([
            'message' => 'Checked out successfully',
            'data' => $attendance->load(['member', 'family', 'recordedBy']),
        ]);
    }
}
