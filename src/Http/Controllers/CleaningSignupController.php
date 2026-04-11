<?php

namespace Prasso\Church\Http\Controllers;

use App\Http\Controllers\Controller as AppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Prasso\Church\Models\Member;
use Prasso\Messaging\Models\MsgMessage;
use Prasso\Messaging\Models\MsgDelivery;

class CleaningSignupController extends Controller
{
    /**
     * Display the cleaning signup form.
     */
    public function show()
    {
        // Get or create the "Clean the Church" volunteer position
        $position = VolunteerPosition::where('title', 'Clean the Church')->first();
        
        $site = AppController::getClientFromHost();
        $masterPage = AppController::getMasterForSite($site);
        
        // Get the masterpage template name from site or use default
        $masterpageTemplate = $this->getMasterpageTemplate($site, $masterPage);
        
        if (!$position) {
            $sitePage = $this->buildSitePage(
                $site,
                $masterpageTemplate,
                'Cleaning Signup',
                'cleaning-signup',
                view('church::cleaning-signup', [
                    'error' => 'The cleaning volunteer position is not available. Please contact the church office.',
                    'site' => $site,
                ])->render()
            );

            return view($masterpageTemplate, [
                'sitePage' => $sitePage,
                'site' => $site,
                'page_short_url' => '/cleaning-signup',
                'masterPage' => $masterPage,
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

        $sitePage = $this->buildSitePage(
            $site,
            $masterpageTemplate,
            'Cleaning Signup',
            'cleaning-signup',
            view('church::cleaning-signup', [
                'position' => $position,
                'userData' => $userData,
                'isAuthenticated' => (bool) $user,
                'site' => $site,
            ])->render()
        );

        return view($masterpageTemplate, [
            'sitePage' => $sitePage,
            'site' => $site,
            'page_short_url' => '/cleaning-signup',
            'masterPage' => $masterPage,
        ]);
    }

    /**
     * Get the masterpage template name for the site
     */
    private function getMasterpageTemplate($site, $masterPage)
    {
        if ($site && !empty($site->default_masterpage)) {
            return $site->default_masterpage;
        }

        // If we have a masterPage object, get the template name from it
        if ($masterPage && isset($masterPage->pagename)) {
            return $masterPage->pagename;
        }
        
        // Otherwise, try to get from site's SitePages
        if ($site) {
            $sitePage = \App\Models\SitePages::where('fk_site_id', $site->id)->first();
            if ($sitePage && isset($sitePage->masterpage)) {
                return $sitePage->masterpage;
            }
        }
        
        // Fallback to default masterpage
        return 'sitepage.templates.blankpage';
    }

    private function buildSitePage($site, string $masterpageTemplate, string $title, string $section, string $content)
    {
        $sitePage = new \App\Models\SitePages();
        $sitePage->fk_site_id = $site?->id;
        $sitePage->title = $title;
        $sitePage->section = $section;
        $sitePage->description = $content;
        $sitePage->masterpage = $masterpageTemplate;

        return $sitePage;
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

            $site = $this->getSiteFromRequest();
            $member = $this->getOrCreateMember(
                $validated['name'],
                $validated['phone'] ?? null,
                $validated['email'] ?? null,
                $site?->id,
                auth()->user()?->id
            );
            $memberId = $member->id;
            $signupType = auth()->check() ? 'member' : 'public';

            // Calculate start date from selected week number
            $weekNumber = $validated['selected_week'];
            $year = now()->year;
            $startDate = $this->getDateOfWeek($year, $weekNumber);

            $metadata = [
                'signup_name' => $validated['name'],
                'signup_phone' => $validated['phone'] ?? null,
                'signup_email' => $validated['email'] ?? null,
                'reminder_type' => $validated['reminder_type'],
                'preferred_week' => $validated['selected_week'],
                'data_key' => $validated['data_key'],
                'signup_date' => now()->toDateTimeString(),
                'ip_address' => request()->ip(),
                'signup_type' => $signupType,
            ];

            $sendReminders = false;

            // Create or update a volunteer assignment with week preference in metadata
            $assignment = DB::transaction(function () use (
                $position,
                $memberId,
                $signupType,
                $startDate,
                $metadata,
                &$sendReminders
            ) {
                $assignment = VolunteerAssignment::withTrashed()
                    ->where('position_id', $position->id)
                    ->where('member_id', $memberId)
                    ->whereDate('start_date', $startDate)
                    ->first();

                if ($assignment) {
                    $existingMetadata = $assignment->metadata ?? [];
                    $sendReminders = ($existingMetadata['reminder_type'] ?? null) !== ($metadata['reminder_type'] ?? null)
                        || ($existingMetadata['signup_phone'] ?? null) !== ($metadata['signup_phone'] ?? null)
                        || ($existingMetadata['signup_email'] ?? null) !== ($metadata['signup_email'] ?? null);

                    if (method_exists($assignment, 'trashed') && $assignment->trashed()) {
                        $assignment->restore();
                        $assignment->status = 'pending';
                    }

                    $assignment->notes = "{$signupType} signup via cleaning form";
                    $assignment->metadata = array_merge($existingMetadata, $metadata);
                    $assignment->save();

                    return $assignment;
                }

                $sendReminders = true;

                return VolunteerAssignment::create([
                    'position_id' => $position->id,
                    'member_id' => $memberId,
                    'start_date' => $startDate,
                    'status' => 'pending', // Requires admin approval
                    'notes' => "{$signupType} signup via cleaning form",
                    'metadata' => $metadata,
                ]);
            });

            if ($sendReminders) {
                $this->sendReminders($assignment, $member, $validated, $site);
            }

            if ($request->expectsJson() || $request->input('return_json')) {
                $registrationMode = null;
                $registrationUrl = null;
                if (!auth()->check() && !$member->user_id) {
                    $registrationMode = $site?->invitation_only ? 'invitation' : 'register';
                    $registrationUrl = $registrationMode === 'invitation'
                        ? route('invitation-request.show', [
                            'name' => $validated['name'],
                            'email' => $validated['email'] ?? null,
                        ])
                        : route('register', [
                            'name' => $validated['name'],
                            'email' => $validated['email'] ?? null,
                        ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for signing up for church cleaning!',
                    'assignment_id' => $assignment->id,
                    'registration_url' => $registrationUrl,
                    'registration_mode' => $registrationMode,
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
     * Display the current list of cleaners and their assigned weeks.
     */
    public function report()
    {
        $position = VolunteerPosition::where('title', 'Clean the Church')->first();

        if (!$position) {
            return view('church::cleaning-report', [
                'error' => 'The cleaning volunteer position is not available. Please contact the church office.'
            ]);
        }

        $assignments = VolunteerAssignment::where('position_id', $position->id)
            ->whereIn('status', ['pending', 'active'])
            ->with('member')
            ->orderBy('start_date')
            ->get()
            ->map(function ($assignment) {
                $startDate = $assignment->start_date;
                $weekNumber = $assignment->metadata['preferred_week'] ?? null;
                if (!$weekNumber && $startDate) {
                    $weekNumber = (int) $startDate->copy()->subDays(3)->format('W');
                }

                $weekRange = null;
                if ($startDate) {
                    $weekRange = $startDate->format('M j') . ' - ' . $startDate->copy()->addDays(2)->format('M j, Y');
                }

                return [
                    'member_name' => $assignment->member?->full_name
                        ?: ($assignment->metadata['signup_name'] ?? 'Unknown'),
                    'week_number' => $weekNumber,
                    'week_range' => $weekRange,
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                ];
            })
            ->toArray();

        return view('church::cleaning-report', [
            'position' => $position,
            'assignments' => $assignments,
        ]);
    }

    /**
     * Display the cleaning checklist.
     *
     * @return \Illuminate\View\View
     */
    public function checklist()
    {
        $regularTasks = [
            "Vacuum auditorium carpet",
            "Vacuum nursery carpet", 
            "Vacuum runners in foyer",
            "Vacuum blue classroom",
            "Sweep and mop fellowship room",
            "Sweep and mop foyer and bathrooms",
            "Sweep and mop back door hallway",
            "Sweep and mop auditorium platform",
            "Sweep children's classroom",
            "Clean toilets in all four bathrooms",
            "Clean sinks in all four bathrooms",
            "Clean mirrors in bathrooms",
            "Sanitize baby changing table with Lysol spray",
            "Clean four glass doors and the nursery door",
            "Collect and empty trash in children's classroom",
            "Collect and empty trash from all four bathrooms",
            "Spray and wipe down tables and countertops",
            "Clean trash off of tables and pews",
        ];

        $extraTasks = [
            "Sweep front porch and sidewalks if needed",
            "Vacuum pew seats",
            "Dust furniture",
            "Dust window sills", 
            "Clean glass in front of baptistry",
        ];

        $site = AppController::getClientFromHost();
        $masterPage = AppController::getMasterForSite($site);
        
        // Get the masterpage template name from site or use default
        $masterpageTemplate = $this->getMasterpageTemplate($site, $masterPage);
        
        $sitePage = $this->buildSitePage(
            $site,
            $masterpageTemplate,
            'Cleaning Checklist',
            'cleaning-checklist',
            view('church::cleaning-checklist', [
                'regularTasks' => $regularTasks,
                'extraTasks' => $extraTasks,
                'site' => $site,
            ])->render()
        );

        return view($masterpageTemplate, [
            'sitePage' => $sitePage,
            'site' => $site,
            'page_short_url' => '/cleaning-checklist',
            'masterPage' => $masterPage,
        ]);
    }

    /**
     * Send reminder notifications based on user preference.
     */
    private function sendReminders(VolunteerAssignment $assignment, Member $member, array $validated, ?\App\Models\Site $site = null)
    {
        $reminderType = $validated['reminder_type'];
        $memberName = $member->getMemberDisplayName() ?? $validated['name'];
        $memberPhone = $validated['phone'] ?? $member->phone;
        $memberEmail = $validated['email'] ?? $member->email;
        $week = $validated['selected_week'];

        try {
            $site = $site ?? $this->getSiteFromRequest();
            if (!$site) {
                Log::warning('Could not determine site for reminders');
                return;
            }

            // Send SMS reminder if requested
            if (in_array($reminderType, ['sms', 'both']) && $memberPhone) {
                $this->sendSmsReminder($memberPhone, $memberName, $week, $member, $site);
            }

            // Send email reminder if requested
            if (in_array($reminderType, ['email', 'both']) && $memberEmail) {
                $this->sendEmailReminder($memberEmail, $memberName, $week, $member, $site);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send reminders for cleaning signup: ' . $e->getMessage());
        }
    }

    /**
     * Send SMS reminder via Prasso Messaging.
     */
    private function sendSmsReminder(?string $phone, string $name, int $week, Member $member, $site)
    {
        if (!$phone) {
            Log::warning('SMS reminder skipped: missing phone number.');
            return;
        }
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
                'recipient_type' => 'member',
                'recipient_id' => $member->id,
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
    private function sendEmailReminder(?string $email, string $name, int $week, Member $member, $site)
    {
        if (!$email) {
            Log::warning('Email reminder skipped: missing email address.');
            return;
        }
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
                'recipient_type' => 'member',
                'recipient_id' => $member->id,
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
     * Get or create a member record for message tracking.
     */
    private function getOrCreateMember(string $name, ?string $phone, ?string $email, ?int $siteId, ?int $userId = null): Member
    {
        $member = null;

        if ($userId) {
            $member = Member::where('user_id', $userId)->first();
        }

        if (!$member && ($email || $phone)) {
            $memberQuery = Member::query();
            if ($siteId) {
                $memberQuery->where('site_id', $siteId);
            }
            $memberQuery->where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->orWhere('email', $email);
                }
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            });

            $member = $memberQuery->first();
        }

        [$firstName, $middleName, $lastName] = $this->splitName($name);

        if ($member) {
            $member->fill([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $email ?? $member->email,
                'phone' => $phone ?? $member->phone,
                'user_id' => $userId ?? $member->user_id,
                'site_id' => $siteId ?? $member->site_id,
            ]);
            $member->save();
            return $member;
        }

        return Member::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'user_id' => $userId,
            'site_id' => $siteId,
        ]);
    }

    /**
     * Split a full name into first, middle, and last parts.
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (empty($parts)) {
            return ['', null, ''];
        }

        $firstName = array_shift($parts);
        $lastName = count($parts) ? array_pop($parts) : '';
        $middleName = count($parts) ? implode(' ', $parts) : null;

        return [$firstName, $middleName, $lastName];
    }

    /**
     * Get the Thursday start date for a given ISO week number.
     */
    private function getDateOfWeek(int $year, int $week): string
    {
        $date = new \DateTime();
        $date->setISODate($year, $week, 1); // Monday
        $date->modify('+3 days');
        return $date->format('Y-m-d');
    }
}
