<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\PastoralVisit;

class PastoralVisitScheduledNotification extends Notification implements ShouldQueue
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
        $staffName = $visit->assignedTo ? $visit->assignedTo->full_name : 'a staff member';
        
        return (new MailMessage)
                    ->subject("Pastoral Visit Scheduled: {$visit->title}")
                    ->greeting('Hello!')
                    ->line("A pastoral visit has been scheduled for you with {$staffName}.")
                    ->line("**When:** {$visit->scheduled_for->format('l, F j, Y \a\t g:i A')}")
                    ->line("**Purpose:** {$visit->purpose}")
                    ->line("**Location:** {$visit->location_type}" . ($visit->location_details ? " ({$visit->location_details})" : ''))
                    ->action('View Visit Details', url("/pastoral-visits/{$visit->id}"))
                    ->line('We look forward to seeing you!');
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
        $staffName = $visit->assignedTo ? $visit->assignedTo->full_name : 'a staff member';
        
        return [
            'type' => 'pastoral_visit.scheduled',
            'title' => 'Pastoral Visit Scheduled',
            'message' => "You have a visit with {$staffName} on {$visit->scheduled_for->format('M j, Y')}",
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
        $staffName = $visit->assignedTo ? $visit->assignedTo->first_name : 'a staff member';
        
        return "Pastoral visit scheduled with {$staffName} on " . 
               $visit->scheduled_for->format('M j, g:i A') . 
               ". Purpose: {$visit->purpose}";
    }
}
