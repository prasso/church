<?php

namespace Prasso\Church\Services;

use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\AttendanceGroup;
use Prasso\Church\Models\AttendanceSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Update the attendance summary for a specific event.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return \Prasso\Church\Models\AttendanceSummary
     */
    public function updateEventSummary(AttendanceEvent $event)
    {
        $startOfDay = $event->start_time->copy()->startOfDay();
        $endOfDay = $event->start_time->copy()->endOfDay();
        
        return $this->generateSummaryForDateRange(
            $startOfDay,
            $endOfDay,
            ['event_id' => $event->id]
        );
    }
    
    /**
     * Update the attendance summary for a ministry.
     *
     * @param  int  $ministryId
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return \Prasso\Church\Models\AttendanceSummary
     */
    public function updateMinistrySummary($ministryId, Carbon $startDate, Carbon $endDate)
    {
        return $this->generateSummaryForDateRange(
            $startDate,
            $endDate,
            ['ministry_id' => $ministryId]
        );
    }
    
    /**
     * Update the attendance summary for a group.
     *
     * @param  int  $groupId
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return \Prasso\Church\Models\AttendanceSummary
     */
    public function updateGroupSummary($groupId, Carbon $startDate, Carbon $endDate)
    {
        return $this->generateSummaryForDateRange(
            $startDate,
            $endDate,
            ['group_id' => $groupId]
        );
    }
    
    /**
     * Generate attendance summary for a date range with optional filters.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  array  $filters
     * @return \Prasso\Church\Models\AttendanceSummary
     */
    public function generateSummaryForDateRange(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        // Build query for attendance records
        $query = AttendanceRecord::query()
            ->join('chm_attendance_events', 'chm_attendance_records.event_id', '=', 'chm_attendance_events.id')
            ->whereBetween('chm_attendance_events.start_time', [$startDate, $endDate]);
        
        // Apply filters
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where("chm_attendance_events.{$field}", $value);
            }
        }
        
        // Get attendance counts by status
        $statusCounts = (clone $query)
            ->select('chm_attendance_records.status', DB::raw('COUNT(*) as count'))
            ->groupBy('chm_attendance_records.status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Get total unique attendees (members and families)
        $attendeeCount = (clone $query)
            ->select(DB::raw('COUNT(DISTINCT COALESCE(member_id, family_id)) as count'))
            ->value('count') ?? 0;
        
        // Get guest count
        $guestCount = (clone $query)->sum('guest_count');
        
        // Get event count
        $eventQuery = AttendanceEvent::query()
            ->whereBetween('start_time', [$startDate, $endDate]);
            
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $eventQuery->where($field, $value);
            }
        }
        
        $eventCount = $eventQuery->count();
        
        // Prepare summary data
        $summaryData = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_attendees' => $attendeeCount,
            'total_guests' => $guestCount,
            'total_events' => $eventCount,
            'present_count' => $statusCounts['present'] ?? 0,
            'late_count' => $statusCounts['late'] ?? 0,
            'excused_count' => $statusCounts['excused'] ?? 0,
            'absent_count' => $statusCounts['absent'] ?? 0,
            'tardy_count' => $statusCounts['tardy'] ?? 0,
            'demographics' => $this->getDemographicData($query),
        ];
        
        // Add filter fields to summary data
        $summaryData = array_merge($summaryData, $filters);
        
        // Find or create the summary
        $summary = AttendanceSummary::firstOrNew([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'event_id' => $filters['event_id'] ?? null,
            'ministry_id' => $filters['ministry_id'] ?? null,
            'group_id' => $filters['group_id'] ?? null,
        ]);
        
        // Update summary data
        $summary->fill($summaryData);
        $summary->save();
        
        return $summary;
    }
    
    /**
     * Get demographic data for attendance records.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function getDemographicData($query)
    {
        // Clone the query to avoid modifying the original
        $demographicsQuery = (clone $query)
            ->join('chm_members', function($join) {
                $join->on('chm_attendance_records.member_id', '=', 'chm_members.id')
                    ->whereNull('chm_members.deleted_at');
            })
            ->select([
                DB::raw('COUNT(DISTINCT chm_members.id) as total_members'),
                DB::raw('SUM(CASE WHEN chm_members.gender = "male" THEN 1 ELSE 0 END) as male_count'),
                DB::raw('SUM(CASE WHEN chm_members.gender = "female" THEN 1 ELSE 0 END) as female_count'),
                DB::raw('AVG(TIMESTAMPDIFF(YEAR, chm_members.date_of_birth, CURDATE())) as avg_age'),
                DB::raw('COUNT(DISTINCT CASE WHEN chm_members.membership_status = "member" THEN chm_members.id END) as member_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN chm_members.membership_status = "regular_attendee" THEN chm_members.id END) as regular_attendee_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN chm_members.membership_status = "visitor" THEN chm_members.id END) as visitor_count'),
            ])
            ->first();
        
        return [
            'total_members' => $demographicsQuery->total_members ?? 0,
            'gender' => [
                'male' => $demographicsQuery->male_count ?? 0,
                'female' => $demographicsQuery->female_count ?? 0,
                'other' => ($demographicsQuery->total_members ?? 0) - (($demographicsQuery->male_count ?? 0) + ($demographicsQuery->female_count ?? 0)),
            ],
            'average_age' => round($demographicsQuery->avg_age ?? 0, 1),
            'membership_status' => [
                'member' => $demographicsQuery->member_count ?? 0,
                'regular_attendee' => $demographicsQuery->regular_attendee_count ?? 0,
                'visitor' => $demographicsQuery->visitor_count ?? 0,
            ],
        ];
    }
    
    /**
     * Get attendance trend data.
     *
     * @param  string  $period  day, week, month, quarter, year
     * @param  int  $limit  Number of periods to return
     * @param  array  $filters  Additional filters
     * @return \Illuminate\Support\Collection
     */
    public function getTrend($period = 'month', $limit = 12, array $filters = [])
    {
        $endDate = now();
        $startDate = $this->getStartDateForPeriod($period, $limit, $endDate);
        
        $query = AttendanceRecord::query()
            ->join('chm_attendance_events', 'chm_attendance_records.event_id', '=', 'chm_attendance_events.id')
            ->whereBetween('chm_attendance_events.start_time', [$startDate, $endDate]);
        
        // Apply filters
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where("chm_attendance_events.{$field}", $value);
            }
        }
        
        // Group by period and count distinct attendees
        $groupBy = $this->getGroupByClause($period);
        
        $results = $query->select([
                DB::raw($groupBy . ' as period'),
                DB::raw('COUNT(DISTINCT COALESCE(member_id, family_id)) as attendee_count'),
                DB::raw('SUM(guest_count) as guest_count'),
                DB::raw('COUNT(DISTINCT event_id) as event_count'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        
        return $results;
    }
    
    /**
     * Get the start date for a given period.
     *
     * @param  string  $period
     * @param  int  $limit
     * @param  \Carbon\Carbon  $endDate
     * @return \Carbon\Carbon
     */
    protected function getStartDateForPeriod($period, $limit, $endDate)
    {
        $method = 'sub' . ucfirst($period) . 's';
        
        if (method_exists($endDate, $method)) {
            return $endDate->copy()->$method($limit);
        }
        
        // Default to months if period is invalid
        return $endDate->copy()->subMonths($limit);
    }
    
    /**
     * Get the SQL group by clause for a period.
     *
     * @param  string  $period
     * @return string
     */
    protected function getGroupByClause($period)
    {
        switch ($period) {
            case 'day':
                return 'DATE(chm_attendance_events.start_time)';
            case 'week':
                return 'YEARWEEK(chm_attendance_events.start_time, 1)';
            case 'month':
                return 'DATE_FORMAT(chm_attendance_events.start_time, "%Y-%m")';
            case 'quarter':
                return 'CONCAT(YEAR(chm_attendance_events.start_time), "-Q", QUARTER(chm_attendance_events.start_time))';
            case 'year':
                return 'YEAR(chm_attendance_events.start_time)';
            default:
                return 'DATE_FORMAT(chm_attendance_events.start_time, "%Y-%m")';
        }
    }
}
