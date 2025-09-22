<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportSchedule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_report_schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'report_id',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'recipients',
        'format',
        'is_active',
        'last_run_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    /**
     * Get the report that owns the schedule.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the runs for the schedule.
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class, 'schedule_id');
    }

    /**
     * Get the next run date for the schedule.
     */
    public function getNextRunDate()
    {
        $now = now();
        $time = $this->time ?: '00:00';
        
        switch ($this->frequency) {
            case 'daily':
                $nextRun = $now->copy()->setTimeFromTimeString($time);
                if ($nextRun->lt($now)) {
                    $nextRun->addDay();
                }
                return $nextRun;
                
            case 'weekly':
                $nextRun = $now->copy()
                    ->setTimeFromTimeString($time)
                    ->next(ucfirst($this->day_of_week ?: 'monday'));
                    
                if ($nextRun->lt($now)) {
                    $nextRun->addWeek();
                }
                return $nextRun;
                
            case 'monthly':
                $day = $this->day_of_month ?: 1;
                $nextRun = $now->copy()
                    ->setTimeFromTimeString($time)
                    ->day($day);
                    
                if ($nextRun->lt($now)) {
                    $nextRun->addMonth();
                }
                return $nextRun;
                
            default:
                return null;
        }
    }

    /**
     * Check if the schedule is due to run.
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $lastRun = $this->last_run_at ?: $now->subYear();
        $nextRun = $this->getNextRunDate();

        return $nextRun && $nextRun->lte($now) && $nextRun->gt($lastRun);
    }

    /**
     * Mark the schedule as run.
     */
    public function markAsRun(): void
    {
        $this->update(['last_run_at' => now()]);
    }
}
