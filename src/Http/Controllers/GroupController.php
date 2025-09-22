<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Ministry;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Display a listing of the groups.
     */
    public function index(Request $request)
    {
        $query = Group::with(['ministry', 'contactPerson']);

        // Filter by ministry
        if ($request->has('ministry_id')) {
            $query->where('ministry_id', $request->ministry_id);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Paginate results
        return $query->paginate($request->input('per_page', 15));
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'contact_person_id' => 'nullable|exists:chm_members,id',
            'meeting_schedule' => 'nullable|string|max:255',
            'meeting_location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_members' => 'nullable|integer|min:1',
            'is_open' => 'boolean',
            'requires_approval' => 'boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $group = Group::create($validated);
            return response()->json($group->load(['ministry', 'contactPerson']), 201);
        });
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        return $group->load(['ministry', 'contactPerson', 'members']);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'contact_person_id' => 'nullable|exists:chm_members,id',
            'meeting_schedule' => 'nullable|string|max:255',
            'meeting_location' => 'nullable|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_members' => 'nullable|integer|min:1',
            'is_open' => 'boolean',
            'requires_approval' => 'boolean',
        ]);

        $group->update($validated);
        return $group->load(['ministry', 'contactPerson']);
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group)
    {
        $group->delete();
        return response()->json(null, 204);
    }

    /**
     * Add a member to the group.
     */
    public function addMember(Request $request, Group $group)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:chm_members,id',
            'role' => 'required|string|in:leader,co-leader,member',
            'notes' => 'nullable|string',
        ]);

        // Check if member is already in the group
        if ($group->members()->where('member_id', $validated['member_id'])->exists()) {
            return response()->json(['message' => 'Member is already in this group'], 422);
        }

        $group->members()->attach($validated['member_id'], [
            'role' => $validated['role'],
            'join_date' => now(),
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Member added to group'], 200);
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Group $group, Member $member)
    {
        $group->members()->detach($member->id);
        return response()->json(['message' => 'Member removed from group'], 200);
    }

    /**
     * Update a member's role in the group.
     */
    public function updateMemberRole(Request $request, Group $group, Member $member)
    {
        $validated = $request->validate([
            'role' => 'required|string|in:leader,co-leader,member',
        ]);

        $group->members()->updateExistingPivot($member->id, [
            'role' => $validated['role'],
        ]);

        return response()->json(['message' => 'Member role updated'], 200);
    }
}
