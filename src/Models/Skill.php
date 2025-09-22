<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class Skill extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_skills';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'category',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
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
     * Get all members that have this skill.
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'chm_member_skill')
            ->withPivot(['proficiency_level', 'years_experience', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all positions that require this skill.
     */
    public function positions()
    {
        return $this->belongsToMany(VolunteerPosition::class, 'chm_position_skill')
            ->withPivot(['is_required', 'proficiency_required'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active skills.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
