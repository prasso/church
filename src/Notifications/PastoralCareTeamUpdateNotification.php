<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\PastoralVisit;

class PastoralCareTeamUpdateNotification extends Notification implements ShouldQueue
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
        $memberName = $visit->member ? $visit->member->full_name : ($visit->family ? $visit->family->name : 'a member');
        
        $mail = (new MailMessage)
                    ->subject("Pastoral Visit Completed: {$memberName}")
                    ->greeting('Pastoral Care Team Update')
                    ->line("A pastoral visit has been completed by {$staffName} with {$memberName}.")
                    ->line("**Date:** {$visit->scheduled_for->format('l, F j, Y')}")
                    ->line("**Purpose:** {$visit->purpose}");
        
        // Add notes if available
        if ($visit->notes) {
            $mail->line("**Notes:**")
                 ->line($visit->notes);
        }
        
        // Add outcome summary if available
        if ($visit->outcome_summary) {
            $mail->line("**Outcome Summary:**")
                 ->line($visit->outcome_summary);
        }
        
        // Add follow-up actions if any
        if ($visit->follow_up_actions) {
            $mail->line("**Follow-up Actions:**")
                 ->line($visit->follow_up_actions);
        }
        
        $mail->action('View Visit Details', url("/admin/pastoral-visits/{$visit->id}"))
             ->line('Thank you for your continued ministry!');
             
        return $mail;
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
            'type' => 'pastoral_care.visit_completed',
            'title' => 'Pastoral Visit Completed',
            'message' => "A visit with {$memberName} has been completed.",
            'visit_id' => $visit->id,
            'member_name' => $memberName,
            'scheduled_for' => $visit->scheduled_for->toDateTimeString(),
            'url' => "/admin/pastoral-visits/{$visit->id}",
        ];
    }
}
