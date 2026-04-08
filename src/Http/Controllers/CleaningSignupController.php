<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Prasso\Church\Models\Member;
use Prasso\Messaging\Models\MsgMessage;
use Prasso\Messaging\Models\MsgDelivery;
use Prasso\Messaging\Models\MsgGuest;

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
        $rules = [
            'name' => 'required|string|max:255',
            'reminder_type' => 'required|string|in:sms,email,both',
            'selected_week' => 'required|integer|min:1|max:52',
            'template' => 'required|string|in:church_cleaning_signup',
            'data_key' => 'required|string',
        ];

        // Phone is required if reminder type includes SMS
        if (in_array($request->input('reminder_type'), ['sms', 'both'])) {
            $rules['phone'] = 'required|string|max:20';
        } else {
            $rules['phone'] = 'nullable|string|max:20';
        }

        // Email is required if reminder type includes email
        if (in_array($request->input('reminder_type'), ['email', 'both'])) {
            $rules['email'] = 'required|email|max:255';
        } else {
            $rules['email'] = 'nullable|email|max:255';
        }

        $validated = $request->validate($rules);

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

            // Determine member_id: use authenticated user's member record if available
            $memberId = null;
            $signupType = 'guest';
            if (auth()->check()) {
                $member = Member::where('user_id', auth()->id())->first();
                if ($member) {
                    $memberId = $member->id;
                    $signupType = 'member';
                }
            }

            // Calculate start date from selected week number
            $weekNumber = $validated['selected_week'];
            $year = now()->year;
            $startDate = $this->getDateOfWeek($year, $weekNumber);

            // Create a volunteer assignment with week preference in metadata
            $assignment = DB::transaction(function () use ($position, $validated, $memberId, $signupType, $startDate) {
                return VolunteerAssignment::create([
                    'position_id' => $position->id,
                    'member_id' => $memberId,
                    'start_date' => $startDate,
                    'status' => 'pending', // Requires admin approval
                    'notes' => "{$signupType} signup via cleaning form",
                    'metadata' => [
                        'guest_name' => $validated['name'],
                        'guest_phone' => $validated['phone'],
                        'guest_email' => $validated['email'],
                        'reminder_type' => $validated['reminder_type'],
                        'preferred_week' => $validated['selected_week'],
                        'data_key' => $validated['data_key'],
                        'signup_date' => now()->toDateTimeString(),
                        'ip_address' => request()->ip(),
                        'signup_type' => $signupType,
                    ],
                ]);
            });

            $this->sendReminders($assignment, $validated);

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

        // Get all pending and active assignments for this position grouped by preferred week
        $assignments = VolunteerAssignment::where('position_id', $position->id)
            ->whereIn('status', ['pending', 'active'])
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

    /**
     * Send reminder notifications based on user preference.
     */
    private function sendReminders(VolunteerAssignment $assignment, array $validated)
    {
        $reminderType = $validated['reminder_type'];
        $guestName = $validated['name'];
        $guestPhone = $validated['phone'];
        $guestEmail = $validated['email'];
        $week = $validated['selected_week'];

        try {
            // Get the site from the request
            $site = $this->getSiteFromRequest();
            if (!$site) {
                Log::warning('Could not determine site for reminders');
                return;
            }

            // Get authenticated user ID if available
            $userId = auth()->check() ? auth()->id() : null;

            // Create or get guest record for tracking
            $guest = $this->getOrCreateGuest($guestName, $guestPhone, $guestEmail, $site->id, $userId);

            // Send SMS reminder if requested
            if (in_array($reminderType, ['sms', 'both'])) {
                $this->sendSmsReminder($guestPhone, $guestName, $week, $guest, $site);
            }

            // Send email reminder if requested
            if (in_array($reminderType, ['email', 'both'])) {
                $this->sendEmailReminder($guestEmail, $guestName, $week, $guest, $site);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send reminders for cleaning signup: ' . $e->getMessage());
        }
    }

    /**
     * Send SMS reminder via Prasso Messaging.
     */
    private function sendSmsReminder(string $phone, string $name, int $week, MsgGuest $guest, $site)
    {
        try {
            // Get the week start date and format it nicely
            $weekStartDate = $this->getDateOfWeek(now()->year, $week);
            $formattedDate = \Carbon\Carbon::parse($weekStartDate)->format('F j');

            // Format the message
            $messageBody = "Hi {$name}, thank you for signing up to clean the church for the week of {$formattedDate}. We'll send you more details soon!";

            // Get team ID from site's teams relationship
            $teamId = $site->teams()->first()?->id;

            // Create message record
            $msgRecord = MsgMessage::create([
                'team_id' => $teamId,
                'subject' => 'Church Cleaning Signup Confirmation',
                'body' => $messageBody,
                'type' => 'sms',
            ]);

            // Create delivery record
            $delivery = MsgDelivery::create([
                'team_id' => $teamId,
                'msg_message_id' => $msgRecord->id,
                'recipient_type' => 'guest',
                'recipient_id' => $guest->id,
                'channel' => 'sms',
                'status' => 'queued',
                'metadata' => [
                    'subject' => 'Church Cleaning Signup Confirmation',
                    'preview' => mb_substr($messageBody, 0, 120),
                ],
            ]);

            Log::info("SMS reminder message created for cleaning signup, delivery ID: {$delivery->id}");
        } catch (\Exception $e) {
            Log::warning('SMS reminder failed: ' . $e->getMessage());
        }
    }

    /**
     * Send email reminder.
     */
    private function sendEmailReminder(string $email, string $name, int $week, MsgGuest $guest, $site)
    {
        try {
            // Get the week start date and format it nicely
            $weekStartDate = $this->getDateOfWeek(now()->year, $week);
            $formattedDate = \Carbon\Carbon::parse($weekStartDate)->format('F j');

            // Format the message
            $messageBody = "Hi {$name},\n\nThank you for signing up to clean the church for the week of {$formattedDate}. We'll send you more details soon!";

            // Get team ID from site's teams relationship
            $teamId = $site->teams()->first()?->id;

            // Create message record
            $msgRecord = MsgMessage::create([
                'team_id' => $teamId,
                'subject' => 'Church Cleaning Signup Confirmation - ' . $site->site_name,
                'body' => $messageBody,
                'type' => 'email',
            ]);

            // Create delivery record
            $delivery = MsgDelivery::create([
                'team_id' => $teamId,
                'msg_message_id' => $msgRecord->id,
                'recipient_type' => 'guest',
                'recipient_id' => $guest->id,
                'channel' => 'email',
                'status' => 'queued',
                'metadata' => [
                    'subject' => 'Church Cleaning Signup Confirmation - ' . $site->site_name,
                    'preview' => mb_substr($messageBody, 0, 120),
                ],
            ]);

            Log::info("Email reminder message created for cleaning signup, delivery ID: {$delivery->id}");
        } catch (\Exception $e) {
            Log::warning('Email reminder failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the current site from the request.
     */
    private function getSiteFromRequest()
    {
        // Try to get from request host (with and without port)
        $host = request()->getHost();
        $httpHost = request()->getHttpHost();
        
        // Try exact match first
        $site = \App\Models\Site::where('host', $host)->orWhere('host', $httpHost)->first();
        
        // If not found, try matching just the hostname part (before the port)
        if (!$site) {
            $site = \App\Models\Site::where('host', 'like', $host . '%')->first();
        }
        
        return $site;
    }

    /**
     * Get or create a guest record for message tracking.
     */
    private function getOrCreateGuest(string $name, string $phone, string $email, int $siteId, ?int $userId = null): MsgGuest
    {
        // Try to find existing guest by email
        $guest = MsgGuest::where('email', $email)->first();

        if ($guest) {
            // Update with latest info if needed
            $guest->update([
                'name' => $name,
                'phone' => $phone,
                'user_id' => $userId, // Update user_id if authenticated
            ]);
            return $guest;
        }

        // Create new guest record
        return MsgGuest::create([
            'user_id' => $userId, // Use authenticated user ID if available
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'is_subscribed' => true,
        ]);
    }

    /**
     * Get the Monday start date for a given ISO week number.
     */
    private function getDateOfWeek(int $year, int $week): string
    {
        $date = new \DateTime();
        $date->setISODate($year, $week, 1); // 1 = Monday
        return $date->format('Y-m-d');
    }
}
