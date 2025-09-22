<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class VolunteerPosition extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_volunteer_positions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'ministry_id',
        'group_id',
        'skills_required',
        'time_commitment',
        'location',
        'is_active',
        'max_volunteers',
        'start_date',
        'end_date',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'skills_required' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the ministry that owns the volunteer position.
     */
    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Get the group that owns the volunteer position.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get all volunteers for this position.
     */
    public function volunteers()
    {
        return $this->hasMany(VolunteerAssignment::class, 'position_id');
    }

    /**
     * Get active volunteers for this position.
     */
    public function activeVolunteers()
    {
        return $this->volunteers()->where('status', 'active');
    }

    /**
     * Check if the position is currently open for new volunteers.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_volunteers && $this->activeVolunteers()->count() >= $this->max_volunteers) {
            return false;
        }

        $now = now();
        if ($this->start_date && $this->start_date->gt($now)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lt($now)) {
            return false;
        }

        return true;
    }
}
