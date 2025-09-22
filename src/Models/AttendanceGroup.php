<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AttendanceGroup extends Model
{
    use HasFactory;

    protected $table = 'chm_attendance_groups';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'ministry_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the ministry that owns the attendance group.
     */
    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Get the user who created the group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all members that belong to the group.
     */
    public function members(): MorphToMany
    {
        return $this->morphedByMany(
            Member::class,
            'attendable',
            'chm_attendance_group_members',
            'group_id',
            'attendable_id'
        )->withPivot('start_date', 'end_date', 'notes')
         ->withTimestamps();
    }

    /**
     * Get all families that belong to the group.
     */
    public function families(): MorphToMany
    {
        return $this->morphedByMany(
            Family::class,
            'attendable',
            'chm_attendance_group_members',
            'group_id',
            'attendable_id'
        )->withPivot('start_date', 'end_date', 'notes')
         ->withTimestamps();
    }

    /**
     * Get all groups that belong to the group.
     */
    public function groups(): MorphToMany
    {
        return $this->morphedByMany(
            Group::class,
            'attendable',
            'chm_attendance_group_members',
            'group_id',
            'attendable_id'
        )->withPivot('start_date', 'end_date', 'notes')
         ->withTimestamps();
    }

    /**
     * Get all active members of the group.
     */
    public function activeMembers()
    {
        $now = now()->toDateString();
        
        return $this->members()
            ->where(function ($query) use ($now) {
                $query->whereNull('chm_attendance_group_members.start_date')
                      ->orWhere('chm_attendance_group_members.start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('chm_attendance_group_members.end_date')
                      ->orWhere('chm_attendance_group_members.end_date', '>=', $now);
            });
    }

    /**
     * Get all active families in the group.
     */
    public function activeFamilies()
    {
        $now = now()->toDateString();
        
        return $this->families()
            ->where(function ($query) use ($now) {
                $query->whereNull('chm_attendance_group_members.start_date')
                      ->orWhere('chm_attendance_group_members.start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('chm_attendance_group_members.end_date')
                      ->orWhere('chm_attendance_group_members.end_date', '>=', $now);
            });
    }

    /**
     * Get all active groups in the group.
     */
    public function activeGroups()
    {
        $now = now()->toDateString();
        
        return $this->groups()
            ->where(function ($query) use ($now) {
                $query->whereNull('chm_attendance_group_members.start_date')
                      ->orWhere('chm_attendance_group_members.start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('chm_attendance_group_members.end_date')
                      ->orWhere('chm_attendance_group_members.end_date', '>=', $now);
            });
    }

    /**
     * Get all members including those in subgroups.
     */
    public function getAllMembers()
    {
        $members = $this->activeMembers()->get();
        
        // Get members from families
        foreach ($this->activeFamilies as $family) {
            $members = $members->merge($family->activeMembers);
        }
        
        // Get members from subgroups
        foreach ($this->activeGroups as $subgroup) {
            $members = $members->merge($subgroup->getAllMembers());
        }
        
        return $members->unique('id');
    }

    /**
     * Get the attendance rate for the group for a specific event or date range.
     */
    public function getAttendanceRate($eventId = null, $startDate = null, $endDate = null)
    {
        $memberIds = $this->getAllMembers()->pluck('id');
        
        if ($memberIds->isEmpty()) {
            return 0;
        }
        
        $query = AttendanceRecord::whereIn('member_id', $memberIds);
        
        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        
        if ($startDate) {
            $query->where('check_in_time', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('check_in_time', '<=', $endDate);
        }
        
        $attendedCount = $query->distinct('member_id')->count('member_id');
        $totalMembers = $memberIds->count();
        
        return $totalMembers > 0 ? ($attendedCount / $totalMembers) * 100 : 0;
    }
}
