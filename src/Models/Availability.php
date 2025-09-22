<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class Availability extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_availabilities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'member_id',
        'start_time',
        'end_time',
        'day_of_week', // 0-6 (Sunday-Saturday) or null for specific dates
        'recurring',
        'timezone',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'recurring' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'recurring' => true,
    ];

    /**
     * Get the member that owns the availability.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Scope a query to only include recurring availabilities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query)
    {
        return $query->where('recurring', true);
    }

    /**
     * Scope a query to only include one-time availabilities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOneTime($query)
    {
        return $query->where('recurring', false);
    }

    /**
     * Scope a query to only include availabilities for a specific day of week.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $dayOfWeek  0-6 (Sunday-Saturday)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope a query to only include availabilities that overlap with the given time range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Carbon\Carbon  $start
     * @param  \Carbon\Carbon  $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverlapsWith($query, $start, $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->where(function ($q) use ($start, $end) {
                // Check if the availability starts within the range
                $q->where('start_time', '>=', $start)
                  ->where('start_time', '<', $end);
            })->orWhere(function ($q) use ($start, $end) {
                // Check if the availability ends within the range
                $q->where('end_time', '>', $start)
                  ->where('end_time', '<=', $end);
            })->orWhere(function ($q) use ($start, $end) {
                // Check if the availability spans the entire range
                $q->where('start_time', '<=', $start)
                  ->where('end_time', '>=', $end);
            });
        });
    }
}
