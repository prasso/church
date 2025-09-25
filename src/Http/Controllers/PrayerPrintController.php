<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\PrayerRequest;

class PrayerPrintController extends Controller
{
    /**
     * Display a printable list of all prayer requests.
     */
    public function printAll(Request $request)
    {
        $this->authorizeView();

        $requests = PrayerRequest::query()
            ->with(['member', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('church::prayer-requests.print', [
            'title' => 'All Prayer Requests',
            'generatedAt' => now(),
            'requests' => $requests,
        ]);
    }

    /**
     * Display a printable list of SMS-sourced prayer requests.
     */
    public function printSms(Request $request)
    {
        $this->authorizeView();

        $requests = PrayerRequest::query()
            ->fromSms()
            ->with(['member', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('church::prayer-requests.print', [
            'title' => 'SMS Prayer Requests',
            'generatedAt' => now(),
            'requests' => $requests,
        ]);
    }

    /**
     * Basic gate to ensure only authenticated users can view print pages.
     */
    protected function authorizeView(): void
    {
        // For now rely on route middleware 'auth'.
        // This method can be extended to include additional authorization logic if needed.
    }
}
