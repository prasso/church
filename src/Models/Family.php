<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends ChurchModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'notes',
    ];

    /**
     * Get the members for the family.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get the head of household for the family.
     */
    public function headOfHousehold()
    {
        return $this->members()->where('is_head_of_household', true)->first();
    }

    /**
     * Get the family display name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($head = $this->headOfHousehold()) {
            return $head->last_name . ' Family';
        }
        
        return $this->name ?: 'Unnamed Family';
    }
}
