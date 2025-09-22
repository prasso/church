<?php

namespace Prasso\Church\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\Availability;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    /**
     * Get availability for a member.
     */
    public function index(Request $request, Member $member = null)
    {
        // If no member is provided, use the authenticated user's member record
        if (!$member) {
            $member = $request->user()->member ?? null;
            if (!$member) {
                return response()->json(['message' => 'No member found'], 404);
            }
        }
        
        $query = $member->availabilities();
        
        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);
            
            $query->where(function($q) use ($start, $end) {
                // For one-time availabilities within the date range
                $q->where(function($q) use ($start, $end) {
                    $q->where('recurring', false)
                      ->where('start_time', '>=', $start)
                      ->where('end_time', '<=', $end);
                });
                
                // For recurring availabilities
                if ($request->boolean('include_recurring', true)) {
                    $q->orWhere('recurring', true);
                }
            });
        }
        
        // Filter by day of week (0-6, Sunday-Saturday)
        if ($request->has('day_of_week') && $request->day_of_week !== null) {
            $query->where('day_of_week', $request->day_of_week);
        }
        
        // Filter by recurring status
        if ($request->has('recurring')) {
            $query->where('recurring', $request->boolean('recurring'));
        }
        
        return $query->orderBy('start_time')->get();
    }
    
    /**
     * Store a new availability for a member.
     */
    public function store(Request $request, Member $member = null)
    {
        $member = $member ?? $request->user()->member;
        
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'recurring' => 'boolean',
            'day_of_week' => 'nullable|integer|between:0,6',
            'timezone' => 'nullable|timezone',
            'notes' => 'nullable|string',
        ]);
        
        // Set default values
        $validated['timezone'] = $validated['timezone'] ?? 'UTC';
        $validated['recurring'] = $validated['recurring'] ?? false;
        
        // If recurring, ensure day_of_week is set
        if ($validated['recurring'] && !isset($validated['day_of_week'])) {
            $validated['day_of_week'] = Carbon::parse($validated['start_time'])->dayOfWeek;
        } elseif (!$validated['recurring']) {
            $validated['day_of_week'] = null;
        }
        
        $availability = $member->availabilities()->create($validated);
        
        return response()->json($availability, 201);
    }
    
    /**
     * Update an existing availability.
     */
    public function update(Request $request, Availability $availability)
    {
        $this->authorize('update', $availability);
        
        $validated = $request->validate([
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'recurring' => 'boolean',
            'day_of_week' => 'nullable|integer|between:0,6',
            'timezone' => 'nullable|timezone',
            'notes' => 'nullable|string',
        ]);
        
        // If updating to recurring, ensure day_of_week is set
        if (($request->has('recurring') && $request->recurring) || $availability->recurring) {
            $dayOfWeek = $request->day_of_week ?? $availability->day_of_week;
            
            if ($dayOfWeek === null) {
                $dayOfWeek = Carbon::parse($request->start_time ?? $availability->start_time)->dayOfWeek;
            }
            
            $validated['day_of_week'] = $dayOfWeek;
        } else {
            $validated['day_of_week'] = null;
        }
        
        $availability->update($validated);
        
        return $availability;
    }
    
    /**
     * Delete an availability.
     */
    public function destroy(Availability $availability)
    {
        $this->authorize('delete', $availability);
        
        $availability->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Find available volunteers for a time slot.
     */
    public function findAvailableVolunteers(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'position_id' => 'nullable|exists:chm_volunteer_positions,id',
            'required_skill_ids' => 'sometimes|array',
            'required_skill_ids.*' => 'exists:chm_skills,id',
            'include_recurring' => 'boolean',
        ]);
        
        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $dayOfWeek = $start->dayOfWeek;
        
        // Base query for members with matching availability
        $query = Member::whereHas('availabilities', function($q) use ($start, $end, $dayOfWeek, $validated) {
            // For the specific time slot (one-time availability)
            $q->where(function($q) use ($start, $end) {
                $q->where('recurring', false)
                  ->where('start_time', '<=', $start)
                  ->where('end_time', '>=', $end);
            });
            
            // For recurring availability on the same day of week
            if ($validated['include_recurring'] ?? true) {
                $q->orWhere(function($q) use ($dayOfWeek, $start, $end) {
                    $q->where('recurring', true)
                      ->where('day_of_week', $dayOfWeek)
                      ->whereTime('start_time', '<=', $start->toTimeString())
                      ->whereTime('end_time', '>=', $end->toTimeString());
                });
            }
        });
        
        // Filter by required skills if any
        if (!empty($validated['required_skill_ids'])) {
            $query->whereHas('skills', function($q) use ($validated) {
                $q->whereIn('skill_id', $validated['required_skill_ids']);
            }, '>=', count($validated['required_skill_ids']));
        }
        
        // Filter by position requirements if specified
        if (!empty($validated['position_id'])) {
            $position = VolunteerPosition::findOrFail($validated['position_id']);
            $requiredSkills = $position->skills()->wherePivot('is_required', true)->pluck('skill_id');
            
            if ($requiredSkills->isNotEmpty()) {
                $query->whereHas('skills', function($q) use ($requiredSkills) {
                    $q->whereIn('skill_id', $requiredSkills);
                }, '>=', $requiredSkills->count());
            }
        }
        
        // Eager load relationships
        $query->with(['availabilities' => function($q) use ($start, $end, $dayOfWeek, $validated) {
            $q->where(function($q) use ($start, $end) {
                $q->where('recurring', false)
                  ->where('start_time', '<=', $start)
                  ->where('end_time', '>=', $end);
            });
            
            if ($validated['include_recurring'] ?? true) {
                $q->orWhere(function($q) use ($dayOfWeek, $start, $end) {
                    $q->where('recurring', true)
                      ->where('day_of_week', $dayOfWeek)
                      ->whereTime('start_time', '<=', $start->toTimeString())
                      ->whereTime('end_time', '>=', $end->toTimeString());
                });
            }
        }, 'skills']);
        
        return $query->get();
    }
    
    /**
     * Check member availability for a time slot.
     */
    public function checkMemberAvailability(Request $request, Member $member)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);
        
        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $dayOfWeek = $start->dayOfWeek;
        
        // Check for one-time availability
        $oneTimeAvailable = $member->availabilities()
            ->where('recurring', false)
            ->where('start_time', '<=', $start)
            ->where('end_time', '>=', $end)
            ->exists();
        
        // Check for recurring availability
        $recurringAvailable = $member->availabilities()
            ->where('recurring', true)
            ->where('day_of_week', $dayOfWeek)
            ->whereTime('start_time', '<=', $start->toTimeString())
            ->whereTime('end_time', '>=', $end->toTimeString())
            ->exists();
        
        return response()->json([
            'is_available' => $oneTimeAvailable || $recurringAvailable,
            'one_time_available' => $oneTimeAvailable,
            'recurring_available' => $recurringAvailable,
        ]);
    }
}
