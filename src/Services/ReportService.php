<?php

namespace Prasso\Church\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Models\AttendanceRecord;

class ReportService
{
    /**
     * Get attendance statistics for a given date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    public function getAttendanceStats(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $query = AttendanceRecord::query()
            ->join('aph_attendance_events', 'aph_attendance_records.event_id', '=', 'aph_attendance_events.id')
            ->whereBetween('aph_attendance_events.start_time', [$startDate, $endDate])
            ->select([
                DB::raw('COUNT(DISTINCT aph_attendance_records.id) as total_attendees'),
                DB::raw('COUNT(DISTINCT aph_attendance_events.id) as total_events'),
                DB::raw('COUNT(DISTINCT aph_attendance_records.member_id) as unique_members'),
                DB::raw('AVG(aph_attendance_records.guest_count) as avg_guests_per_event')
            ]);

        // Apply filters
        if (!empty($filters['ministry_id'])) {
            $query->where('aph_attendance_events.ministry_id', $filters['ministry_id']);
        }

        if (!empty($filters['group_id'])) {
            $query->where('aph_attendance_events.group_id', $filters['group_id']);
        }

        return $query->first()->toArray();
    }

    /**
     * Get member growth statistics
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $interval
     * @return array
     */
    public function getMemberGrowth(Carbon $startDate, Carbon $endDate, string $interval = 'month')
    {
        $format = $interval === 'month' ? 'Y-m' : 'Y';
        
        return Member::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select([
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as new_members'),
                DB::raw('(SELECT COUNT(*) FROM aph_members m2 WHERE m2.created_at <= MAX(aph_members.created_at)) as total_members')
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Generate custom report based on parameters
     *
     * @param array $params
     * @return array
     */
    public function generateCustomReport(array $params)
    {
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);
        $reportType = $params['report_type'];
        
        switch ($reportType) {
            case 'attendance_trends':
                return $this->getAttendanceTrends($startDate, $endDate, $params['group_by'] ?? 'week');
            case 'member_engagement':
                return $this->getMemberEngagement($startDate, $endDate);
            case 'event_attendance':
                return $this->getEventAttendance($startDate, $endDate, $params['event_id'] ?? null);
            default:
                throw new \InvalidArgumentException("Invalid report type: {$reportType}");
        }
    }

    /**
     * Get attendance trends over time
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy
     * @return array
     */
    protected function getAttendanceTrends(Carbon $startDate, Carbon $endDate, string $groupBy = 'week')
    {
        $format = $groupBy === 'month' ? '%Y-%m' : '%Y-%U';
        
        return AttendanceRecord::query()
            ->join('aph_attendance_events', 'aph_attendance_records.event_id', '=', 'aph_attendance_events.id')
            ->whereBetween('aph_attendance_events.start_time', [$startDate, $endDate])
            ->select([
                DB::raw("DATE_FORMAT(aph_attendance_events.start_time, '{$format}') as period"),
                DB::raw('COUNT(DISTINCT aph_attendance_records.id) as total_attendees'),
                DB::raw('COUNT(DISTINCT aph_attendance_events.id) as total_events'),
                DB::raw('COUNT(DISTINCT aph_attendance_records.member_id) as unique_members')
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get member engagement metrics
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getMemberEngagement(Carbon $startDate, Carbon $endDate)
    {
        return [
            'active_members' => $this->getActiveMembersCount($startDate, $endDate),
            'new_members' => $this->getNewMembersCount($startDate, $endDate),
            'attendance_rate' => $this->getAverageAttendanceRate($startDate, $endDate),
            'engagement_score' => $this->calculateEngagementScore($startDate, $endDate)
        ];
    }

    /**
     * Get event attendance details
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $eventId
     * @return array
     */
    protected function getEventAttendance(Carbon $startDate, Carbon $endDate, ?int $eventId = null)
    {
        $query = AttendanceEvent::query()
            ->withCount(['records as total_attendees'])
            ->whereBetween('start_time', [$startDate, $endDate]);

        if ($eventId) {
            $query->where('id', $eventId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get count of active members in date range
     */
    protected function getActiveMembersCount(Carbon $startDate, Carbon $endDate): int
    {
        return AttendanceRecord::query()
            ->join('aph_attendance_events', 'aph_attendance_records.event_id', '=', 'aph_attendance_events.id')
            ->whereBetween('aph_attendance_events.start_time', [$startDate, $endDate])
            ->distinct('member_id')
            ->count('member_id');
    }

    /**
     * Get count of new members in date range
     */
    protected function getNewMembersCount(Carbon $startDate, Carbon $endDate): int
    {
        return Member::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Calculate average attendance rate
     */
    protected function getAverageAttendanceRate(Carbon $startDate, Carbon $endDate): float
    {
        // This is a simplified calculation - adjust based on your requirements
        $totalEvents = AttendanceEvent::whereBetween('start_time', [$startDate, $endDate])->count();
        $totalAttendance = AttendanceRecord::query()
            ->join('aph_attendance_events', 'aph_attendance_records.event_id', '=', 'aph_attendance_events.id')
            ->whereBetween('aph_attendance_events.start_time', [$startDate, $endDate])
            ->count();

        return $totalEvents > 0 ? round($totalAttendance / $totalEvents, 2) : 0;
    }

    /**
     * Calculate engagement score (0-100)
     */
    protected function calculateEngagementScore(Carbon $startDate, Carbon $endDate): float
    {
        // This is a simplified engagement score calculation
        // You might want to adjust the weights based on your specific requirements
        
        $activeMembers = $this->getActiveMembersCount($startDate, $endDate);
        $totalMembers = Member::count();
        $attendanceRate = $this->getAverageAttendanceRate($startDate, $endDate);
        
        // Normalize values (assuming max 100 members and 100% attendance rate)
        $memberRatio = $totalMembers > 0 ? ($activeMembers / $totalMembers) * 100 : 0;
        
        // Weighted average (adjust weights as needed)
        $score = ($memberRatio * 0.6) + ($attendanceRate * 0.4);
        
        return min(100, max(0, round($score, 2)));
    }
    
    /**
     * Get giving statistics for a given date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    public function getGivingReport(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $query = DB::table('aph_donations')
            ->whereBetween('donation_date', [$startDate, $endDate])
            ->select([
                DB::raw('COUNT(DISTINCT id) as total_donations'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as average_donation'),
                DB::raw('COUNT(DISTINCT donor_id) as unique_donors')
            ]);
            
        // Apply filters
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }
        
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        $stats = $query->first();
        
        // Get giving by category
        $byCategory = DB::table('aph_donations')
            ->join('aph_donation_categories', 'aph_donations.category_id', '=', 'aph_donation_categories.id')
            ->whereBetween('donation_date', [$startDate, $endDate])
            ->select([
                'aph_donation_categories.name as category',
                DB::raw('COUNT(aph_donations.id) as donation_count'),
                DB::raw('SUM(amount) as total_amount')
            ])
            ->groupBy('category_id', 'aph_donation_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();
            
        return [
            'summary' => $stats,
            'by_category' => $byCategory,
            'time_period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ];
    }
    
    /**
     * Generate a comprehensive dashboard with all key metrics
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getDashboardMetrics(Carbon $startDate, Carbon $endDate)
    {
        return [
            'attendance' => $this->getAttendanceStats(
                $startDate->copy()->subMonth(), 
                $endDate
            ),
            'member_growth' => $this->getMemberGrowth(
                $startDate->copy()->subYear(), 
                $endDate
            ),
            'engagement' => $this->getMemberEngagement($startDate, $endDate),
            'giving' => $this->getGivingReport(
                $startDate->copy()->startOfMonth(), 
                $endDate->copy()->endOfMonth()
            ),
            'recent_events' => $this->getEventAttendance(
                $startDate->copy()->subDays(30), 
                $endDate,
                null,
                5
            )
        ];
    }
    
    /**
     * Export report data in the specified format
     *
     * @param array $data
     * @param string $format
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportReport(array $data, string $format = 'csv', string $filename = 'report')
    {
        $filename = "{$filename}_" . now()->format('Y-m-d_His') . ".{$format}";
        
        if ($format === 'csv' || $format === 'xlsx') {
            return Excel::download(
                new ReportExport($data),
                $filename,
                \Maatwebsite\Excel\Excel::strtoupper($format)
            );
        }
        
        if ($format === 'pdf') {
            $pdf = PDF::loadView('church::reports.export_pdf', [
                'data' => $data,
                'title' => $filename,
                'generated_at' => now()
            ]);
            
            return $pdf->download($filename);
        }
        
        throw new \InvalidArgumentException("Unsupported export format: {$format}");
    }
        // This is a simplified engagement score - adjust the formula as needed
        $activeMembers = $this->getActiveMembersCount($startDate, $endDate);
        $totalMembers = Member::count();
        $attendanceRate = $this->getAverageAttendanceRate($startDate, $endDate);

        if ($totalMembers === 0) {
            return 0;
        }

        $score = (($activeMembers / $totalMembers) * 100 + $attendanceRate) / 2;
        return min(100, round($score, 2));
    }
}
