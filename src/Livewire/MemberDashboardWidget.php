<?php

namespace Prasso\Church\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Filament\Notifications\Notification;

class MemberDashboardWidget extends Component
{
    public $member;
    public $myAssignments = [];
    public $availablePositions = [];
    public $siteId;

    public function mount($siteId = null)
    {
        $this->siteId = $siteId;
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        
        // Only show if user is an admin and also a member of the current site
        if (!$user || !$this->isAdmin($user) || !$this->isMemberOfSite($user)) {
            return;
        }

        $this->member = Member::where('email', $user->email)->first();

        if (!$this->member) {
            return;
        }

        // Get member's active assignments
        $this->myAssignments = VolunteerAssignment::where('member_id', $this->member->id)
            ->where('status', 'active')
            ->with('position')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'position_title' => $assignment->position->title ?? 'Unknown',
                    'start_date' => $assignment->start_date?->format('M d, Y'),
                    'end_date' => $assignment->end_date?->format('M d, Y'),
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                ];
            })
            ->toArray();

        // Get available positions
        $this->availablePositions = VolunteerPosition::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with('activeVolunteers')
            ->get()
            ->filter(function ($position) {
                return $position->isOpen();
            })
            ->map(function ($position) {
                return [
                    'id' => $position->id,
                    'title' => $position->title,
                    'description' => $position->description,
                    'time_commitment' => $position->time_commitment,
                    'location' => $position->location,
                    'max_volunteers' => $position->max_volunteers,
                    'current_volunteers' => $position->activeVolunteers()->count(),
                    'is_open' => $position->isOpen(),
                ];
            })
            ->values()
            ->take(5) // Limit to first 5 for dashboard
            ->toArray();
    }

    public function signUpForPosition($positionId)
    {
        $user = Auth::user();
        
        // Smart member lookup - first try user_id, then email with NULL user_id
        $member = Member::when($this->siteId, function ($query) {
                return $query->where('site_id', $this->siteId);
            })
            ->where('user_id', $user->id)
            ->first();
            
        // If not found by user_id, try to find by email and update the user_id
        if (!$member) {
            $member = Member::where('email', $user->email)
                ->whereNull('user_id')
                ->when($this->siteId, function ($query) {
                    return $query->where('site_id', $this->siteId);
                })
                ->first();
            
            // If found by email with no user_id, update it
            if ($member) {
                $member->update(['user_id' => $user->id]);
            }
        }

        if (!$member) {
            Notification::make()
                ->danger()
                ->title('Member Profile Not Found')
                ->body('Your member profile could not be found.')
                ->send();
            return;
        }

        $position = VolunteerPosition::findOrFail($positionId);

        if (!$position->isOpen()) {
            Notification::make()
                ->warning()
                ->title('Position Not Available')
                ->body('This position is no longer open for new volunteers.')
                ->send();
            return;
        }

        // Check for existing active assignment
        $existing = VolunteerAssignment::where('member_id', $member->id)
            ->where('position_id', $positionId)
            ->where('status', 'active')
            ->exists();

        if ($existing) {
            Notification::make()
                ->warning()
                ->title('Already Signed Up')
                ->body('You are already signed up for this position.')
                ->send();
            return;
        }

        try {
            VolunteerAssignment::create([
                'member_id' => $member->id,
                'position_id' => $positionId,
                'start_date' => now(),
                'status' => 'active',
                'assigned_by' => Auth::id(),
                'approved_by' => Auth::id(),
            ]);

            Notification::make()
                ->success()
                ->title('Successfully Signed Up')
                ->body("You have been signed up for {$position->title}.")
                ->send();

            // Refresh the data
            $this->loadData();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to Sign Up')
                ->body('An error occurred while signing up: ' . $e->getMessage())
                ->send();
        }
    }

    protected function isAdmin($user): bool
    {
        // Check if user has admin role (site admin or super admin)
        return $user->isSuperAdmin() || $user->isInstructor();
    }

    protected function isMember($user): bool
    {
        return Member::where('email', $user->email)->exists();
    }

    protected function isMemberOfSite($user): bool
    {
        // If no siteId provided, cannot verify membership
        if (!$this->siteId) {
            return false;
        }

        // Check if user has a member record associated with this site
        return Member::where('user_id', $user->id)
            ->where('site_id', $this->siteId)
            ->exists();
    }

    public function shouldRender(): bool
    {
        $user = Auth::user();
        return $user && $this->isAdmin($user) && $this->isMemberOfSite($user);
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return view('livewire.empty-component');
        }

        // Get the site for styling
        $site = $this->siteId ? \App\Models\Site::find($this->siteId) : null;

        return view('church::widgets.member-dashboard-widget', [
            'member' => $this->member,
            'myAssignments' => $this->myAssignments,
            'availablePositions' => $this->availablePositions,
            'site' => $site,
        ]);
    }
}
