<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GroupReport extends Model
{
    /**
     * Get group membership statistics.
     *
     * @param  int  $groupId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array
     */
    public static function getMembershipStats($groupId = null, $startDate = null, $endDate = null)
    {
        $query = DB::table('chm_group_member')
            ->join('chm_groups', 'chm_group_member.group_id', '=', 'chm_groups.id')
            ->select(
                'chm_groups.id as group_id',
                'chm_groups.name as group_name',
                DB::raw('COUNT(DISTINCT chm_group_member.member_id) as total_members'),
                DB::raw('SUM(CASE WHEN chm_group_member.status = "active" THEN 1 ELSE 0 END) as active_members'),
                DB::raw('SUM(CASE WHEN chm_group_member.role = "leader" THEN 1 ELSE 0 END) as leaders_count')
            )
            ->groupBy('chm_groups.id', 'chm_groups.name');
        
        if ($groupId) {
            $query->where('chm_groups.id', $groupId);
        }
        
        if ($startDate) {
            $query->whereDate('chm_group_member.join_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('chm_group_member.join_date', '<=', $endDate);
        }
        
        return $query->get();
    }
    
    /**
     * Get group growth over time.
     *
     * @param  int  $groupId
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  string  $interval  (day, week, month, year)
     * @return array
     */
    public static function getGrowthOverTime($groupId = null, $startDate = null, $endDate = null, $interval = 'month')
    {
        $startDate = $startDate ?: now()->subYear()->format('Y-m-d');
        $endDate = $endDate ?: now()->format('Y-m-d');
        
        $dateFormat = [
            'day' => '%Y-%m-%d',
            'week' => '%x-%v',
            'month' => '%Y-%m',
            'year' => '%Y',
        ][$interval] ?? '%Y-%m';
        
        $query = DB::table('chm_group_member')
            ->select(
                DB::raw("DATE_FORMAT(join_date, '{$dateFormat}') as period"),
                DB::raw('COUNT(DISTINCT member_id) as new_members'),
                DB::raw('(SELECT COUNT(DISTINCT gm2.member_id) FROM chm_group_member gm2 WHERE gm2.join_date <= chm_group_member.join_date' . 
                         ($groupId ? ' AND gm2.group_id = ' . $groupId : '') . 
                         ') as total_members')
            )
            ->whereDate('join_date', '>=', $startDate)
            ->whereDate('join_date', '<=', $endDate)
            ->groupBy('period')
            ->orderBy('period');
            
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        
        return $query->get();
    }
    
    /**
     * Get group engagement metrics.
     *
     * @param  int  $groupId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array
     */
    public static function getEngagementMetrics($groupId = null, $startDate = null, $endDate = null)
    {
        $query = DB::table('chm_events')
            ->select(
                'chm_events.group_id',
                'chm_groups.name as group_name',
                DB::raw('COUNT(DISTINCT chm_events.id) as total_events'),
                DB::raw('AVG(chm_attendances.attended) * 100 as avg_attendance_rate')
            )
            ->leftJoin('chm_groups', 'chm_events.group_id', '=', 'chm_groups.id')
            ->leftJoin('chm_attendances', 'chm_events.id', '=', 'chm_attendances.event_id')
            ->groupBy('chm_events.group_id', 'chm_groups.name');
            
        if ($groupId) {
            $query->where('chm_events.group_id', $groupId);
        }
        
        if ($startDate) {
            $query->whereDate('chm_events.start_time', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('chm_events.end_time', '<=', $endDate);
        }
        
        return $query->get();
    }
    
    /**
     * Get demographic breakdown of group members.
     *
     * @param  int  $groupId
     * @return array
     */
    public static function getDemographics($groupId = null)
    {
        $query = DB::table('chm_members')
            ->join('chm_group_member', 'chm_members.id', '=', 'chm_group_member.member_id')
            ->select(
                DB::raw('COUNT(*) as count'),
                'chm_members.gender',
                DB::raw('FLOOR(DATEDIFF(NOW(), chm_members.birthdate) / 365.25 / 10) * 10 as age_group')
            )
            ->where('chm_group_member.status', 'active')
            ->groupBy('gender', 'age_group')
            ->orderBy('gender')
            ->orderBy('age_group');
            
        if ($groupId) {
            $query->where('chm_group_member.group_id', $groupId);
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
}
