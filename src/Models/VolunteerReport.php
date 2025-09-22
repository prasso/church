<?php

namespace Prasso\Church\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VolunteerReport
{
    /**
     * Get volunteer assignment statistics.
     *
     * @param  int  $positionId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Support\Collection
     */
    public static function getAssignmentStats($positionId = null, $startDate = null, $endDate = null)
    {
        $query = DB::table('chm_volunteer_assignments')
            ->join('chm_volunteer_positions', 'chm_volunteer_assignments.position_id', '=', 'chm_volunteer_positions.id')
            ->join('chm_members', 'chm_volunteer_assignments.member_id', '=', 'chm_members.id')
            ->select(
                'chm_volunteer_positions.id as position_id',
                'chm_volunteer_positions.title as position_title',
                DB::raw('COUNT(DISTINCT chm_volunteer_assignments.member_id) as total_volunteers'),
                DB::raw('SUM(CASE WHEN chm_volunteer_assignments.status = "active" THEN 1 ELSE 0 END) as active_volunteers'),
                DB::raw('AVG(DATEDIFF(NOW(), chm_volunteer_assignments.start_date) / 30) as avg_months_serving')
            )
            ->groupBy('chm_volunteer_positions.id', 'chm_volunteer_positions.title');
            
        if ($positionId) {
            $query->where('chm_volunteer_positions.id', $positionId);
        }
        
        if ($startDate) {
            $query->whereDate('chm_volunteer_assignments.start_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('chm_volunteer_assignments.start_date', '<=', $endDate);
        }
        
        return $query->get();
    }
    
    /**
     * Get volunteer hours by position.
     *
     * @param  int  $positionId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Support\Collection
     */
    public static function getHoursByPosition($positionId = null, $startDate = null, $endDate = null)
    {
        $query = DB::table('chm_volunteer_hours')
            ->join('chm_volunteer_assignments', 'chm_volunteer_hours.assignment_id', '=', 'chm_volunteer_assignments.id')
            ->join('chm_volunteer_positions', 'chm_volunteer_assignments.position_id', '=', 'chm_volunteer_positions.id')
            ->select(
                'chm_volunteer_positions.id as position_id',
                'chm_volunteer_positions.title as position_title',
                DB::raw('SUM(chm_volunteer_hours.hours) as total_hours'),
                DB::raw('COUNT(DISTINCT chm_volunteer_hours.volunteer_id) as unique_volunteers'),
                DB::raw('SUM(chm_volunteer_hours.hours) / GREATEST(COUNT(DISTINCT chm_volunteer_hours.volunteer_id), 1) as avg_hours_per_volunteer')
            )
            ->groupBy('chm_volunteer_positions.id', 'chm_volunteer_positions.title');
            
        if ($positionId) {
            $query->where('chm_volunteer_positions.id', $positionId);
        }
        
        if ($startDate) {
            $query->whereDate('chm_volunteer_hours.date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('chm_volunteer_hours.date', '<=', $endDate);
        }
        
        return $query->get();
    }
    
    /**
     * Get volunteer hours over time.
     *
     * @param  int  $positionId
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  string  $interval  (day, week, month, year)
     * @return \Illuminate\Support\Collection
     */
    public static function getHoursOverTime($positionId = null, $startDate = null, $endDate = null, $interval = 'month')
    {
        $startDate = $startDate ?: now()->subYear()->format('Y-m-d');
        $endDate = $endDate ?: now()->format('Y-m-d');
        
        $dateFormat = [
            'day' => '%Y-%m-%d',
            'week' => '%x-%v',
            'month' => '%Y-%m',
            'year' => '%Y',
        ][$interval] ?? '%Y-%m';
        
        $query = DB::table('chm_volunteer_hours')
            ->join('chm_volunteer_assignments', 'chm_volunteer_hours.assignment_id', '=', 'chm_volunteer_assignments.id')
            ->select(
                DB::raw("DATE_FORMAT(chm_volunteer_hours.date, '{$dateFormat}') as period"),
                DB::raw('SUM(chm_volunteer_hours.hours) as total_hours'),
                DB::raw('COUNT(DISTINCT chm_volunteer_hours.volunteer_id) as unique_volunteers')
            )
            ->whereDate('chm_volunteer_hours.date', '>=', $startDate)
            ->whereDate('chm_volunteer_hours.date', '<=', $endDate)
            ->groupBy('period')
            ->orderBy('period');
            
        if ($positionId) {
            $query->where('chm_volunteer_assignments.position_id', $positionId);
        }
        
        return $query->get();
    }
    
    /**
     * Get volunteer demographics.
     *
     * @param  int  $positionId
     * @return array
     */
    public static function getVolunteerDemographics($positionId = null)
    {
        $query = DB::table('chm_volunteer_assignments')
            ->join('chm_members', 'chm_volunteer_assignments.member_id', '=', 'chm_members.id')
            ->select(
                DB::raw('COUNT(*) as count'),
                'chm_members.gender',
                DB::raw('FLOOR(DATEDIFF(NOW(), chm_members.birthdate) / 365.25 / 10) * 10 as age_group')
            )
            ->where('chm_volunteer_assignments.status', 'active')
            ->groupBy('gender', 'age_group')
            ->orderBy('gender')
            ->orderBy('age_group');
            
        if ($positionId) {
            $query->where('chm_volunteer_assignments.position_id', $positionId);
        }
        
        $results = $query->get();
        
        // Format the results into a more structured format
        $demographics = [
            'gender' => [],
            'age_groups' => [],
            'total' => 0
        ];
        
        foreach ($results as $row) {
            // Gender breakdown
            if (!isset($demographics['gender'][$row->gender])) {
                $demographics['gender'][$row->gender] = 0;
            }
            $demographics['gender'][$row->gender] += $row->count;
            
            // Age group breakdown
            $ageGroup = $row->age_group . '-' . ($row->age_group + 9);
            if (!isset($demographics['age_groups'][$ageGroup])) {
                $demographics['age_groups'][$ageGroup] = 0;
            }
            $demographics['age_groups'][$ageGroup] += $row->count;
            
            // Total count
            $demographics['total'] += $row->count;
        }
        
        return $demographics;
    }
    
    /**
     * Get top volunteers by hours served.
     *
     * @param  int  $limit
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Support\Collection
     */
    public static function getTopVolunteers($limit = 10, $startDate = null, $endDate = null)
    {
        $query = DB::table('chm_volunteer_hours')
            ->join('chm_members', 'chm_volunteer_hours.volunteer_id', '=', 'chm_members.id')
            ->select(
                'chm_members.id',
                'chm_members.first_name',
                'chm_members.last_name',
                DB::raw('SUM(chm_volunteer_hours.hours) as total_hours'),
                DB::raw('COUNT(DISTINCT DATE(chm_volunteer_hours.date)) as days_served')
            )
            ->groupBy('chm_members.id', 'chm_members.first_name', 'chm_members.last_name')
            ->orderBy('total_hours', 'desc')
            ->limit($limit);
            
        if ($startDate) {
            $query->whereDate('chm_volunteer_hours.date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('chm_volunteer_hours.date', '<=', $endDate);
        }
        
        return $query->get();
    }
}
