<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Prasso\Church\Models\Member;

class CleaningSignupController extends Controller
{
    /**
     * Display the cleaning signup form.
     */
    public function show()
    {
        // Get or create the "Clean the Church" volunteer position
        $position = VolunteerPosition::where('title', 'Clean the Church')->first();
        
        if (!$position) {
            return view('church::cleaning-signup', [
                'error' => 'The cleaning volunteer position is not available. Please contact the church office.'
            ]);
        }

        // Get user data if authenticated
        $user = auth()->user();
        $userData = null;
        
        if ($user) {
            $userData = [
                'name' => $user->name ?? '',
                'phone' => $user->phone ?? '',
                'email' => $user->email ?? '',
            ];
        }

        return view('church::cleaning-signup', [
            'position' => $position,
            'userData' => $userData,
            'isAuthenticated' => (bool) $user,
        ]);
    }

    /**
     * Store a cleaning signup submission as a volunteer assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'selected_week' => 'required|integer|min:1|max:52',
            'template' => 'required|string|in:church_cleaning_signup',
            'data_key' => 'required|string',
        ]);

        try {
            // Get the "Clean the Church" volunteer position
            $position = VolunteerPosition::where('title', 'Clean the Church')->first();
            
            if (!$position) {
                throw new \Exception('Cleaning volunteer position not found');
            }

            // Check if position is still open
            if (!$position->isOpen()) {
                if ($request->expectsJson() || $request->input('return_json')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The cleaning volunteer position is no longer available.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'The cleaning volunteer position is no longer available.');
            }

            // Create a volunteer assignment with week preference in metadata
            $assignment = DB::transaction(function () use ($position, $validated) {
                return VolunteerAssignment::create([
                    'position_id' => $position->id,
                    'member_id' => null, // Guest signup (no member account)
                    'status' => 'pending', // Requires admin approval
                    'notes' => "Guest signup via cleaning form",
                    'metadata' => [
                        'guest_name' => $validated['name'],
                        'guest_phone' => $validated['phone'],
                        'preferred_week' => $validated['selected_week'],
                        'data_key' => $validated['data_key'],
                        'signup_date' => now()->toDateTimeString(),
                        'ip_address' => request()->ip(),
                    ],
                ]);
            });

            // TODO: Send SMS reminder to guest_phone via Prasso Messaging
            // TODO: Send email notification to admin

            if ($request->expectsJson() || $request->input('return_json')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for signing up for church cleaning!',
                    'assignment_id' => $assignment->id,
                ], 201);
            }

            return redirect()->back()->with('success', 'Thank you for signing up for church cleaning!');
        } catch (\Exception $e) {
            Log::error('Cleaning signup error: ' . $e->getMessage());

            if ($request->expectsJson() || $request->input('return_json')) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your signup.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while processing your signup.');
        }
    }

    /**
     * Get current cleaning schedule with availability based on volunteer assignments.
     */
    public function getSchedule()
    {
        // Get the "Clean the Church" position
        $position = VolunteerPosition::where('title', 'Clean the Church')->first();
        
        if (!$position) {
            return response()->json([
                'error' => 'Cleaning position not found',
            ], 404);
        }

        // Get all active assignments for this position grouped by preferred week
        $assignments = VolunteerAssignment::where('position_id', $position->id)
            ->where('status', 'active')
            ->get();

        // Count assignments by preferred week
        $weekCounts = [];
        foreach ($assignments as $assignment) {
            $week = $assignment->metadata['preferred_week'] ?? null;
            if ($week) {
                $weekCounts[$week] = ($weekCounts[$week] ?? 0) + 1;
            }
        }

        // Build response for all 52 weeks
        $weeks = [];
        for ($i = 1; $i <= 52; $i++) {
            $count = $weekCounts[$i] ?? 0;
            // A week is "taken" if it has reached max volunteers (or 1 if no max set)
            $maxPerWeek = $position->max_volunteers ?? 1;
            $weeks[] = [
                'weekNumber' => $i,
                'taken' => $count >= $maxPerWeek,
                'count' => $count,
                'maxVolunteers' => $maxPerWeek,
            ];
        }

        return response()->json($weeks);
    }
}
