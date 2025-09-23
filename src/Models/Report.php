<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'report_type',
        'filters',
        'columns',
        'settings',
        'is_public',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'settings' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_created_at',
        'formatted_updated_at',
    ];

    /**
     * Get the creator of the report.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated the report.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the schedules for the report.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    /**
     * Get the runs for the report.
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class)->latest();
    }

    /**
     * Get the last run of the report.
     */
    public function lastRun()
    {
        return $this->hasOne(ReportRun::class)->latest()->limit(1);
    }

    /**
     * Get the formatted created at attribute.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('M d, Y h:i A');
    }

    /**
     * Get the formatted updated at attribute.
     */
    public function getFormattedUpdatedAtAttribute(): ?string
    {
        return $this->updated_at ? $this->updated_at->format('M d, Y h:i A') : null;
    }

    /**
     * Scope a query to only include public reports.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include reports for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('created_by', $userId)
                    ->orWhere('is_public', true);
    }

    /**
     * Get the default columns for the report type.
     */
    public function getDefaultColumns(): array
    {
        $defaults = [
            'attendance' => ['date', 'event_name', 'total_attendees', 'first_time_guests'],
            'membership' => ['name', 'join_date', 'status', 'last_attended'],
            'giving' => ['date', 'donor_name', 'amount', 'payment_method'],
        ];

        return $defaults[$this->report_type] ?? [];
    }

    /**
     * Get the available filters for the report type.
     */
    public function getAvailableFilters(): array
    {
        $filters = [
            'attendance' => [
                'date_range' => ['type' => 'date_range'],
                'ministry_id' => ['type' => 'select', 'label' => 'Ministry'],
                'group_id' => ['type' => 'select', 'label' => 'Group'],
                'event_type' => ['type' => 'select', 'label' => 'Event Type'],
            ],
            'membership' => [
                'status' => ['type' => 'select', 'label' => 'Status'],
                'join_date' => ['type' => 'date_range', 'label' => 'Join Date'],
                'ministry_id' => ['type' => 'select', 'label' => 'Ministry'],
                'group_id' => ['type' => 'select', 'label' => 'Group'],
            ],
            'giving' => [
                'date_range' => ['type' => 'date_range'],
                'donor_type' => ['type' => 'select', 'label' => 'Donor Type'],
                'payment_method' => ['type' => 'select', 'label' => 'Payment Method'],
                'campaign_id' => ['type' => 'select', 'label' => 'Campaign'],
            ],
        ];

        return $filters[$this->report_type] ?? [];
    }
}
