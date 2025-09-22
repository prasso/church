<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrayerRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $request->user();
        $isStaff = $user && $user->isStaff();
        $isOwner = $user && (
            $this->member_id === $user->id || 
            $this->requested_by === $user->id
        );
        
        $canViewDetails = $isStaff || $isOwner || $this->is_public;
        
        return [
            'id' => $this->id,
            'title' => $this->when($canViewDetails, $this->title, 'Prayer Request'),
            'description' => $this->when(
                $canViewDetails, 
                $this->description,
                $isOwner ? 'Your private prayer request' : 'A prayer request'
            ),
            'is_anonymous' => (bool) $this->is_anonymous,
            'is_public' => (bool) $this->is_public,
            'status' => $this->when($canViewDetails, $this->status),
            'prayer_count' => $this->when($canViewDetails, $this->prayer_count),
            'answer' => $this->when(
                $canViewDetails && $this->status === 'answered',
                $this->answer
            ),
            'answered_at' => $this->when(
                $canViewDetails && $this->status === 'answered',
                $this->answered_at
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'member' => $this->when(
                $canViewDetails && !$this->is_anonymous,
                function () {
                    return [
                        'id' => $this->member->id,
                        'name' => $this->member->full_name,
                    ];
                }
            ),
            'requested_by' => $this->when(
                $canViewDetails && $this->requestedBy && !$this->is_anonymous,
                function () {
                    return [
                        'id' => $this->requestedBy->id,
                        'name' => $this->requestedBy->full_name,
                    ];
                }
            ),
            'prayer_groups' => $this->when(
                $canViewDetails && $this->relationLoaded('prayerGroups'),
                function () {
                    return $this->prayerGroups->map(function ($group) {
                        return [
                            'id' => $group->id,
                            'name' => $group->name,
                        ];
                    });
                }
            ),
            
            // Permissions
            'can' => [
                'view' => $canViewDetails,
                'update' => $isStaff || $isOwner,
                'delete' => $isStaff,
                'pray' => $canViewDetails && $user && $user->id !== $this->member_id,
            ],
        ];
    }
}
