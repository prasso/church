<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\PastoralVisitAssigned;
use Prasso\Church\Models\Member;
use Illuminate\Support\Facades\Notification;
use Prasso\Church\Notifications\PastoralVisitAssignedNotification;

class SendPastoralVisitAssignedNotification
{
    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\PastoralVisitAssigned  $event
     * @return void
     */
    public function handle(PastoralVisitAssigned $event)
    {
        $visit = $event->visit;
        
        // Notify the assigned staff member
        if ($visit->assignedTo) {
            $visit->assignedTo->notify(new PastoralVisitAssignedNotification($visit));
        }
        
        // Notify the member or family about the scheduled visit
        if ($visit->member) {
            // Only notify if the member is different from the assigned staff
            if (!$visit->assignedTo || $visit->member->id !== $visit->assignedTo->id) {
                $visit->member->notify(new \Prasso\Church\Notifications\PastoralVisitScheduledNotification($visit));
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
                    new \Prasso\Church\Notifications\PastoralVisitScheduledNotification($visit)
                );
            }
        }
    }
}
