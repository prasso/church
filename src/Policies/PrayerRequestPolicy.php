<?php

namespace Prasso\Church\Policies;

use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\Member;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrayerRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user, ?Group $group = null)
    {
        // If checking within a group context, verify the user is a member
        if ($group) {
            return $group->members()->where('member_id', $user->member?->id)->exists();
        }
        
        // Otherwise, any authenticated user can view prayer requests
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, PrayerRequest $prayerRequest)
    {
        // Admin can view any prayer request
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Members can view their own prayer requests or public ones
        $member = $user->member;
        if (!$member) {
            return false;
        }
        
        return $prayerRequest->is_public || 
               $prayerRequest->member_id === $member->id ||
               $prayerRequest->requested_by === $member->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user)
    {
        // Any authenticated user with a member record can create prayer requests
        return $user->member !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, PrayerRequest $prayerRequest)
    {
        // Admin can update any prayer request
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Only the requester or the subject can update the prayer request
        $member = $user->member;
        if (!$member) {
            return false;
        }
        
        return $prayerRequest->requested_by === $member->id || 
               $prayerRequest->member_id === $member->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, PrayerRequest $prayerRequest)
    {
        // Admin can delete any prayer request
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Only the requester can delete the prayer request
        $member = $user->member;
        if (!$member) {
            return false;
        }
        
        return $prayerRequest->requested_by === $member->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, PrayerRequest $prayerRequest)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, PrayerRequest $prayerRequest)
    {
        return $user->hasRole('admin');
    }
}
