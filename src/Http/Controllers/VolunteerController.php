<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Illuminate\Support\Facades\DB;

class VolunteerController extends Controller
{
    /**
     * Display a listing of volunteer positions.
     */
    public function indexPositions(Request $request)
    {
        $query = VolunteerPosition::with(['ministry', 'group']);

        // Filter by ministry
        if ($request->has('ministry_id')) {
            $query->where('ministry_id', $request->ministry_id);
        }

        // Filter by group
        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Paginate results
        return $query->paginate($request->input('per_page', 15));
    }

    /**
     * Store a newly created volunteer position in storage.
     */
    public function storePosition(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_groups,id',
            'skills_required' => 'nullable|array',
            'time_commitment' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'max_volunteers' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        return DB::transaction(function () use ($validated) {
            $position = VolunteerPosition::create($validated);
            return response()->json($position->load(['ministry', 'group']), 201);
        });
    }

    /**
     * Display the specified volunteer position.
     */
    public function showPosition(VolunteerPosition $position)
    {
        return $position->load(['ministry', 'group', 'volunteers.member']);
    }

    /**
     * Update the specified volunteer position in storage.
     */
    public function updatePosition(Request $request, VolunteerPosition $position)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_groups,id',
            'skills_required' => 'nullable|array',
            'time_commitment' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'max_volunteers' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $position->update($validated);
        return $position->load(['ministry', 'group']);
    }

    /**
     * Remove the specified volunteer position from storage.
     */
    public function destroyPosition(VolunteerPosition $position)
    {
        $position->delete();
        return response()->json(null, 204);
    }

    /**
     * Assign a member to a volunteer position.
     */
    public function assignMember(Request $request, VolunteerPosition $position)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:chm_members,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        // Check if position is open for new volunteers
        if (!$position->isOpen()) {
            return response()->json(['message' => 'This position is not currently open for new volunteers'], 422);
        }

        // Check if member is already assigned to this position
        $existingAssignment = VolunteerAssignment::where('member_id', $validated['member_id'])
            ->where('position_id', $position->id)
            ->where(function ($query) use ($validated) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where('status', 'active')
            ->exists();

        if ($existingAssignment) {
            return response()->json(['message' => 'Member is already assigned to this position'], 422);
        }

        $assignment = $position->volunteers()->create([
            'member_id' => $validated['member_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => auth('sanctum')->id(),
            'approved_by' => auth('sanctum')->id(),
        ]);

        return response()->json($assignment->load('member'), 201);
    }

    /**
     * Remove a member from a volunteer position.
     */
    public function unassignMember(VolunteerPosition $position, Member $member)
    {
        $assignment = $position->volunteers()
            ->where('member_id', $member->id)
            ->where('status', 'active')
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'No active assignment found for this member and position'], 404);
        }

        $assignment->update([
            'status' => 'inactive',
            'end_date' => now(),
        ]);

        return response()->json(['message' => 'Member unassigned from position'], 200);
    }

    /**
     * Get all volunteer assignments.
     */
    public function indexAssignments(Request $request)
    {
        $query = VolunteerAssignment::with(['position', 'member']);

        // Filter by member
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        // Filter by position
        if ($request->has('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where(function($q) use ($request) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '<=', $request->end_date);
            });
        }

        // Paginate results
        return $query->paginate($request->input('per_page', 15));
    }

    /**
     * Update a volunteer assignment.
     */
    public function updateAssignment(Request $request, VolunteerAssignment $assignment)
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|string|in:active,inactive,pending,completed',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'trained_on' => 'nullable|date',
        ]);

        $assignment->update($validated);
        return $assignment->load(['position', 'member']);
    }
}
