<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Prasso\Church\Models\AttendanceEvent;

class AttendanceEventCreatedNotification extends AttendanceNotification
{
    use Queueable;

    /**
     * The attendance event instance.
     *
     * @var \Prasso\Church\Models\AttendanceEvent
     */
    public $event;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    public function __construct(AttendanceEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's subject.
     *
     * @return string
     */
    protected function getSubject()
    {
        return 'New Attendance Event: ' . $this->event->name;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $details = [
            'Event' => $this->event->name,
            'Date' => $this->event->start_time->format('F j, Y'),
            'Time' => $this->event->start_time->format('g:i A'),
        ];

        if ($this->event->location) {
            $details['Location'] = $this->event->location->name;
        }

        if ($this->event->description) {
            $details['Description'] = $this->event->description;
        }

        $url = route('attendance.events.show', $this->event->id);

        return $this->buildMailMessage(
            'New Attendance Event Created',
            'A new attendance event has been created with the following details:',
            $details,
            'View Event',
            $url
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'attendance_event_created',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'event_date' => $this->event->start_time->toIso8601String(),
            'url' => route('attendance.events.show', $this->event->id),
        ];
    }
}
