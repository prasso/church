<?php

namespace Prasso\Church\Models;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;
// Ministry model will be in the same namespace
use RRule\RRule;

class Event extends ChurchModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'event_type_id',
        'location',
        'image_url',
        'recurrence_pattern',
        'recurrence_days',
        'recurrence_interval',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'capacity',
        'requires_registration',
        'registration_deadline',
        'status',
        'created_by',
        'ministry_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'registration_deadline' => 'datetime',
        'recurrence_days' => 'array',
        'metadata' => 'array',
        'requires_registration' => 'boolean',
        'recurrence_interval' => 'integer',
        'capacity' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'draft',
        'recurrence_pattern' => 'none',
        'recurrence_interval' => 1,
        'requires_registration' => false,
    ];

    /**
     * Get all occurrences of this event.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function occurrences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    /**
     * Get the user who created this event.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the ministry this event belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ministry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Get the event type for this event.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    /**
     * Get the attendees for the event.
     */
    public function attendees()
    {
        return $this->hasManyThrough(
            Attendance::class,
            EventOccurrence::class,
            'event_id',
            'event_occurrence_id',
            'id',
            'id'
        );
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('end_date', '>=', now()->toDateString())
                    ->orWhereNull('end_date')
                    ->orderBy('start_date')
                    ->orderBy('start_time');
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->whereHas('eventType', function ($query) use ($type) {
            $query->where('slug', $type);
        });
    }

    /**
     * Check if the event is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->recurrence_pattern !== 'none';
    }

    /**
     * Generate occurrences for a recurring event.
     */
    public function generateOccurrences($endDate = null): void
    {
        if (!$this->isRecurring() || !$this->start_date) {
            return;
        }

        $endDate = $endDate ? Carbon::parse($endDate) : $this->end_date;
        
        // Delete future occurrences that haven't happened yet
        $this->occurrences()
            ->where('date', '>', now()->toDateString())
            ->where('status', 'scheduled')
            ->delete();

        $rrule = $this->getRecurrenceRule();
        $occurrences = $rrule->getOccurrencesBetween(
            Carbon::parse($this->start_date)->startOfDay(),
            $endDate ? $endDate->endOfDay() : now()->addYear()
        );

        foreach ($occurrences as $occurrence) {
            $this->occurrences()->updateOrCreate(
                ['date' => $occurrence->format('Y-m-d')],
                [
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                    'status' => 'scheduled',
                ]
            );
        }
    }

    /**
     * Get the recurrence rule
     * @return \RRule\RRule
     */
    public function getRecurrenceRule(): \RRule\RRule
    {
        $startDate = Carbon::parse($this->start_date);
        $ruleData = [
            'FREQ' => strtoupper($this->recurrence_pattern),
            'INTERVAL' => $this->recurrence_interval,
            'DTSTART' => $startDate->format('Ymd\THis\Z'),
        ];

        if ($this->end_date) {
            $ruleData['UNTIL'] = Carbon::parse($this->end_date)->endOfDay()->format('Ymd\THis\Z');
        }

        if ($this->recurrence_pattern === 'weekly' && !empty($this->recurrence_days)) {
            $ruleData['BYDAY'] = implode(',', array_map(function($day) {
                return ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][$day % 7];
            }, (array)$this->recurrence_days));
        }

        return new \RRule\RRule($ruleData);
    }

    /**
     * Get the next occurrence of this event.
     */
    public function nextOccurrence()
    {
        return $this->occurrences()
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'scheduled')
            ->orderBy('date')
            ->first();
    }

    /**
     * Get the attendance count for this event.
     */
    public function getAttendanceCountAttribute(): int
    {
        return $this->attendees()->count();
    }

    /**
     * Check if the event is full.
     */
    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }
        
        return $this->attendees()->count() >= $this->capacity;
    }

    /**
     * Check if registration is open for this event.
     */
    public function isRegistrationOpen(): bool
    {
        if (!$this->requires_registration) {
            return false;
        }

        if ($this->registration_deadline) {
            return now()->lte($this->registration_deadline);
        }

        return true;
    }
}
