<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Models\User;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'chm_attendance_records';

    protected $fillable = [
        'event_id',
        'member_id',
        'family_id',
        'checked_in_by',
        'check_in_time',
        'check_out_time',
        'status',
        'guest_count',
        'notes',
        'source',
        'metadata',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'guest_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * The event this attendance record is for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(AttendanceEvent::class, 'event_id');
    }

    /**
     * The member who attended (if applicable).
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * The family that attended (if applicable).
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * The user who recorded the attendance.
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'checked_in_by');
    }

    /**
     * Get the duration of the attendance in minutes.
     */
    public function getDurationInMinutesAttribute(): ?int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }
        
        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }

    /**
     * Check if the attendance is currently active (checked in but not out).
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->check_in_time && !$this->check_out_time;
    }

    /**
     * Scope a query to only include records for a specific member.
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope a query to only include records for a specific family.
     */
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    /**
     * Scope a query to only include records within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : $start->copy()->endOfDay();
        
        return $query->whereBetween('check_in_time', [$start, $end]);
    }

    /**
     * Scope a query to only include records for a specific event type.
     */
    public function scopeForEventType($query, $eventTypeId)
    {
        return $query->whereHas('event', function ($q) use ($eventTypeId) {
            $q->where('event_type_id', $eventTypeId);
        });
    }

    /**
     * Check out the attendee if they are checked in.
     */
    public function checkOut($time = null)
    {
        if ($this->check_out_time) {
            return false; // Already checked out
        }

        $this->check_out_time = $time ?? now();
        return $this->save();
    }

    /**
     * Get the status with a human-readable label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'present' => 'Present',
            'late' => 'Late',
            'excused' => 'Excused',
            'absent' => 'Absent',
            'tardy' => 'Tardy',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the attendance type (member, family, or guest).
     */
    public function getAttendanceTypeAttribute(): string
    {
        if ($this->member_id) {
            return 'member';
        }
        
        if ($this->family_id) {
            return 'family';
        }
        
        return 'guest';
    }
}
