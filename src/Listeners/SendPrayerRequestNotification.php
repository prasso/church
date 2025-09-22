<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\PrayerRequestCreated;
use Prasso\Church\Models\Member;
use Illuminate\Support\Facades\Notification;
use Prasso\Church\Notifications\PrayerRequestReceived;

class SendPrayerRequestNotification
{
    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\PrayerRequestCreated  $event
     * @return void
     */
    public function handle(PrayerRequestCreated $event)
    {
        $prayerRequest = $event->prayerRequest;
        
        // Get all staff members who should be notified
        $staffMembers = Member::where('is_staff', true)
            ->where('receive_prayer_notifications', true)
            ->get();
            
        if ($staffMembers->isNotEmpty()) {
            Notification::send($staffMembers, new PrayerRequestReceived($prayerRequest));
        }
        
        // If this is a group prayer request, notify group members
        if ($prayerRequest->prayerGroups->isNotEmpty()) {
            foreach ($prayerRequest->prayerGroups as $group) {
                $members = $group->members()
                    ->where('chm_members.id', '!=', $prayerRequest->member_id) // Don't notify the requester
                    ->where('receive_group_notifications', true)
                    ->get();
                    
                if ($members->isNotEmpty()) {
                    Notification::send($members, new PrayerRequestReceived($prayerRequest, $group));
                }
            }
        }
    }
}
