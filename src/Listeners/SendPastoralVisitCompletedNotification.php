<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\PastoralVisitCompleted;
use Prasso\Church\Models\Member;
use Illuminate\Support\Facades\Notification;
use Prasso\Church\Notifications\PastoralVisitCompletedNotification;
use Prasso\Church\Notifications\PastoralVisitFollowUpNotification;

class SendPastoralVisitCompletedNotification
{
    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\PastoralVisitCompleted  $event
     * @return void
     */
    public function handle(PastoralVisitCompleted $event)
    {
        $visit = $event->visit;
        
        // Notify the assigned staff member
        if ($visit->assignedTo) {
            $visit->assignedTo->notify(new PastoralVisitCompletedNotification($visit));
        }
        
        // Notify the member or family about the completed visit
        if ($visit->member) {
            // Only notify if the member is different from the assigned staff
            if (!$visit->assignedTo || $visit->member->id !== $visit->assignedTo->id) {
                $visit->member->notify(new \Prasso\Church\Notifications\PastoralVisitFollowUpNotification($visit));
            }
        } elseif ($visit->family) {
            // Notify all adult family members
            $adultMembers = $visit->family->members()
                ->where('is_adult', true)
                ->where(function($query) use ($visit) {
                    // Don't notify the assigned staff member again
                    if ($visit->assignedTo) {
                        $query->where('id', '!=', $visit->assignedTo->id);
                    }
                })
                ->get();
                
            if ($adultMembers->isNotEmpty()) {
                Notification::send(
                    $adultMembers, 
                    new \Prasso\Church\Notifications\PastoralVisitFollowUpNotification($visit)
                );
            }
        }
        
        // Notify pastoral care team about the completed visit
        $pastoralCareTeam = Member::whereHas('roles', function($query) {
                $query->where('name', 'pastoral_care');
            })
            ->where('receive_pastoral_updates', true)
            ->where(function($query) use ($visit) {
                // Don't notify the assigned staff member again
                if ($visit->assignedTo) {
                    $query->where('id', '!=', $visit->assignedTo->id);
                }
            })
            ->get();
            
        if ($pastoralCareTeam->isNotEmpty()) {
            Notification::send(
                $pastoralCareTeam,
                new \Prasso\Church\Notifications\PastoralCareTeamUpdateNotification($visit)
            );
        }
    }
}
