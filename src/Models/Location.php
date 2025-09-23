<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_locations';

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
        'zip',
        'country',
        'capacity',
        'description',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the events that take place at this location.
     */
    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class);
    }

    /**
     * Get the formatted address.
     *
     * @return string
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = [];

        if (!empty($this->address)) {
            $parts[] = $this->address;
        }

        $cityStateZip = [];
        if (!empty($this->city)) {
            $cityStateZip[] = $this->city;
        }
        
        if (!empty($this->state)) {
            $cityStateZip[] = $this->state;
        }
        
        if (!empty($this->zip)) {
            $cityStateZip[] = $this->zip;
        }
        
        if (!empty($cityStateZip)) {
            $parts[] = implode(', ', $cityStateZip);
        }
        
        if (!empty($this->country)) {
            $parts[] = $this->country;
        }

        return implode("\n", $parts);
    }
}
