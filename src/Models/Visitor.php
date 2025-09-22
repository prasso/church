<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Prasso\Church\Events\VisitorCreated;

class Visitor extends ChurchModel
{
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => VisitorCreated::class,
    ];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'visit_date',
        'how_did_you_hear',
        'interests',
        'notes',
        'follow_up_date',
        'follow_up_notes',
        'status',
        'converted_to_member',
        'converted_at',
        'assigned_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'visit_date' => 'date',
        'follow_up_date' => 'date',
        'converted_at' => 'datetime',
        'interests' => 'array',
        'converted_to_member' => 'boolean',
    ];

    /**
     * The user who is assigned to follow up with this visitor.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'assigned_to');
    }

    /**
     * The member this visitor was converted to, if applicable.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'converted_to_member_id');
    }

    /**
     * Get the full name of the visitor.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Scope a query to only include visitors who need follow-up.
     */
    public function scopeNeedsFollowUp($query)
    {
        return $query->where('status', 'needs_follow_up')
                    ->orWhere(function($q) {
                        $q->where('follow_up_date', '<=', now())
                          ->where('converted_to_member', false);
                    });
    }
}
