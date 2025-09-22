<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\VolunteerReport;
use Prasso\Church\Models\VolunteerPosition;

class VolunteerReportController extends Controller
{
    /**
     * Get volunteer assignment statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $positionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignmentStats(Request $request, $positionId = null)
    {
        $this->authorize('viewAny', VolunteerPosition::class);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $stats = VolunteerReport::getAssignmentStats($positionId, $startDate, $endDate);
        
        return response()->json($stats);
    }
    
    /**
     * Get volunteer hours by position.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $positionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoursByPosition(Request $request, $positionId = null)
    {
        $this->authorize('viewAny', VolunteerPosition::class);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $hours = VolunteerReport::getHoursByPosition($positionId, $startDate, $endDate);
        
        return response()->json($hours);
    }
    
    /**
     * Get volunteer hours over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $positionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoursOverTime(Request $request, $positionId = null)
    {
        $this->authorize('viewAny', VolunteerPosition::class);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $interval = $request->input('interval', 'month');
        
        $hours = VolunteerReport::getHoursOverTime($positionId, $startDate, $endDate, $interval);
        
        return response()->json($hours);
    }
    
    /**
     * Get volunteer demographics.
     *
     * @param  int  $positionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function demographics($positionId = null)
    {
        $this->authorize('viewAny', VolunteerPosition::class);
        
        $demographics = VolunteerReport::getVolunteerDemographics($positionId);
        
        return response()->json($demographics);
    }
    
    /**
     * Get top volunteers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function topVolunteers(Request $request)
    {
        $this->authorize('viewAny', VolunteerPosition::class);
        
        $limit = $request->input('limit', 10);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $volunteers = VolunteerReport::getTopVolunteers($limit, $startDate, $endDate);
        
        return response()->json($volunteers);
    }
    
    /**
     * Get a comprehensive report for a specific volunteer position.
     *
     * @param  int  $positionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function positionReport($positionId)
    {
        $position = VolunteerPosition::findOrFail($positionId);
        $this->authorize('view', $position);
        
        $startDate = request()->input('start_date', now()->subYear()->format('Y-m-d'));
        $endDate = request()->input('end_date', now()->format('Y-m-d'));
        
        $report = [
            'position' => $position,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'assignment_stats' => VolunteerReport::getAssignmentStats($positionId, $startDate, $endDate)->first(),
            'hours_by_position' => VolunteerReport::getHoursByPosition($positionId, $startDate, $endDate)->first(),
            'hours_over_time' => VolunteerReport::getHoursOverTime($positionId, $startDate, $endDate, 'month'),
            'demographics' => VolunteerReport::getVolunteerDemographics($positionId),
            'top_volunteers' => VolunteerReport::getTopVolunteers(5, $startDate, $endDate),
        ];
        
        return response()->json($report);
    }
}
