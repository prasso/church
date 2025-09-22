<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PastoralVisitResource extends JsonResource
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
        $isAssigned = $user && $this->assigned_to === $user->id;
        $isRelated = $user && (
            $this->member_id === $user->id || 
            ($this->family_id && $this->family_id === $user->family_id)
        );
        
        $canViewDetails = $isStaff || $isAssigned || $isRelated;
        $canViewConfidential = $isStaff || $isAssigned;
        
        return [
            'id' => $this->id,
            'title' => $this->when($canViewDetails, $this->title, 'Pastoral Visit'),
            'purpose' => $this->when($canViewDetails, $this->purpose, 'A pastoral visit'),
            'scheduled_for' => $this->when($canViewDetails, $this->scheduled_for),
            'started_at' => $this->when($canViewDetails, $this->started_at),
            'ended_at' => $this->when($canViewDetails, $this->ended_at),
            'duration_minutes' => $this->when($canViewDetails, $this->duration_minutes),
            'location_type' => $this->when($canViewDetails, $this->location_type),
            'location_details' => $this->when($canViewDetails, $this->location_details),
            'status' => $this->when($canViewDetails, $this->status),
            'notes' => $this->when(
                $canViewDetails && ($isStaff || $isAssigned || $this->status === 'completed'),
                $this->notes
            ),
            'follow_up_actions' => $this->when(
                $canViewDetails && ($isStaff || $isAssigned || $isRelated),
                $this->follow_up_actions
            ),
            'follow_up_date' => $this->when(
                $canViewDetails && ($isStaff || $isAssigned || $isRelated),
                $this->follow_up_date
            ),
            'spiritual_needs' => $this->when(
                $canViewDetails && ($isStaff || $isAssigned || $isRelated),
                $this->spiritual_needs
            ),
            'outcome_summary' => $this->when(
                $canViewDetails && ($isStaff || $isAssigned || $this->status === 'completed'),
                $this->outcome_summary
            ),
            'is_confidential' => $this->when($canViewConfidential, $this->is_confidential),
            'created_at' => $this->when($canViewDetails, $this->created_at),
            'updated_at' => $this->when($canViewDetails, $this->updated_at),
            
            // Relationships
            'member' => $this->when(
                $canViewDetails && $this->relationLoaded('member'),
                function () {
                    return [
                        'id' => $this->member->id,
                        'name' => $this->member->full_name,
                    ];
                }
            ),
            'family' => $this->when(
                $canViewDetails && $this->relationLoaded('family'),
                function () {
                    return [
                        'id' => $this->family->id,
                        'name' => $this->family->name,
                    ];
                }
            ),
            'assigned_to' => $this->when(
                $canViewDetails && $this->relationLoaded('assignedTo'),
                function () {
                    return [
                        'id' => $this->assignedTo->id,
                        'name' => $this->assignedTo->full_name,
                        'role' => $this->assignedTo->role,
                    ];
                }
            ),
            
            // Permissions
            'can' => [
                'view' => $canViewDetails,
                'update' => $isStaff || $isAssigned,
                'delete' => $isStaff,
                'start' => $isStaff && $this->status === 'scheduled',
                'complete' => $isStaff && in_array($this->status, ['scheduled', 'in_progress']),
                'cancel' => $isStaff && $this->status !== 'canceled' && $this->status !== 'completed',
            ],
        ];
    }
}
