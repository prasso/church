<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

abstract class AttendanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
            'database' => 'notifications',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    abstract public function toMail($notifiable);

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    abstract public function toArray($notifiable);

    /**
     * Format the notification's message with common elements.
     *
     * @param  string  $greeting
     * @param  string  $message
     * @param  array  $details
     * @param  string  $actionText
     * @param  string  $actionUrl
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($greeting, $message, $details = [], $actionText = null, $actionUrl = null)
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting($greeting)
            ->line($message);

        // Add details if provided
        foreach ($details as $label => $value) {
            if (is_array($value)) {
                $mailMessage->line($label . ':');
                foreach ($value as $item) {
                    $mailMessage->line(new HtmlString("&nbsp;&nbsp;• " . e($item)));
                }
            } else {
                $mailMessage->line("**{$label}:** {$value}");
            }
        }

        // Add action button if provided
        if ($actionText && $actionUrl) {
            $mailMessage->action($actionText, $actionUrl);
        }

        return $mailMessage;
    }

    /**
     * Get the notification's subject.
     *
     * @return string
     */
    abstract protected function getSubject();
}
