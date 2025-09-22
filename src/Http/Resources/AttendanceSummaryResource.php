<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'event' => $this->when($this->event_id, function () {
                return [
                    'id' => $this->event->id,
                    'name' => $this->event->name,
                    'start_time' => $this->event->start_time,
                    'end_time' => $this->event->end_time,
                ];
            }),
            'ministry_id' => $this->ministry_id,
            'ministry' => $this->when($this->ministry_id, function () {
                return [
                    'id' => $this->ministry->id,
                    'name' => $this->ministry->name,
                ];
            }),
            'group_id' => $this->group_id,
            'group' => $this->when($this->group_id, function () {
                return [
                    'id' => $this->group->id,
                    'name' => $this->group->name,
                ];
            }),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_attendees' => $this->total_attendees,
            'total_guests' => $this->total_guests,
            'total_events' => $this->total_events,
            'present_count' => $this->present_count,
            'late_count' => $this->late_count,
            'excused_count' => $this->excused_count,
            'absent_count' => $this->absent_count,
            'tardy_count' => $this->tardy_count,
            'demographics' => $this->demographics,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Computed attributes
            'attendance_rate' => $this->when(isset($this->attendance_rate), function () {
                return round($this->attendance_rate, 2);
            }),
            'average_attendance' => $this->when(isset($this->average_attendance), function () {
                return round($this->average_attendance, 2);
            }),
            'trend' => $this->when(isset($this->trend), $this->trend),
            'top_attendees' => $this->when(isset($this->top_attendees), $this->top_attendees),
            'event_types' => $this->when(isset($this->event_types), $this->event_types),
        ];
    }
    
    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'statuses' => config('attendance.statuses'),
                'date_ranges' => config('attendance.reporting.date_ranges'),
                'group_by_options' => config('attendance.reporting.group_by'),
                'permissions' => [
                    'view_reports' => $request->user()?->can('view_attendance_reports') ?? false,
                    'export_reports' => $request->user()?->can('export_attendance_reports') ?? false,
                ],
            ],
        ];
    }
}
