<?php

namespace Prasso\Church\Policies;

use Prasso\Church\Models\User;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\AttendanceGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_attendance');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceEvent|\Prasso\Church\Models\AttendanceRecord|\Prasso\Church\Models\AttendanceGroup  $model
     * @return mixed
     */
    public function view(User $user, $model)
    {
        if ($model instanceof AttendanceEvent) {
            return $user->can('view_attendance') && 
                   $this->canAccessEvent($user, $model);
        }
        
        if ($model instanceof AttendanceRecord) {
            return $user->can('view_attendance') && 
                   $this->canAccessRecord($user, $model);
        }
        
        if ($model instanceof AttendanceGroup) {
            return $user->can('view_attendance_groups') && 
                   $this->canAccessGroup($user, $model);
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return mixed
     */
    public function create(User $user, $model = null)
    {
        if ($model === AttendanceEvent::class || $model instanceof AttendanceEvent) {
            return $user->can('create_attendance_events');
        }
        
        if ($model === AttendanceRecord::class || $model instanceof AttendanceRecord) {
            return $user->can('create_attendance_records');
        }
        
        if ($model === AttendanceGroup::class || $model instanceof AttendanceGroup) {
            return $user->can('create_attendance_groups');
        }
        
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceEvent|\Prasso\Church\Models\AttendanceRecord|\Prasso\Church\Models\AttendanceGroup  $model
     * @return mixed
     */
    public function update(User $user, $model)
    {
        if ($model instanceof AttendanceEvent) {
            return $user->can('update_attendance_events') && 
                   $this->canAccessEvent($user, $model);
        }
        
        if ($model instanceof AttendanceRecord) {
            return $user->can('update_attendance_records') && 
                   $this->canAccessRecord($user, $model);
        }
        
        if ($model instanceof AttendanceGroup) {
            return $user->can('update_attendance_groups') && 
                   $this->canAccessGroup($user, $model);
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceEvent|\Prasso\Church\Models\AttendanceRecord|\Prasso\Church\Models\AttendanceGroup  $model
     * @return mixed
     */
    public function delete(User $user, $model)
    {
        if ($model instanceof AttendanceEvent) {
            return $user->can('delete_attendance_events') && 
                   $this->canAccessEvent($user, $model);
        }
        
        if ($model instanceof AttendanceRecord) {
            return $user->can('delete_attendance_records') && 
                   $this->canAccessRecord($user, $model);
        }
        
        if ($model instanceof AttendanceGroup) {
            return $user->can('delete_attendance_groups') && 
                   $this->canAccessGroup($user, $model);
        }
        
        return false;
    }
    
    /**
     * Determine whether the user can check in a member/family.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return bool
     */
    public function checkIn(User $user)
    {
        return $user->can('check_in_attendance');
    }
    
    /**
     * Determine whether the user can check out a member/family.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return bool
     */
    public function checkOut(User $user, AttendanceRecord $record)
    {
        return $user->can('check_out_attendance') && 
               ($record->checked_in_by == $user->id || $user->can('manage_attendance'));
    }
    
    /**
     * Determine whether the user can view attendance reports.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return bool
     */
    public function viewReports(User $user)
    {
        return $user->can('view_attendance_reports');
    }
    
    /**
     * Determine whether the user can export attendance data.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return bool
     */
    public function export(User $user)
    {
        return $user->can('export_attendance_data');
    }
    
    /**
     * Determine whether the user can manage attendance settings.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return bool
     */
    public function manageSettings(User $user)
    {
        return $user->can('manage_attendance_settings');
    }
    
    /**
     * Check if the user can access the event.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return bool
     */
    protected function canAccessEvent(User $user, AttendanceEvent $event)
    {
        // Super admins can access all events
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Event creator can access their own events
        if ($event->created_by == $user->id) {
            return true;
        }
        
        // Ministry leaders can access events for their ministries
        if ($event->ministry_id && $user->ministries->contains('id', $event->ministry_id)) {
            return true;
        }
        
        // Group leaders can access events for their groups
        if ($event->group_id && $user->leadGroups->contains('id', $event->group_id)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if the user can access the attendance record.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return bool
     */
    protected function canAccessRecord(User $user, AttendanceRecord $record)
    {
        // Super admins can access all records
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can access their own records
        if ($record->member_id && $record->member_id == $user->member?->id) {
            return true;
        }
        
        // Family members can access family records
        if ($record->family_id && $user->member?->family_id == $record->family_id) {
            return true;
        }
        
        // Check if user has access to the related event
        if ($record->event) {
            return $this->canAccessEvent($user, $record->event);
        }
        
        return false;
    }
    
    /**
     * Check if the user can access the attendance group.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\AttendanceGroup  $group
     * @return bool
     */
    protected function canAccessGroup(User $user, AttendanceGroup $group)
    {
        // Super admins can access all groups
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Group creator can access their own groups
        if ($group->created_by == $user->id) {
            return true;
        }
        
        // Ministry leaders can access groups for their ministries
        if ($group->ministry_id && $user->ministries->contains('id', $group->ministry_id)) {
            return true;
        }
        
        // Group leaders can access groups they lead
        if ($user->leadGroups->contains('id', $group->id)) {
            return true;
        }
        
        // Members can access groups they belong to
        if ($group->members->contains('id', $user->member?->id)) {
            return true;
        }
        
        return false;
    }
}
