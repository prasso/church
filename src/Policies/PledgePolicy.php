<?php

namespace Prasso\Church\Policies;

use App\Models\User;
use Prasso\Church\Models\Pledge;

class PledgePolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true; // Any authenticated user can view their own pledges
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Pledge $pledge)
    {
        return $user->id === $pledge->member_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true; // Any authenticated user can create pledges
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Pledge $pledge)
    {
        return $user->id === $pledge->member_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Pledge $pledge)
    {
        return $user->id === $pledge->member_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Pledge $pledge)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Pledge $pledge)
    {
        return $user->hasRole('admin');
    }
}
