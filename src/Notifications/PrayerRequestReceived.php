<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Group;

class PrayerRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public $prayerRequest;
    public $group;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @param  \Prasso\Church\Models\Group|null  $group
     * @return void
     */
    public function __construct(PrayerRequest $prayerRequest, Group $group = null)
    {
        $this->prayerRequest = $prayerRequest;
        $this->group = $group;
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
        $subject = 'New Prayer Request';
        $greeting = 'A new prayer request has been submitted';
        
        if ($this->group) {
            $subject = "New Prayer Request in {$this->group->name}";
            $greeting = "A new prayer request has been submitted in {$this->group->name}";
        }
        
        return (new MailMessage)
                    ->subject($subject)
                    ->greeting("$greeting:")
                    ->line($this->prayerRequest->title)
                    ->line($this->prayerRequest->description)
                    ->action('View Prayer Request', url("/prayer-requests/{$this->prayerRequest->id}"))
                    ->line('Thank you for your prayers!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $title = 'New Prayer Request';
        $message = $this->prayerRequest->title;
        
        if ($this->group) {
            $title = "New Prayer Request in {$this->group->name}";
        }
        
        return [
            'type' => 'prayer_request.received',
            'title' => $title,
            'message' => $message,
            'prayer_request_id' => $this->prayerRequest->id,
            'group_id' => $this->group ? $this->group->id : null,
            'url' => "/prayer-requests/{$this->prayerRequest->id}",
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
        $message = "New prayer request: {$this->prayerRequest->title}";
        
        if ($this->group) {
            $message = "[{$this->group->name}] $message";
        }
        
        return $message;
    }
}
