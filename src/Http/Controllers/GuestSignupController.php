<?php

namespace Prasso\Church\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prasso\Church\Models\Member;
use Prasso\Messaging\Http\Controllers\Api\ConsentController;

class GuestSignupController extends Controller
{
    /**
     * Handle guest signup form submission.
     * Creates a member record and initiates SMS consent flow.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'heard_from' => 'nullable|string|max:1000',
            'first_time' => 'nullable|boolean',
            'consent' => 'required|boolean',
        ]);

        // Consent must be explicitly accepted
        if (!$validated['consent']) {
            return $this->sendError(
                'You must agree to receive text messages to continue.',
                [],
                422
            );
        }

        try {
            $site = $this->getSiteFromRequest();
            if (!$site) {
                return $this->sendError('Site not found.', [], 404);
            }

            // Create or update member
            $member = $this->getOrCreateMember(
                $validated['name'],
                $validated['phone'],
                $validated['email'] ?? null,
                $site->id
            );

            // Store additional information in notes if provided
            $notes = 'Guest signup via web form';
            if ($validated['heard_from'] ?? null) {
                $notes .= ' - Heard from: ' . $validated['heard_from'];
            }
            if ($validated['first_time'] ?? false) {
                $notes .= ' - First time visitor';
            }
            $member->notes = $notes;
            $member->save();

            // Initiate SMS consent flow via messaging package
            $consentRequest = new Request([
                'phone' => $validated['phone'],
                'name' => $validated['name'],
                'email' => $validated['email'] ?? '',
                'checkbox' => true,
                'consent_checkbox' => true,
                'source_url' => $request->headers->get('referer'),
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'team_id' => $site->team_id ?? null,
            ]);

            $consentController = new ConsentController();
            $consentResponse = $consentController->optInWeb($consentRequest);
            $consentStatusCode = $consentResponse->getStatusCode();

            // Log the signup
            Log::info('Guest signup initiated', [
                'member_id' => $member->id,
                'site_id' => $site->id,
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'consent_status' => $consentStatusCode,
            ]);

            // Return success response
            return $this->sendResponse(
                [
                    'member_id' => $member->id,
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'message' => 'Thank you! Please check your phone for a confirmation text message.',
                ],
                'Guest signup recorded. Confirmation SMS sent.',
                201
            );
        } catch (\Exception $e) {
            Log::error('Guest signup error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->sendError(
                'An error occurred while processing your signup.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get or create a member from guest signup data.
     *
     * @param string $name
     * @param string $phone
     * @param string|null $email
     * @param int $siteId
     * @return Member
     */
    protected function getOrCreateMember(string $name, string $phone, ?string $email, int $siteId): Member
    {
        // Try to find existing member by phone or email
        $query = Member::where('site_id', $siteId);

        if ($email) {
            $member = $query->where(function ($q) use ($phone, $email) {
                $q->where('phone', 'like', "%$phone")
                  ->orWhere('email', $email);
            })->first();
        } else {
            $member = $query->where('phone', 'like', "%$phone")->first();
        }

        if ($member) {
            // Update existing member with any new information
            if ($email && !$member->email) {
                $member->email = $email;
            }
            $member->save();
            return $member;
        }

        // Create new member
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        return Member::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'site_id' => $siteId,
            'membership_status' => 'visitor',
            'notes' => 'Guest signup via web form',
        ]);
    }

    /**
     * Get the site from the request context.
     * Looks for site_id in request, or uses current site from middleware.
     *
     * @return Site|null
     */
    protected function getSiteFromRequest(): ?Site
    {
        // Try to get from request parameter
        if (request()->has('site_id')) {
            return Site::find(request()->input('site_id'));
        }

        // Try to get from middleware (if set by site context middleware)
        if (request()->has('site')) {
            return request()->input('site');
        }

        // Try to get from subdomain or host
        // The host column contains comma-separated values, so we need to search within it
        $requestHost = request()->getHost();
        $sites = Site::all();
        
        foreach ($sites as $site) {
            $hosts = array_map('trim', explode(',', $site->host ?? ''));
            if (in_array($requestHost, $hosts)) {
                return $site;
            }
        }

        // Fallback: return first site (for development)
        return Site::first();
    }
}
