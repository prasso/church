<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    protected $table = 'chm_attendance_summaries';

    protected $fillable = [
        'summary_date',
        'event_id',
        'ministry_id',
        'group_id',
        'total_attended',
        'total_members',
        'total_guests',
        'total_absent',
        'attendance_rate',
        'demographics',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'total_attended' => 'integer',
        'total_members' => 'integer',
        'total_guests' => 'integer',
        'total_absent' => 'integer',
        'attendance_rate' => 'decimal:2',
        'demographics' => 'array',
    ];

    /**
     * Get the event this summary is for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(AttendanceEvent::class, 'event_id');
    }

    /**
     * Get the ministry this summary is for.
     */
    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    /**
     * Get the group this summary is for.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AttendanceGroup::class, 'group_id');
    }

    /**
     * Generate a summary for a specific date range.
     */
    public static function generateForDateRange($startDate, $endDate, $options = [])
    {
        $eventId = $options['event_id'] ?? null;
        $ministryId = $options['ministry_id'] ?? null;
        $groupId = $options['group_id'] ?? null;
        
        $query = AttendanceRecord::query()
            ->selectRaw('COUNT(DISTINCT member_id) as member_count, COUNT(*) as total_records, SUM(guest_count) as guest_count')
            ->whereBetween('check_in_time', [$startDate, $endDate]);
        
        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        
        if ($ministryId) {
            $query->whereHas('event', function($q) use ($ministryId) {
                $q->where('ministry_id', $ministryId);
            });
        }
        
        $attendance = $query->first();
        
        // Get total members for the group/ministry
        $totalMembers = 0;
        if ($groupId) {
            $group = AttendanceGroup::find($groupId);
            $totalMembers = $group ? $group->getAllMembers()->count() : 0;
        } elseif ($ministryId) {
            $totalMembers = Member::where('ministry_id', $ministryId)->count();
        } else {
            $totalMembers = Member::count();
        }
        
        $attended = $attendance->member_count ?? 0;
        $guests = $attendance->guest_count ?? 0;
        $absent = max(0, $totalMembers - $attended);
        $attendanceRate = $totalMembers > 0 ? ($attended / $totalMembers) * 100 : 0;
        
        // Get demographics
        $demographics = [
            'age_groups' => self::getAgeGroupDemographics($startDate, $endDate, $options),
            'gender' => self::getGenderDemographics($startDate, $endDate, $options),
            'membership_status' => self::getMembershipStatusDemographics($startDate, $endDate, $options),
        ];
        
        // Create or update the summary
        return self::updateOrCreate(
            [
                'summary_date' => $startDate,
                'event_id' => $eventId,
                'ministry_id' => $ministryId,
                'group_id' => $groupId,
            ],
            [
                'total_attended' => $attended,
                'total_members' => $totalMembers,
                'total_guests' => $guests,
                'total_absent' => $absent,
                'attendance_rate' => $attendanceRate,
                'demographics' => $demographics,
            ]
        );
    }
    
    /**
     * Get age group demographics for the given criteria.
     */
    protected static function getAgeGroupDemographics($startDate, $endDate, $options)
    {
        $query = AttendanceRecord::query()
            ->join('chm_members', 'chm_attendance_records.member_id', '=', 'chm_members.id')
            ->selectRaw('COUNT(*) as count, 
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE()) < 18 THEN 1 ELSE 0 END) as under_18,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE()) BETWEEN 18 AND 25 THEN 1 ELSE 0 END) as age_18_25,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE()) BETWEEN 26 AND 35 THEN 1 ELSE 0 END) as age_26_35,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE()) BETWEEN 36 AND 50 THEN 1 ELSE 0 END) as age_36_50,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE()) BETWEEN 51 AND 65 THEN 1 ELSE 0 END) as age_51_65,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE()) > 65 THEN 1 ELSE 0 END) as over_65'
            )
            ->whereBetween('chm_attendance_records.check_in_time', [$startDate, $endDate])
            ->whereNotNull('chm_members.date_of_birth');
        
        self::applyQueryFilters($query, $options);
        
        $result = $query->first();
        
        return [
            'under_18' => (int) ($result->under_18 ?? 0),
            'age_18_25' => (int) ($result->age_18_25 ?? 0),
            'age_26_35' => (int) ($result->age_26_35 ?? 0),
            'age_36_50' => (int) ($result->age_36_50 ?? 0),
            'age_51_65' => (int) ($result->age_51_65 ?? 0),
            'over_65' => (int) ($result->over_65 ?? 0),
        ];
    }
    
    /**
     * Get gender demographics for the given criteria.
     */
    protected static function getGenderDemographics($startDate, $endDate, $options)
    {
        $query = AttendanceRecord::query()
            ->join('chm_members', 'chm_attendance_records.member_id', '=', 'chm_members.id')
            ->selectRaw('gender, COUNT(*) as count')
            ->whereBetween('chm_attendance_records.check_in_time', [$startDate, $endDate])
            ->whereIn('gender', ['male', 'female', 'other'])
            ->groupBy('gender');
        
        self::applyQueryFilters($query, $options);
        
        $results = $query->pluck('count', 'gender');
        
        return [
            'male' => (int) ($results['male'] ?? 0),
            'female' => (int) ($results['female'] ?? 0),
            'other' => (int) ($results['other'] ?? 0),
        ];
    }
    
    /**
     * Get membership status demographics for the given criteria.
     */
    protected static function getMembershipStatusDemographics($startDate, $endDate, $options)
    {
        $query = AttendanceRecord::query()
            ->join('chm_members', 'chm_attendance_records.member_id', '=', 'chm_members.id')
            ->selectRaw('membership_status, COUNT(*) as count')
            ->whereBetween('chm_attendance_records.check_in_time', [$startDate, $endDate])
            ->groupBy('membership_status');
        
        self::applyQueryFilters($query, $options);
        
        return $query->pluck('count', 'membership_status')->toArray();
    }
    
    /**
     * Apply filters to the query based on the provided options.
     */
    protected static function applyQueryFilters($query, $options)
    {
        if (!empty($options['event_id'])) {
            $query->where('chm_attendance_records.event_id', $options['event_id']);
        }
        
        if (!empty($options['ministry_id'])) {
            $query->whereHas('event', function($q) use ($options) {
                $q->where('ministry_id', $options['ministry_id']);
            });
        }
        
        if (!empty($options['group_id'])) {
            $group = AttendanceGroup::find($options['group_id']);
            if ($group) {
                $memberIds = $group->getAllMembers()->pluck('id');
                $query->whereIn('chm_attendance_records.member_id', $memberIds);
            }
        }
    }
    
    /**
     * Get the attendance trend over time.
     */
    public static function getTrend($period = 'month', $limit = 12, $options = [])
    {
        $query = self::query()
            ->select(
                'summary_date',
                'total_attended',
                'total_members',
                'total_guests',
                'attendance_rate'
            )
            ->orderBy('summary_date', 'asc')
            ->limit($limit);
        
        if (!empty($options['event_id'])) {
            $query->where('event_id', $options['event_id']);
        }
        
        if (!empty($options['ministry_id'])) {
            $query->where('ministry_id', $options['ministry_id']);
        }
        
        if (!empty($options['group_id'])) {
            $query->where('group_id', $options['group_id']);
        }
        
        // Group by the appropriate time period
        if ($period === 'week') {
            $query->selectRaw('YEARWEEK(summary_date, 1) as period')
                 ->groupBy('period');
        } elseif ($period === 'month') {
            $query->selectRaw('DATE_FORMAT(summary_date, "%Y-%m") as period')
                 ->groupBy('period');
        } elseif ($period === 'quarter') {
            $query->selectRaw('CONCAT(YEAR(summary_date), "-Q", QUARTER(summary_date)) as period')
                 ->groupBy('period');
        } else {
            $query->selectRaw('YEAR(summary_date) as period')
                 ->groupBy('period');
        }
        
        return $query->get();
    }
}
