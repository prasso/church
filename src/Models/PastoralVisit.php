<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class PastoralVisit extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_pastoral_visits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'purpose',
        'scheduled_for',
        'started_at',
        'ended_at',
        'duration_minutes',
        'location_type',
        'location_details',
        'member_id',
        'family_id',
        'assigned_to',
        'status',
        'notes',
        'follow_up_actions',
        'follow_up_date',
        'spiritual_needs',
        'outcome_summary',
        'is_confidential',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'follow_up_date' => 'datetime',
        'duration_minutes' => 'integer',
        'is_confidential' => 'boolean',
        'spiritual_needs' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'scheduled',
        'is_confidential' => false,
    ];

    /**
     * Get the member associated with the visit.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the family associated with the visit.
     */
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the staff member assigned to the visit.
     */
    public function assignedTo()
    {
        return $this->belongsTo(Member::class, 'assigned_to');
    }

    /**
     * Scope a query to only include upcoming visits.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_for', '>=', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_for');
    }

    /**
     * Scope a query to only include completed visits.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')
                    ->orderBy('scheduled_for', 'desc');
    }

    /**
     * Scope a query to only include visits for a specific staff member.
     */
    public function scopeForStaff($query, $staffId)
    {
        return $query->where('assigned_to', $staffId);
    }

    /**
     * Scope a query to only include visits for a specific member.
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope a query to only include visits for a specific family.
     */
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    /**
     * Mark the visit as started.
     */
    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    /**
     * Mark the visit as completed.
     */
    public function markAsCompleted($notes = null, $outcomeSummary = null)
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'duration_minutes' => $this->started_at ? now()->diffInMinutes($this->started_at) : 0,
            'notes' => $notes ?? $this->notes,
            'outcome_summary' => $outcomeSummary ?? $this->outcome_summary,
        ]);
    }

    /**
     * Add a follow-up action.
     */
    public function addFollowUp($action, $date = null)
    {
        $this->update([
            'follow_up_actions' => $this->follow_up_actions . "\n- {$action}",
            'follow_up_date' => $date ?? $this->follow_up_date,
        ]);
    }
}
