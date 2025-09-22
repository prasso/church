<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\PastoralVisit;

class PastoralVisitFollowUpNotification extends Notification implements ShouldQueue
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
        
        $mail = (new MailMessage)
                    ->subject("Thank You for Your Time - Pastoral Visit Follow-up")
                    ->greeting('Hello!')
                    ->line("Thank you for taking the time to meet with {$staffName} on {$visit->scheduled_for->format('l, F j, Y')}.")
                    ->line('We appreciate the opportunity to connect with you and hope the visit was meaningful.');
        
        // Add follow-up actions if any
        if ($visit->follow_up_actions) {
            $mail->line('**Follow-up Actions:**')
                 ->line($visit->follow_up_actions);
        }
        
        // Add a link to provide feedback
        $mail->action('Provide Feedback', url("/pastoral-visits/{$visit->id}/feedback"))
             ->line('If you have any further questions or needs, please don\'t hesitate to reach out.')
             ->line('Blessings,')
             ->salutation('Your Pastoral Care Team');
             
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
        
        return [
            'type' => 'pastoral_visit.follow_up',
            'title' => 'Pastoral Visit Follow-up',
            'message' => 'Thank you for your recent pastoral visit. We appreciate your time.',
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
        
        return "Thank you for your time during our recent pastoral visit. " . 
               "We appreciate the opportunity to connect with you. " .
               "If you have any questions, please contact us.";
    }
}
