<?php

namespace Prasso\Church\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class EventOccurrence extends ChurchModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_event_occurrences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
        'location_override',
        'status',
        'cancellation_reason',
        'attendance_count',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'array',
        'attendance_count' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'scheduled',
    ];

    /**
     * Get the event this occurrence belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get all attendance records for this occurrence.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class, 'event_occurrence_id');
    }

    /**
     * Get the start datetime of the occurrence.
     */
    public function getStartDateTimeAttribute()
    {
        return Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time->format('H:i:s'));
    }

    /**
     * Get the end datetime of the occurrence.
     */
    public function getEndDateTimeAttribute()
    {
        if (!$this->end_time) {
            return null;
        }
        
        // If the end time is before the start time, assume it's on the next day
        $endDate = $this->date;
        if ($this->end_time->lt($this->start_time)) {
            $endDate = $endDate->copy()->addDay();
        }
        
        return Carbon::parse($endDate->format('Y-m-d') . ' ' . $this->end_time->format('H:i:s'));
    }

    /**
     * Check if the occurrence is in the past.
     */
    public function isPast(): bool
    {
        return $this->endDateTime && $this->endDateTime->isPast();
    }

    /**
     * Check if the occurrence is happening now.
     */
    public function isHappeningNow(): bool
    {
        $now = now();
        return $this->startDateTime->lte($now) && 
               (!$this->endDateTime || $this->endDateTime->gte($now));
    }

    /**
     * Check if the occurrence is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->startDateTime->isFuture();
    }

    /**
     * Check if the occurrence is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Cancel the occurrence.
     */
    public function cancel(?string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        
        // Notify attendees of cancellation
        // TODO: Implement notification system
        
        return $this->save();
    }

    /**
     * Get the location for this occurrence.
     */
    public function getLocationAttribute(): ?string
    {
        return $this->location_override ?? $this->event->location;
    }

    /**
     * Get the title for this occurrence.
     */
    public function getTitleAttribute(): string
    {
        return $this->event->title;
    }

    /**
     * Get the description for this occurrence.
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->event->description;
    }

    /**
     * Get the type of the parent event.
     */
    public function getTypeAttribute(): string
    {
        return $this->event->type;
    }

    /**
     * Get the image URL for this occurrence.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->event->image_url;
    }

    /**
     * Get the capacity for this occurrence.
     */
    public function getCapacityAttribute(): ?int
    {
        return $this->event->capacity;
    }

    /**
     * Check if this occurrence is full.
     */
    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }
        
        return $this->attendances()->count() >= $this->capacity;
    }

    /**
     * Get the remaining capacity for this occurrence.
     */
    public function getRemainingCapacityAttribute(): ?int
    {
        if (!$this->capacity) {
            return null;
        }
        
        return max(0, $this->capacity - $this->attendances()->count());
    }

    /**
     * Record attendance for a member.
     */
    public function recordAttendance($memberId, array $attributes = [])
    {
        return $this->attendances()->updateOrCreate(
            ['member_id' => $memberId],
            array_merge($attributes, [
                'status' => 'present',
                'check_in_time' => now(),
            ])
        );
    }

    /**
     * Record attendance for a guest.
     */
    public function recordGuestAttendance(array $attributes)
    {
        return $this->attendances()->create(array_merge($attributes, [
            'status' => 'present',
            'check_in_time' => now(),
        ]));
    }
}
