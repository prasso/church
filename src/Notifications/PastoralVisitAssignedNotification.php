<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\PastoralVisit;

class PastoralVisitAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $visit;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return void
     */
    public function __construct(PastoralVisit $visit)
    {
        $this->visit = $visit;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        
        // Add email if user has email notifications enabled
        if ($notifiable->email_notifications) {
            $channels[] = 'mail';
        }
        
        // Add SMS if user has SMS notifications enabled and phone number is verified
        if ($notifiable->sms_notifications && $notifiable->hasVerifiedPhone()) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $visit = $this->visit;
        $memberName = $visit->member ? $visit->member->full_name : ($visit->family ? $visit->family->name : 'a member');
        
        return (new MailMessage)
                    ->subject("New Pastoral Visit Assigned: {$visit->title}")
                    ->greeting('Hello!')
                    ->line("You have been assigned a new pastoral visit with {$memberName}.")
                    ->line("**When:** {$visit->scheduled_for->format('l, F j, Y \a\t g:i A')}")
                    ->line("**Purpose:** {$visit->purpose}")
                    ->line("**Location:** {$visit->location_type}" . ($visit->location_details ? " ({$visit->location_details})" : ''))
                    ->action('View Visit Details', url("/pastoral-visits/{$visit->id}"))
                    ->line('Thank you for your ministry!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $visit = $this->visit;
        $memberName = $visit->member ? $visit->member->full_name : ($visit->family ? $visit->family->name : 'a member');
        
        return [
            'type' => 'pastoral_visit.assigned',
            'title' => 'New Pastoral Visit Assigned',
            'message' => "You have a visit with {$memberName} on {$visit->scheduled_for->format('M j, Y')}",
            'visit_id' => $visit->id,
            'scheduled_for' => $visit->scheduled_for->toDateTimeString(),
            'url' => "/pastoral-visits/{$visit->id}",
        ];
    }
    
    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function toSms($notifiable)
    {
        $visit = $this->visit;
        $memberName = $visit->member ? $visit->member->first_name : ($visit->family ? $visit->family->name : 'a member');
        
        return "New pastoral visit with {$memberName} on " . 
               $visit->scheduled_for->format('M j, g:i A') . 
               ". Purpose: {$visit->purpose}";
    }
}
