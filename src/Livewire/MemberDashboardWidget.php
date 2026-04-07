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

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        
        // Only show if user is an admin and also a member
        if (!$user || !$this->isAdmin($user) || !$this->isMember($user)) {
            return;
        }

        $this->member = Member::where('user_id', $user->id)->first();

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
        $member = Member::where('user_id', $user->id)->first();

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
        return $user->hasRole('site_admin') || $user->hasRole('super_admin');
    }

    protected function isMember($user): bool
    {
        return Member::where('user_id', $user->id)->exists();
    }

    public function shouldRender(): bool
    {
        $user = Auth::user();
        return $user && $this->isAdmin($user) && $this->isMember($user);
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return view('livewire.empty-component');
        }

        return view('church.widgets.member-dashboard-widget', [
            'member' => $this->member,
            'myAssignments' => $this->myAssignments,
            'availablePositions' => $this->availablePositions,
            'memberDashboardUrl' => route('church.member.dashboard'),
        ]);
    }
}
