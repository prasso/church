<?php

namespace Prasso\Church\Http\Controllers\Api;

use Prasso\Church\Http\Controllers\Controller;
use Prasso\Church\Models\PastoralVisit;
use Prasso\Church\Http\Resources\PastoralVisitResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PastoralVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        
        $query = PastoralVisit::with(['member', 'family', 'assignedTo']);
        
        // For non-staff, only show visits they're involved with
        if (!$isStaff) {
            $query->where(function($q) use ($user) {
                $q->where('member_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
                  
                // If user has a family, include family visits
                if ($user->family_id) {
                    $q->orWhere('family_id', $user->family_id);
                }
            });
        }
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by assigned staff member
        if ($isStaff && $request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        // Filter by member or family
        if ($isStaff) {
            if ($request->has('member_id')) {
                $query->where('member_id', $request->member_id);
            }
            if ($request->has('family_id')) {
                $query->where('family_id', $request->family_id);
            }
        }
        
        // Date range filters
        if ($request->has('start_date')) {
            $query->where('scheduled_for', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('scheduled_for', '<=', $request->end_date . ' 23:59:59');
        }
        
        // Order by scheduled date
        $visits = $query->orderBy('scheduled_for', 'desc')
                       ->paginate($request->per_page ?? 15);
        
        return PastoralVisitResource::collection($visits);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'purpose' => 'required|string',
            'scheduled_for' => 'required|date',
            'location_type' => 'required|in:home,hospital,church,other',
            'location_details' => 'nullable|string|max:255',
            'member_id' => [
                'nullable',
                'exists:chm_members,id',
                function ($attribute, $value, $fail) use ($user, $isStaff) {
                    // Only staff can create visits for other members
                    if (!$isStaff && $value && $value != $user->id) {
                        $fail('You can only request visits for yourself.');
                    }
                },
            ],
            'family_id' => [
                'nullable',
                'exists:chm_families,id',
                function ($attribute, $value, $fail) use ($user, $isStaff) {
                    // Only staff or family members can create family visits
                    if (!$isStaff && $value && $value != $user->family_id) {
                        $fail('You can only request visits for your own family.');
                    }
                },
            ],
            'assigned_to' => [
                'nullable',
                'exists:chm_members,id',
                function ($attribute, $value, $fail) use ($isStaff) {
                    // Only staff can assign visits
                    if (!$isStaff && $value) {
                        $fail('You cannot assign visits to staff members.');
                    }
                },
            ],
            'is_confidential' => 'sometimes|boolean',
            'spiritual_needs' => 'sometimes|array',
            'spiritual_needs.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Set default values
        $data['status'] = 'scheduled';
        
        // If member_id not provided and family_id is not set, default to current user
        if (!isset($data['member_id']) && !isset($data['family_id'])) {
            $data['member_id'] = $user->id;
        }
        
        // If assigned_to not provided and user is staff, assign to self
        if ($isStaff && !isset($data['assigned_to'])) {
            $data['assigned_to'] = $user->id;
        }
        
        $visit = PastoralVisit::create($data);
        
        // Trigger notification to assigned staff member if different from creator
        if ($visit->assigned_to && $visit->assigned_to != $user->id) {
            event(new \Prasso\Church\Events\PastoralVisitAssigned($visit));
        }
        
        return new PastoralVisitResource($visit->load(['member', 'family', 'assignedTo']));
    }

    /**
     * Display the specified resource.
     *
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, PastoralVisit $visit)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        $isAssigned = $visit->assigned_to === $user->id;
        $isRelated = $visit->member_id === $user->id || 
                    ($visit->family_id && $visit->family_id === $user->family_id);
        
        if (!$isStaff && !$isAssigned && !$isRelated) {
            return response()->json([
                'message' => 'You do not have permission to view this visit.'
            ], 403);
        }
        
        // For non-staff, don't show confidential visits unless they're the assigned staff
        if ($visit->is_confidential && !$isStaff && !$isAssigned) {
            return response()->json([
                'message' => 'This is a confidential visit and you are not authorized to view it.'
            ], 403);
        }
        
        return new PastoralVisitResource($visit->load(['member', 'family', 'assignedTo']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PastoralVisit $visit)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        $isAssigned = $visit->assigned_to === $user->id;
        
        // Only staff or assigned staff can update the visit
        if (!$isStaff && !$isAssigned) {
            return response()->json([
                'message' => 'You do not have permission to update this visit.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'purpose' => 'sometimes|required|string',
            'scheduled_for' => 'sometimes|required|date',
            'started_at' => 'sometimes|nullable|date',
            'ended_at' => 'sometimes|nullable|date|after_or_equal:started_at',
            'duration_minutes' => 'sometimes|nullable|integer|min:0',
            'location_type' => 'sometimes|required|in:home,hospital,church,other',
            'location_details' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|required|in:scheduled,in_progress,completed,canceled',
            'notes' => 'sometimes|nullable|string',
            'follow_up_actions' => 'sometimes|nullable|string',
            'follow_up_date' => 'sometimes|nullable|date',
            'spiritual_needs' => 'sometimes|nullable|array',
            'spiritual_needs.*' => 'string',
            'outcome_summary' => 'sometimes|nullable|string',
            'is_confidential' => 'sometimes|boolean',
            'assigned_to' => [
                'sometimes',
                'nullable',
                'exists:chm_members,id',
                function ($attribute, $value, $fail) use ($isStaff) {
                    // Only staff can assign visits
                    if (!$isStaff && $value) {
                        $fail('You cannot assign visits to staff members.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Handle status changes
        if (isset($data['status'])) {
            switch ($data['status']) {
                case 'in_progress':
                    $data['started_at'] = $data['started_at'] ?? now();
                    break;
                    
                case 'completed':
                    $data['ended_at'] = $data['ended_at'] ?? now();
                    if ($visit->started_at && $data['ended_at']) {
                        $data['duration_minutes'] = $visit->started_at->diffInMinutes($data['ended_at']);
                    }
                    break;
            }
        }
        
        // Only staff can change certain fields
        if (!$isStaff) {
            unset($data['assigned_to'], $data['is_confidential']);
        }
        
        $originalAssignedTo = $visit->assigned_to;
        $visit->update($data);
        
        // Trigger notifications if assignment changed
        if (isset($data['assigned_to']) && $data['assigned_to'] != $originalAssignedTo) {
            event(new \Prasso\Church\Events\PastoralVisitAssigned($visit->fresh()));
        }
        
        return new PastoralVisitResource($visit->fresh(['member', 'family', 'assignedTo']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return \Illuminate\Http\Response
     */
    public function destroy(PastoralVisit $visit)
    {
        $user = request()->user();
        
        // Only staff can delete visits
        if (!$user->isStaff()) {
            return response()->json([
                'message' => 'You do not have permission to delete this visit.'
            ], 403);
        }
        
        $visit->delete();
        
        return response()->json(['message' => 'Visit deleted successfully']);
    }
    
    /**
     * Mark a visit as started.
     *
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return \Illuminate\Http\Response
     */
    public function start(PastoralVisit $visit)
    {
        $user = request()->user();
        $isStaff = $user->isStaff();
        
        // Only assigned staff or admin can start a visit
        if (!$isStaff || ($visit->assigned_to && $visit->assigned_to != $user->id)) {
            return response()->json([
                'message' => 'You are not authorized to start this visit.'
            ], 403);
        }
        
        if ($visit->status !== 'scheduled') {
            return response()->json([
                'message' => 'Only scheduled visits can be started.'
            ], 422);
        }
        
        $visit->markAsStarted();
        
        return new PastoralVisitResource($visit->fresh(['member', 'family', 'assignedTo']));
    }
    
    /**
     * Mark a visit as completed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return \Illuminate\Http\Response
     */
    public function complete(Request $request, PastoralVisit $visit)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        
        // Only assigned staff or admin can complete a visit
        if (!$isStaff || ($visit->assigned_to && $visit->assigned_to != $user->id)) {
            return response()->json([
                'message' => 'You are not authorized to complete this visit.'
            ], 403);
        }
        
        if (!in_array($visit->status, ['scheduled', 'in_progress'])) {
            return response()->json([
                'message' => 'Only scheduled or in-progress visits can be completed.'
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'notes' => 'sometimes|nullable|string',
            'outcome_summary' => 'sometimes|nullable|string',
            'follow_up_actions' => 'sometimes|nullable|string',
            'follow_up_date' => 'sometimes|nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $data = $validator->validated();
        
        $visit->markAsCompleted(
            $data['notes'] ?? $visit->notes,
            $data['outcome_summary'] ?? $visit->outcome_summary
        );
        
        // Update follow-up information if provided
        if (isset($data['follow_up_actions'])) {
            $visit->follow_up_actions = $data['follow_up_actions'];
        }
        
        if (isset($data['follow_up_date'])) {
            $visit->follow_up_date = $data['follow_up_date'];
        }
        
        $visit->save();
        
        // Trigger notifications if needed
        event(new \Prasso\Church\Events\PastoralVisitCompleted($visit->fresh()));
        
        return new PastoralVisitResource($visit->fresh(['member', 'family', 'assignedTo']));
    }
    
    /**
     * Get the calendar events for pastoral visits.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function calendar(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        
        $query = PastoralVisit::query();
        
        // For non-staff, only show their own visits
        if (!$isStaff) {
            $query->where(function($q) use ($user) {
                $q->where('member_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
                  
                if ($user->family_id) {
                    $q->orWhere('family_id', $user->family_id);
                }
            });
        }
        
        // Apply date range filters
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->addMonth()->endOfMonth()->toDateString());
        
        $query->whereBetween('scheduled_for', [$start, $end])
              ->orderBy('scheduled_for');
        
        // Filter by assigned staff
        if ($isStaff && $request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        $visits = $query->get();
        
        // Format for fullcalendar
        $events = $visits->map(function ($visit) use ($user) {
            $isAssigned = $visit->assigned_to === $user->id;
            $isRelated = $visit->member_id === $user->id || 
                        ($visit->family_id && $visit->family_id === $user->family_id);
            
            // Determine if the event is editable by the current user
            $editable = $user->isStaff() || $isAssigned;
            
            // Determine the event color based on status
            $colors = [
                'scheduled' => '#3b82f6', // blue
                'in_progress' => '#f59e0b', // amber
                'completed' => '#10b981', // emerald
                'canceled' => '#6b7280', // gray
            ];
            
            return [
                'id' => $visit->id,
                'title' => $visit->title,
                'start' => $visit->scheduled_for->toIso8601String(),
                'end' => $visit->scheduled_for->addHour()->toIso8601String(),
                'allDay' => false,
                'color' => $colors[$visit->status] ?? '#3b82f6',
                'editable' => $editable,
                'status' => $visit->status,
                'location' => $visit->location_type,
                'extendedProps' => [
                    'description' => $visit->purpose,
                    'location' => $visit->location_details,
                    'assigned_to' => $visit->assignedTo ? [
                        'id' => $visit->assignedTo->id,
                        'name' => $visit->assignedTo->full_name,
                    ] : null,
                    'member' => $visit->member ? [
                        'id' => $visit->member->id,
                        'name' => $visit->member->full_name,
                    ] : null,
                    'family' => $visit->family ? [
                        'id' => $visit->family->id,
                        'name' => $visit->family->name,
                    ] : null,
                ],
            ];
        });
        
        return response()->json($events);
    }
}
