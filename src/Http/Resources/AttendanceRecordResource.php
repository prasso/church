<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
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
            'event' => new AttendanceEventResource($this->whenLoaded('event')),
            'member_id' => $this->member_id,
            'member' => $this->whenLoaded('member', function () {
                return [
                    'id' => $this->member->id,
                    'first_name' => $this->member->first_name,
                    'last_name' => $this->member->last_name,
                    'full_name' => $this->member->full_name,
                    'email' => $this->member->email,
                    'phone' => $this->member->phone,
                    'photo_url' => $this->member->photo_url,
                ];
            }),
            'family_id' => $this->family_id,
            'family' => $this->whenLoaded('family', function () {
                return [
                    'id' => $this->family->id,
                    'name' => $this->family->name,
                    'head_of_household' => $this->family->head_of_household,
                ];
            }),
            'checked_in_by' => $this->checked_in_by,
            'checked_in_by_user' => $this->whenLoaded('checkedInBy', function () {
                return [
                    'id' => $this->checkedInBy->id,
                    'name' => $this->checkedInBy->name,
                    'email' => $this->checkedInBy->email,
                ];
            }),
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'status' => $this->status,
            'status_display' => config("attendance.statuses.{$this->status}.name") ?? ucfirst($this->status),
            'guest_count' => $this->guest_count,
            'notes' => $this->notes,
            'source' => $this->source,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
            
            // Computed attributes
            'duration' => $this->duration,
            'duration_in_minutes' => $this->duration_in_minutes,
            'duration_formatted' => $this->duration_formatted,
            'is_checked_in' => $this->isCheckedIn(),
            'is_checked_out' => $this->isCheckedOut(),
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
                'permissions' => [
                    'update' => $request->user()?->can('update', $this->resource) ?? false,
                    'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                    'check_out' => $request->user()?->can('checkOut', $this->resource) ?? false,
                ],
            ],
        ];
    }
}
