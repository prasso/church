<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class Group extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'ministry_id',
        'meeting_schedule',
        'meeting_location',
        'start_date',
        'end_date',
        'max_members',
        'is_open',
        'requires_approval',
        'contact_person_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_open' => 'boolean',
        'requires_approval' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_open' => true,
        'requires_approval' => false,
    ];

    /**
     * Get the ministry that owns the group.
     */
    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Get the contact person for the group.
     */
    public function contactPerson()
    {
        return $this->belongsTo(Member::class, 'contact_person_id');
    }

    /**
     * Get all members of the group.
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'chm_group_member', 'group_id', 'member_id')
            ->withPivot(['role', 'join_date', 'leave_date', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all leaders of the group.
     */
    public function leaders()
    {
        return $this->members()->wherePivot('role', 'leader');
    }

    /**
     * Get all active members of the group.
     */
    public function activeMembers()
    {
        return $this->members()->wherePivot('status', 'active');
    }

    /**
     * Get all events associated with this group.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }
    
    /**
     * Get all prayer requests associated with this group.
     */
    public function prayerRequests()
    {
        return $this->belongsToMany(PrayerRequest::class, 'chm_prayer_group_requests', 'group_id', 'prayer_request_id')
            ->withTimestamps();
    }

    /**
     * Check if a member is a leader of this group.
     *
     * @param  \Prasso\Church\Models\Member|int  $member
     * @return bool
     */
    public function isLeader($member)
    {
        $memberId = $member instanceof Member ? $member->id : $member;
        return $this->leaders()->where('member_id', $memberId)->exists();
    }

    /**
     * Check if a member is a member of this group.
     *
     * @param  \Prasso\Church\Models\Member|int  $member
     * @return bool
     */
    public function hasMember($member)
    {
        $memberId = $member instanceof Member ? $member->id : $member;
        return $this->members()->where('member_id', $memberId)->exists();
    }
}
