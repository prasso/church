<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRun extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'aph_report_runs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'report_id',
        'schedule_id',
        'status',
        'error_message',
        'file_path',
        'parameters',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'parameters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'duration',
        'formatted_started_at',
        'formatted_completed_at',
    ];

    /**
     * The possible status values.
     *
     * @var array
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the report that owns the run.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the schedule that owns the run.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class);
    }

    /**
     * Get the duration of the run in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get the formatted started at attribute.
     */
    public function getFormattedStartedAtAttribute(): ?string
    {
        return $this->started_at ? $this->started_at->format('M d, Y h:i A') : null;
    }

    /**
     * Get the formatted completed at attribute.
     */
    public function getFormattedCompletedAtAttribute(): ?string
    {
        return $this->completed_at ? $this->completed_at->format('M d, Y h:i A') : null;
    }

    /**
     * Mark the run as started.
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark the run as completed.
     */
    public function markAsCompleted(string $filePath = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the run as failed.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Check if the run is still pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the run is currently processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the run has completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the run has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
