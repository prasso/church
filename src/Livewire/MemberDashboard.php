<?php

namespace Prasso\Church\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Illuminate\Support\Facades\Auth;

class MemberDashboard extends Component
{
    #[Url]
    public $tab = 'overview';

    public ?Member $member = null;
    public $availablePositions = [];
    public $myAssignments = [];
    public $memberData = [];
    public $editingProfile = false;

    public function mount()
    {
        $user = Auth::user();
        
        if (!$user) {
            redirect('/login');
        }

        $this->member = Member::where('user_id', $user->id)->first();

        if (!$this->member) {
            // Member record doesn't exist yet
            $this->member = null;
        } else {
            $this->loadMemberData();
            $this->loadVolunteerData();
        }
    }

    public function loadMemberData()
    {
        if (!$this->member) return;

        $this->memberData = [
            'first_name' => $this->member->first_name,
            'last_name' => $this->member->last_name,
            'email' => $this->member->email,
            'phone' => $this->member->phone,
            'address' => $this->member->address,
            'city' => $this->member->city,
            'state' => $this->member->state,
            'postal_code' => $this->member->postal_code,
            'membership_status' => $this->member->membership_status,
        ];
    }

    public function loadVolunteerData()
    {
        if (!$this->member) return;

        // Load available positions (open for signup)
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
            ->toArray();

        // Load my active assignments
        $this->myAssignments = VolunteerAssignment::where('member_id', $this->member->id)
            ->where('status', 'active')
            ->with('position')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'position_id' => $assignment->position_id,
                    'position_title' => $assignment->position->title ?? 'Unknown',
                    'start_date' => $assignment->start_date?->format('M d, Y'),
                    'end_date' => $assignment->end_date?->format('M d, Y'),
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                ];
            })
            ->toArray();
    }

    public function signUpForPosition($positionId)
    {
        if (!$this->member) {
            $this->dispatch('notify', type: 'error', message: 'Member profile not found.');
            return;
        }

        $position = VolunteerPosition::findOrFail($positionId);

        if (!$position->isOpen()) {
            $this->dispatch('notify', type: 'error', message: 'This position is no longer open.');
            return;
        }

        // Check for existing active assignment
        $existing = VolunteerAssignment::where('member_id', $this->member->id)
            ->where('position_id', $positionId)
            ->where('status', 'active')
            ->exists();

        if ($existing) {
            $this->dispatch('notify', type: 'warning', message: 'You are already signed up for this position.');
            return;
        }

        try {
            VolunteerAssignment::create([
                'member_id' => $this->member->id,
                'position_id' => $positionId,
                'start_date' => now(),
                'status' => 'active',
                'assigned_by' => Auth::id(),
                'approved_by' => Auth::id(),
            ]);

            $this->dispatch('notify', type: 'success', message: 'Successfully signed up for ' . $position->title);
            $this->loadVolunteerData();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to sign up: ' . $e->getMessage());
        }
    }

    public function cancelAssignment($assignmentId)
    {
        $assignment = VolunteerAssignment::findOrFail($assignmentId);

        if ($assignment->member_id !== $this->member->id) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        try {
            $assignment->update([
                'status' => 'inactive',
                'end_date' => now(),
            ]);

            $this->dispatch('notify', type: 'success', message: 'Cancelled assignment.');
            $this->loadVolunteerData();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to cancel: ' . $e->getMessage());
        }
    }

    public function toggleEditProfile()
    {
        $this->editingProfile = !$this->editingProfile;
    }

    public function updateProfile()
    {
        if (!$this->member) return;

        try {
            $this->member->update([
                'first_name' => $this->memberData['first_name'],
                'last_name' => $this->memberData['last_name'],
                'email' => $this->memberData['email'],
                'phone' => $this->memberData['phone'],
                'address' => $this->memberData['address'],
                'city' => $this->memberData['city'],
                'state' => $this->memberData['state'],
                'postal_code' => $this->memberData['postal_code'],
            ]);

            $this->editingProfile = false;
            $this->dispatch('notify', type: 'success', message: 'Profile updated successfully.');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to update profile: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.member-dashboard', [
            'member' => $this->member,
            'availablePositions' => $this->availablePositions,
            'myAssignments' => $this->myAssignments,
            'memberData' => $this->memberData,
            'editingProfile' => $this->editingProfile,
            'tab' => $this->tab,
        ]);
    }
}
