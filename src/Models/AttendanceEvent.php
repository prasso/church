<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class AttendanceEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chm_attendance_events';

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'location_id',
        'event_type_id',
        'ministry_id',
        'group_id',
        'expected_attendance',
        'notes',
        'requires_check_in',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_details',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'requires_check_in' => 'boolean',
        'is_recurring' => 'boolean',
        'recurrence_details' => 'array',
    ];

    /**
     * Get the location where the event takes place.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the event type.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the ministry that organizes the event.
     */
    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Get the group associated with the event.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the attendance records for the event.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'event_id');
    }

    /**
     * Get the user who created the event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the event.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include events on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        $date = Carbon::parse($date);
        return $query->whereDate('start_time', $date);
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query, $days = 30)
    {
        return $query->where('start_time', '>=', now())
                    ->where('start_time', '<=', now()->addDays($days));
    }

    /**
     * Scope a query to only include past events.
     */
    public function scopePast($query, $days = 30)
    {
        return $query->where('start_time', '<', now())
                    ->where('start_time', '>=', now()->subDays($days));
    }

    /**
     * Get the attendance rate for the event.
     */
    public function getAttendanceRateAttribute()
    {
        if (!$this->expected_attendance) {
            return null;
        }
        
        $attended = $this->attendanceRecords()->count();
        return ($attended / $this->expected_attendance) * 100;
    }

    /**
     * Check if the event is happening now.
     */
    public function getIsHappeningNowAttribute(): bool
    {
        $now = now();
        return $this->start_time <= $now && 
               ($this->end_time === null || $this->end_time >= $now);
    }

    /**
     * Generate recurring events based on the recurrence pattern.
     */
    public function generateRecurringEvents($endDate = null)
    {
        if (!$this->is_recurring || !$this->recurrence_pattern) {
            return collect();
        }

        $endDate = $endDate ? Carbon::parse($endDate) : now()->addYear();
        $events = collect();
        $current = Carbon::parse($this->start_time);
        $originalId = $this->id;

        // Remove the ID to allow saving as new records
        $attributes = $this->getAttributes();
        unset($attributes['id']);

        while ($current <= $endDate) {
            if ($current->gt($this->start_time)) { // Skip the original event
                $event = new self($attributes);
                $event->start_time = $current->copy();
                $event->end_time = $this->end_time ? 
                    $current->copy()->addSeconds($this->start_time->diffInSeconds($this->end_time)) : 
                    null;
                $event->save();
                $events->push($event);
            }

            // Move to next occurrence based on pattern
            switch ($this->recurrence_pattern) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'biweekly':
                    $current->addWeeks(2);
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                default:
                    // Custom pattern in recurrence_details
                    if ($this->recurrence_details) {
                        $current->add($this->recurrence_details['interval'] ?? '1 day');
                    } else {
                        $current->addDay();
                    }
                    break;
            }
        }

        return $events;
    }
}
