<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceEventResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'location_id' => $this->location_id,
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                    'capacity' => $this->location->capacity,
                ];
            }),
            'event_type_id' => $this->event_type_id,
            'event_type' => $this->whenLoaded('eventType', function () {
                return [
                    'id' => $this->eventType->id,
                    'name' => $this->eventType->name,
                    'description' => $this->eventType->description,
                ];
            }),
            'ministry_id' => $this->ministry_id,
            'ministry' => $this->whenLoaded('ministry', function () {
                return [
                    'id' => $this->ministry->id,
                    'name' => $this->ministry->name,
                    'description' => $this->ministry->description,
                ];
            }),
            'group_id' => $this->group_id,
            'group' => $this->whenLoaded('group', function () {
                return [
                    'id' => $this->group->id,
                    'name' => $this->group->name,
                    'description' => $this->group->description,
                ];
            }),
            'expected_attendance' => $this->expected_attendance,
            'actual_attendance' => $this->when(isset($this->attendance_count), function () {
                return $this->attendance_count;
            }),
            'attendance_rate' => $this->when(isset($this->attendance_rate), function () {
                return round($this->attendance_rate, 2);
            }),
            'requires_check_in' => $this->requires_check_in,
            'is_recurring' => $this->is_recurring,
            'recurrence_pattern' => $this->recurrence_pattern,
            'recurrence_details' => $this->recurrence_details,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
            
            // Relationships
            'attendance_records' => AttendanceRecordResource::collection($this->whenLoaded('attendanceRecords')),
            'attendance_summary' => new AttendanceSummaryResource($this->whenLoaded('attendanceSummary')),
            
            // Computed attributes
            'is_past' => $this->isPast(),
            'is_upcoming' => $this->isUpcoming(),
            'duration' => $this->duration,
            'duration_in_minutes' => $this->duration_in_minutes,
            'duration_formatted' => $this->duration_formatted,
            'check_in_url' => $this->when($this->requires_check_in, function () {
                return route('attendance.check-in', ['event' => $this->id]);
            }),
            'check_in_qr_code' => $this->when($this->requires_check_in, function () {
                return $this->generateQrCode();
            }),
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
                'recurrence_patterns' => config('attendance.recurring.patterns'),
                'permissions' => [
                    'create' => $request->user()?->can('create', \Prasso\Church\Models\AttendanceEvent::class) ?? false,
                    'update' => $request->user()?->can('update', $this->resource) ?? false,
                    'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                    'manage_attendance' => $request->user()?->can('manage_attendance') ?? false,
                ],
            ],
        ];
    }
}
