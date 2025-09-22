<?php

namespace Prasso\Church\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class Attendance extends ChurchModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_attendances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_occurrence_id',
        'member_id',
        'family_id',
        'recorded_by',
        'check_in_time',
        'check_out_time',
        'status',
        'notes',
        'guest_name',
        'guest_email',
        'guest_phone',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'present',
    ];

    /**
     * Get the event occurrence this attendance is for.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function occurrence(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EventOccurrence::class, 'event_occurrence_id');
    }

    /**
     * Get the member who attended.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the family of the attendee.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function family(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the user who recorded this attendance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recordedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }

    /**
     * Check if the attendance is for a guest.
     */
    public function isGuest(): bool
    {
        return !is_null($this->guest_name);
    }

    /**
     * Get the name of the attendee.
     */
    public function getAttendeeNameAttribute(): string
    {
        if ($this->isGuest()) {
            return $this->guest_name;
        }

        return $this->member ? $this->member->full_name : 'Unknown';
    }

    /**
     * Get the email of the attendee.
     */
    public function getAttendeeEmailAttribute(): ?string
    {
        if ($this->isGuest()) {
            return $this->guest_email;
        }

        return $this->member ? $this->member->email : null;
    }

    /**
     * Get the phone number of the attendee.
     */
    public function getAttendeePhoneAttribute(): ?string
    {
        if ($this->isGuest()) {
            return $this->guest_phone;
        }

        return $this->member ? $this->member->phone : null;
    }

    /**
     * Check if the attendee is currently checked in.
     */
    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_time) && is_null($this->check_out_time);
    }

    /**
     * Check if the attendee has checked out.
     */
    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_time);
    }

    /**
     * Check in the attendee.
     */
    public function checkIn(User $recordedBy = null): bool
    {
        $this->check_in_time = now();
        $this->check_out_time = null;
        $this->status = 'present';
        
        if ($recordedBy) {
            $this->recorded_by = $recordedBy->id;
        }
        
        return $this->save();
    }

    /**
     * Check out the attendee.
     */
    public function checkOut(User $recordedBy = null): bool
    {
        if ($this->isCheckedOut()) {
            return true; // Already checked out
        }
        
        $this->check_out_time = now();
        
        if ($recordedBy) {
            $this->recorded_by = $recordedBy->id;
        }
        
        return $this->save();
    }

    /**
     * Mark the attendee as absent.
     */
    public function markAbsent(string $notes = null, User $recordedBy = null): bool
    {
        $this->status = 'absent';
        $this->notes = $notes;
        
        if ($recordedBy) {
            $this->recorded_by = $recordedBy->id;
        }
        
        return $this->save();
    }

    /**
     * Mark the attendee as excused.
     */
    public function markExcused(string $reason = null, User $recordedBy = null): bool
    {
        $this->status = 'excused';
        $this->notes = $reason;
        
        if ($recordedBy) {
            $this->recorded_by = $recordedBy->id;
        }
        
        return $this->save();
    }

    /**
     * Get the duration of the attendance in minutes.
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }
        
        return $this->check_out_time->diffInMinutes($this->check_in_time);
    }

    /**
     * Scope a query to only include check-ins for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: $startDate;
        
        return $query->whereHas('occurrence', function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        });
    }

    /**
     * Scope a query to only include check-ins for a specific member.
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope a query to only include check-ins for a specific family.
     */
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }
}
