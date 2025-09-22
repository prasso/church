<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\Member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrayerRequestController extends Controller
{
    /**
     * Display a listing of the prayer requests.
     */
    public function index(Request $request)
    {
        $query = PrayerRequest::query();
        $user = $request->user();
        $member = $user->member ?? null;

        // Filter by member if requested
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        
        // Filter by requester if requested
        if ($request->has('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by visibility (public/private)
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }
        
        // Filter by group if provided
        if ($request->has('group_id')) {
            $group = Group::findOrFail($request->group_id);
            $query->whereHas('prayerGroups', function($q) use ($group) {
                $q->where('groups.id', $group->id);
            });
        }
        
        // For non-admin users, only show public requests or those they have access to
        if (!$user->hasRole('admin')) {
            $query->where(function($q) use ($member) {
                $q->where('is_public', true)
                  ->orWhere('requested_by', $member?->id)
                  ->orWhere('member_id', $member?->id);
            });
        }
        
        // Paginate results
        return $query->with(['member', 'requestedBy'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($request->input('per_page', 15));
    }

    /**
     * Store a newly created prayer request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'member_id' => 'nullable|exists:chm_members,id',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:chm_groups,id',
        ]);
        
        $user = $request->user();
        $member = $user->member;
        
        // If no member_id is provided, default to the authenticated user's member record
        if (empty($validated['member_id'])) {
            $validated['member_id'] = $member->id;
        }
        
        // Set requested_by to the authenticated user
        $validated['requested_by'] = $member->id;
        
        // Create the prayer request
        $prayerRequest = PrayerRequest::create($validated);
        
        // Attach to groups if provided
        if (!empty($validated['group_ids'])) {
            $prayerRequest->prayerGroups()->sync($validated['group_ids']);
        }
        
        return response()->json($prayerRequest->load('prayerGroups'), 201);
    }

    /**
     * Display the specified prayer request.
     */
    public function show(PrayerRequest $prayerRequest)
    {
        $this->authorize('view', $prayerRequest);
        return $prayerRequest->load(['member', 'requestedBy', 'prayerGroups']);
    }

    /**
     * Update the specified prayer request.
     */
    public function update(Request $request, PrayerRequest $prayerRequest)
    {
        $this->authorize('update', $prayerRequest);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
            'status' => 'sometimes|in:active,answered,inactive',
            'answer' => 'nullable|string',
            'group_ids' => 'sometimes|array',
            'group_ids.*' => 'exists:chm_groups,id',
        ]);
        
        // If marking as answered, set the answered_at timestamp
        if (isset($validated['status']) && $validated['status'] === 'answered' && !$prayerRequest->answered_at) {
            $validated['answered_at'] = now();
        }
        
        $prayerRequest->update($validated);
        
        // Sync groups if provided
        if (isset($validated['group_ids'])) {
            $prayerRequest->prayerGroups()->sync($validated['group_ids']);
        }
        
        return $prayerRequest->fresh(['member', 'requestedBy', 'prayerGroups']);
    }

    /**
     * Remove the specified prayer request.
     */
    public function destroy(PrayerRequest $prayerRequest)
    {
        $this->authorize('delete', $prayerRequest);
        
        $prayerRequest->delete();
        
        return response()->json(['message' => 'Prayer request deleted successfully']);
    }
    
    /**
     * Increment the prayer count for a prayer request.
     */
    public function incrementPrayerCount(PrayerRequest $prayerRequest)
    {
        $prayerRequest->incrementPrayerCount();
        
        return response()->json([
            'message' => 'Prayer count incremented',
            'prayer_count' => $prayerRequest->prayer_count
        ]);
    }
    
    /**
     * Get prayer requests for a specific group.
     */
    public function groupPrayerRequests(Group $group)
    {
        $this->authorize('viewAny', [PrayerRequest::class, $group]);
        
        return $group->prayerRequests()
                    ->with(['member', 'requestedBy'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(request()->input('per_page', 15));
    }
}
