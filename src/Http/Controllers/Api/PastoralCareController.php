<?php

namespace Prasso\Church\Http\Controllers\Api;

use Prasso\Church\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\PastoralVisit;
use Prasso\Church\Http\Resources\PrayerRequestResource;
use Prasso\Church\Http\Resources\PastoralVisitResource;

class PastoralCareController extends Controller
{
    /**
     * Display a dashboard of pastoral care metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        $data = [
            'prayer_requests' => [
                'total' => $isStaff 
                    ? PrayerRequest::count() 
                    : PrayerRequest::where('is_public', true)->count(),
                'active' => $isStaff 
                    ? PrayerRequest::where('status', 'active')->count()
                    : PrayerRequest::where('status', 'active')
                        ->where('is_public', true)
                        ->count(),
                'answered' => $isStaff 
                    ? PrayerRequest::where('status', 'answered')->count()
                    : PrayerRequest::where('status', 'answered')
                        ->where('is_public', true)
                        ->count(),
            ],
            'pastoral_visits' => [
                'total' => $isStaff 
                    ? PastoralVisit::count()
                    : PastoralVisit::where('member_id', $user->id)
                        ->orWhere('family_id', $user->family_id)
                        ->count(),
                'scheduled' => $isStaff 
                    ? PastoralVisit::where('status', 'scheduled')->count()
                    : PastoralVisit::whereIn('status', ['scheduled', 'in_progress'])
                        ->where(function($query) use ($user) {
                            $query->where('member_id', $user->id)
                                ->orWhere('family_id', $user->family_id);
                        })
                        ->count(),
                'completed' => $isStaff 
                    ? PastoralVisit::where('status', 'completed')->count()
                    : PastoralVisit::where('status', 'completed')
                        ->where(function($query) use ($user) {
                            $query->where('member_id', $user->id)
                                ->orWhere('family_id', $user->family_id);
                        })
                        ->count(),
            ],
            'upcoming_visits' => $isStaff
                ? PastoralVisitResource::collection(
                    PastoralVisit::with(['member', 'family', 'assignedTo'])
                        ->where('status', 'scheduled')
                        ->orderBy('scheduled_for')
                        ->take(5)
                        ->get()
                )
                : PastoralVisitResource::collection(
                    PastoralVisit::with(['member', 'family', 'assignedTo'])
                        ->whereIn('status', ['scheduled', 'in_progress'])
                        ->where(function($query) use ($user) {
                            $query->where('member_id', $user->id)
                                ->orWhere('family_id', $user->family_id);
                        })
                        ->orderBy('scheduled_for')
                        ->take(5)
                        ->get()
                ),
            'recent_requests' => $isStaff
                ? PrayerRequestResource::collection(
                    PrayerRequest::with(['member', 'requestedBy'])
                        ->latest()
                        ->take(5)
                        ->get()
                )
                : PrayerRequestResource::collection(
                    PrayerRequest::with(['member', 'requestedBy'])
                        ->where('is_public', true)
                        ->orWhere('member_id', $user->id)
                        ->orWhere('requested_by', $user->id)
                        ->latest()
                        ->take(5)
                        ->get()
                ),
        ];

        return response()->json($data);
    }
}
