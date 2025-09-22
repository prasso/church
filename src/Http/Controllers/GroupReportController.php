<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\GroupReport;
use Prasso\Church\Models\Group;

class GroupReportController extends Controller
{
    /**
     * Get group membership statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function membershipStats(Request $request, $groupId = null)
    {
        $this->authorize('viewAny', Group::class);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $stats = GroupReport::getMembershipStats($groupId, $startDate, $endDate);
        
        return response()->json($stats);
    }
    
    /**
     * Get group growth over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function growthOverTime(Request $request, $groupId = null)
    {
        $this->authorize('viewAny', Group::class);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $interval = $request->input('interval', 'month');
        
        $growth = GroupReport::getGrowthOverTime($groupId, $startDate, $endDate, $interval);
        
        return response()->json($growth);
    }
    
    /**
     * Get group engagement metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function engagementMetrics(Request $request, $groupId = null)
    {
        $this->authorize('viewAny', Group::class);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $metrics = GroupReport::getEngagementMetrics($groupId, $startDate, $endDate);
        
        return response()->json($metrics);
    }
    
    /**
     * Get demographic breakdown of group members.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function demographics($groupId = null)
    {
        $this->authorize('viewAny', Group::class);
        
        $demographics = GroupReport::getDemographics($groupId);
        
        return response()->json($demographics);
    }
    
    /**
     * Get a comprehensive report for a specific group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function groupReport($groupId)
    {
        $group = Group::findOrFail($groupId);
        $this->authorize('view', $group);
        
        $startDate = request()->input('start_date', now()->subYear()->format('Y-m-d'));
        $endDate = request()->input('end_date', now()->format('Y-m-d'));
        
        $report = [
            'group' => $group,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'membership_stats' => GroupReport::getMembershipStats($groupId, $startDate, $endDate)->first(),
            'growth' => GroupReport::getGrowthOverTime($groupId, $startDate, $endDate, 'month'),
            'engagement' => GroupReport::getEngagementMetrics($groupId, $startDate, $endDate)->first(),
            'demographics' => GroupReport::getDemographics($groupId),
        ];
        
        return response()->json($report);
    }
}
