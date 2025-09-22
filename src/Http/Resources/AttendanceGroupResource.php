<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceGroupResource extends JsonResource
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
            'is_active' => $this->is_active,
            'ministry_id' => $this->ministry_id,
            'ministry' => $this->whenLoaded('ministry', function () {
                return [
                    'id' => $this->ministry->id,
                    'name' => $this->ministry->name,
                    'description' => $this->ministry->description,
                ];
            }),
            'created_by' => $this->created_by,
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
            
            // Relationships
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'first_name' => $member->first_name,
                        'last_name' => $member->last_name,
                        'full_name' => $member->full_name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'photo_url' => $member->photo_url,
                        'pivot' => $member->pivot,
                    ];
                });
            }),
            'families' => $this->whenLoaded('families', function () {
                return $this->families->map(function ($family) {
                    return [
                        'id' => $family->id,
                        'name' => $family->name,
                        'head_of_household' => $family->head_of_household,
                        'pivot' => $family->pivot,
                    ];
                });
            }),
            'groups' => $this->whenLoaded('groups', function () {
                return $this->groups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'pivot' => $group->pivot,
                    ];
                });
            }),
            'active_members' => $this->whenLoaded('activeMembers', function () {
                return $this->activeMembers->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'first_name' => $member->first_name,
                        'last_name' => $member->last_name,
                        'full_name' => $member->full_name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'photo_url' => $member->photo_url,
                    ];
                });
            }),
            'active_families' => $this->whenLoaded('activeFamilies', function () {
                return $this->activeFamilies->map(function ($family) {
                    return [
                        'id' => $family->id,
                        'name' => $family->name,
                        'head_of_household' => $family->head_of_household,
                    ];
                });
            }),
            'active_groups' => $this->whenLoaded('activeGroups', function () {
                return $this->activeGroups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                    ];
                });
            }),
            'events' => AttendanceEventResource::collection($this->whenLoaded('events')),
            'attendance_summary' => new AttendanceSummaryResource($this->whenLoaded('attendanceSummary')),
            
            // Computed attributes
            'total_members' => $this->when(isset($this->total_members), $this->total_members),
            'active_member_count' => $this->when(isset($this->active_member_count), $this->active_member_count),
            'attendance_rate' => $this->when(isset($this->attendance_rate), function () {
                return round($this->attendance_rate, 2);
            }),
            'recent_events' => $this->when(isset($this->recent_events), function () {
                return $this->recent_events->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'name' => $event->name,
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'attendance_count' => $event->attendance_records_count,
                    ];
                });
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
                'permissions' => [
                    'create' => $request->user()?->can('create', \Prasso\Church\Models\AttendanceGroup::class) ?? false,
                    'update' => $request->user()?->can('update', $this->resource) ?? false,
                    'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                    'manage_members' => $request->user()?->can('manage_members', $this->resource) ?? false,
                ],
            ],
        ];
    }
}
