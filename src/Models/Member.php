<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Events\MemberCreated;
use Prasso\Church\Models\Attendance;
use Prasso\Church\Models\Availability;
use Prasso\Church\Models\Family;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\Pledge;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Skill;
use Prasso\Church\Models\Transaction;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Messaging\Contracts\MemberContact;

class Member extends ChurchModel implements MemberContact
{
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => MemberCreated::class,
    ];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'birthdate',
        'anniversary',
        'baptism_date',
        'membership_date',
        'membership_status',
        'gender',
        'marital_status',
        'photo_path',
        'notes',
        'family_id',
        'is_head_of_household',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'birthdate' => 'date',
        'anniversary' => 'date',
        'baptism_date' => 'date',
        'membership_date' => 'date',
        'is_head_of_household' => 'boolean',
    ];

    /**
     * Get the family that owns the member.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * Get the skills associated with the member.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'chm_member_skill')
            ->withPivot(['proficiency_level', 'years_experience', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the member's availability.
     */
    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * Get the volunteer positions this member is assigned to.
     */
    public function volunteerPositions()
    {
        return $this->belongsToMany(VolunteerPosition::class, 'chm_volunteer_assignments', 'member_id', 'position_id')
            ->withPivot(['start_date', 'end_date', 'status', 'notes', 'assigned_by', 'approved_by', 'trained_on'])
            ->withTimestamps();
    }

    /**
     * Get the groups this member belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'chm_group_member', 'member_id', 'group_id')
            ->withPivot(['role', 'join_date', 'leave_date', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all transactions for the member.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all pledges for the member.
     */
    public function pledges()
    {
        return $this->hasMany(Pledge::class);
    }
    
    /**
     * Get all prayer requests for the member.
     */
    public function prayerRequests()
    {
        return $this->hasMany(PrayerRequest::class, 'member_id');
    }
    
    /**
     * Get all prayer requests submitted by the member.
     */
    public function submittedPrayerRequests()
    {
        return $this->hasMany(PrayerRequest::class, 'requested_by');
    }

    /**
     * Get the user associated with the member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Get the attendance records for the member.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the full name of the member.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /**
     * MemberContact interface implementation
     */
    public function getMemberId()
    {
        return $this->getKey();
    }

    public function getMemberEmail(): ?string
    {
        return $this->email;
    }

    public function getMemberPhone(): ?string
    {
        // Use attribute accessor if present, else raw property
        return $this->getAttribute('phone');
    }

    public function getMemberDisplayName(): ?string
    {
        // Prefer accessor full_name when available
        return $this->full_name ?: trim("{$this->first_name} {$this->last_name}") ?: null;
    }
}
