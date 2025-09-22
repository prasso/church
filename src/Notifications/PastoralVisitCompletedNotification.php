<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\PastoralVisit;

class PastoralVisitCompletedNotification extends Notification implements ShouldQueue
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
                    ->subject("Pastoral Visit Completed: {$visit->title}")
                    ->greeting('Hello!')
                    ->line("The pastoral visit with {$staffName} has been marked as completed.")
                    ->line("**Visit Date:** {$visit->scheduled_for->format('l, F j, Y')}")
                    ->line("**Purpose:** {$visit->purpose}")
                    ->when($visit->outcome_summary, function ($message) use ($visit) {
                        $message->line("**Outcome:** {$visit->outcome_summary}");
                    })
                    ->when($visit->follow_up_actions, function ($message) use ($visit) {
                        $message->line("**Follow-up Actions:** {$visit->follow_up_actions}");
                    })
                    ->action('View Visit Details', url("/pastoral-visits/{$visit->id}"))
                    ->line('Thank you for being part of our church community!');
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
            'type' => 'pastoral_visit.completed',
            'title' => 'Pastoral Visit Completed',
            'message' => "Your visit with {$staffName} on {$visit->scheduled_for->format('M j, Y')} has been completed.",
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
        
        return "Your pastoral visit with {$staffName} on " . 
               $visit->scheduled_for->format('M j') . 
               " has been completed. Thank you!";
    }
}
