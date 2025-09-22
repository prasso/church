<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ministry extends ChurchModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_ministries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'leader_id',
        'parent_id',
        'is_active',
        'meeting_schedule',
        'meeting_location',
        'contact_email',
        'contact_phone',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the events associated with this ministry.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the parent ministry if this is a sub-ministry.
     */
    public function parent()
    {
        return $this->belongsTo(Ministry::class, 'parent_id');
    }

    /**
     * Get the child ministries.
     */
    public function children()
    {
        return $this->hasMany(Ministry::class, 'parent_id');
    }

    /**
     * Get the leader of this ministry.
     */
    public function leader()
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    /**
     * Get all members involved in this ministry.
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'chm_ministry_member', 'ministry_id', 'member_id')
            ->withPivot('role', 'start_date', 'end_date', 'is_active')
            ->withTimestamps();
    }
}
