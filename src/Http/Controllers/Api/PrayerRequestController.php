<?php

namespace Prasso\Church\Http\Controllers\Api;

use Prasso\Church\Http\Controllers\Controller;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Http\Resources\PrayerRequestResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrayerRequestController extends Controller
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
        
        $query = PrayerRequest::with(['member', 'requestedBy', 'prayerGroups']);
        
        // For non-staff, only show public requests or those they created/are about them
        if (!$isStaff) {
            $query->where(function($q) use ($user) {
                $q->where('is_public', true)
                  ->orWhere('member_id', $user->id)
                  ->orWhere('requested_by', $user->id);
            });
        }
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by member if staff member is filtering
        if ($isStaff && $request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        
        // Order by most recent first
        $prayerRequests = $query->latest()->paginate($request->per_page ?? 15);
        
        return PrayerRequestResource::collection($prayerRequests);
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
            'description' => 'required|string',
            'is_anonymous' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'member_id' => [
                'nullable',
                'exists:chm_members,id',
                function ($attribute, $value, $fail) use ($user, $isStaff) {
                    // Only staff can create prayer requests for other members
                    if (!$isStaff && $value && $value != $user->id) {
                        $fail('You can only create prayer requests for yourself.');
                    }
                },
            ],
            'prayer_group_ids' => 'sometimes|array',
            'prayer_group_ids.*' => 'exists:chm_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $data = $validator->validated();
        $data['requested_by'] = $user->id;
        
        // If member_id not provided, default to the current user
        if (!isset($data['member_id'])) {
            $data['member_id'] = $user->id;
        }
        
        $prayerRequest = PrayerRequest::create($data);
        
        // Attach to prayer groups if provided
        if (isset($data['prayer_group_ids'])) {
            $prayerRequest->prayerGroups()->sync($data['prayer_group_ids']);
        }
        
        // Trigger notification to staff
        event(new \Prasso\Church\Events\PrayerRequestCreated($prayerRequest));
        
        return new PrayerRequestResource($prayerRequest->load(['member', 'requestedBy', 'prayerGroups']));
    }

    /**
     * Display the specified resource.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, PrayerRequest $prayerRequest)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        $isOwner = $prayerRequest->member_id === $user->id || 
                  $prayerRequest->requested_by === $user->id;
        
        if (!$isStaff && !$isOwner && !$prayerRequest->is_public) {
            return response()->json([
                'message' => 'You do not have permission to view this prayer request.'
            ], 403);
        }
        
        return new PrayerRequestResource($prayerRequest->load(['member', 'requestedBy', 'prayerGroups']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PrayerRequest $prayerRequest)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        $isOwner = $prayerRequest->member_id === $user->id || 
                  $prayerRequest->requested_by === $user->id;
        
        if (!$isStaff && !$isOwner) {
            return response()->json([
                'message' => 'You do not have permission to update this prayer request.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'is_anonymous' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,answered,inactive',
            'answer' => 'required_if:status,answered|nullable|string',
            'prayer_group_ids' => 'sometimes|array',
            'prayer_group_ids.*' => 'exists:chm_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Only staff can change certain fields
        if (!$isStaff) {
            unset($data['status'], $data['answer']);
        } elseif (isset($data['status']) && $data['status'] === 'answered') {
            $data['answered_at'] = now();
        }
        
        $prayerRequest->update($data);
        
        // Sync prayer groups if provided
        if (isset($data['prayer_group_ids'])) {
            $prayerRequest->prayerGroups()->sync($data['prayer_group_ids']);
        }
        
        return new PrayerRequestResource($prayerRequest->fresh(['member', 'requestedBy', 'prayerGroups']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(PrayerRequest $prayerRequest)
    {
        $user = request()->user();
        $isStaff = $user->isStaff();
        $isOwner = $prayerRequest->member_id === $user->id || 
                  $prayerRequest->requested_by === $user->id;
        
        if (!$isStaff && !$isOwner) {
            return response()->json([
                'message' => 'You do not have permission to delete this prayer request.'
            ], 403);
        }
        
        $prayerRequest->delete();
        
        return response()->json(['message' => 'Prayer request deleted successfully']);
    }
    
    /**
     * Record that a user has prayed for a prayer request.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @return \Illuminate\Http\Response
     */
    public function pray(PrayerRequest $prayerRequest)
    {
        $user = request()->user();
        $isStaff = $user->isStaff();
        $isOwner = $prayerRequest->member_id === $user->id || 
                  $prayerRequest->requested_by === $user->id;
        
        // Only allow praying for public requests or those the user has access to
        if (!$isStaff && !$isOwner && !$prayerRequest->is_public) {
            return response()->json([
                'message' => 'You do not have permission to pray for this request.'
            ], 403);
        }
        
        // Prevent users from praying for their own requests
        if ($isOwner) {
            return response()->json([
                'message' => 'You cannot pray for your own prayer request.'
            ], 422);
        }
        
        // Record the prayer
        $prayerRequest->increment('prayer_count');
        
        // Optionally, you could track who prayed for what
        // $prayerRequest->prayedBy()->syncWithoutDetaching([$user->id]);
        
        return response()->json([
            'message' => 'Prayer recorded. Thank you for praying!',
            'prayer_count' => $prayerRequest->fresh()->prayer_count,
        ]);
    }
}
