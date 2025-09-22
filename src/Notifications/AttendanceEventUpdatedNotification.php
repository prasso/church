<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Prasso\Church\Models\AttendanceEvent;

class AttendanceEventUpdatedNotification extends AttendanceNotification
{
    use Queueable;

    /**
     * The attendance event instance.
     *
     * @var \Prasso\Church\Models\AttendanceEvent
     */
    public $event;

    /**
     * The original event data before changes.
     *
     * @var array
     */
    public $original;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @param  array  $original
     * @return void
     */
    public function __construct(AttendanceEvent $event, array $original)
    {
        $this->event = $event;
        $this->original = $original;
    }

    /**
     * Get the notification's subject.
     *
     * @return string
     */
    protected function getSubject()
    {
        return 'Attendance Event Updated: ' . $this->event->name;
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
        ];

        // Show what changed
        $changes = [];
        
        if (isset($this->original['name']) && $this->original['name'] !== $this->event->name) {
            $changes[] = "Name: {$this->original['name']} → {$this->event->name}";
        }
        
        if (isset($this->original['start_time']) && $this->original['start_time'] != $this->event->start_time) {
            $originalDate = $this->event->start_time->copy()->setTimestamp(strtotime($this->original['start_time']));
            $changes[] = "Date/Time: {$originalDate->format('F j, Y g:i A')} → {$this->event->start_time->format('F j, Y g:i A')}";
        }
        
        if (isset($this->original['location_id']) && $this->original['location_id'] != $this->event->location_id) {
            $oldLocation = $this->event->location_id ? 
                \Prasso\Church\Models\Location::find($this->original['location_id'])->name ?? 'None' : 
                'None';
            $newLocation = $this->event->location ? $this->event->location->name : 'None';
            $changes[] = "Location: {$oldLocation} → {$newLocation}";
        }
        
        if (isset($this->original['status']) && $this->original['status'] !== $this->event->status) {
            $changes[] = "Status: " . ucfirst($this->original['status'] ?? 'none') . " → " . ucfirst($this->event->status);
        }
        
        if (count($changes) > 0) {
            $details['Changes'] = $changes;
        }

        $url = route('attendance.events.show', $this->event->id);

        return $this->buildMailMessage(
            'Attendance Event Updated',
            'An attendance event has been updated with the following changes:',
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
        $changes = [];
        
        if (isset($this->original['name'])) {
            $changes['name'] = [
                'from' => $this->original['name'],
                'to' => $this->event->name,
            ];
        }
        
        if (isset($this->original['start_time'])) {
            $changes['start_time'] = [
                'from' => $this->original['start_time'],
                'to' => $this->event->start_time->toIso8601String(),
            ];
        }
        
        if (isset($this->original['location_id'])) {
            $changes['location_id'] = [
                'from' => $this->original['location_id'],
                'to' => $this->event->location_id,
            ];
        }
        
        if (isset($this->original['status'])) {
            $changes['status'] = [
                'from' => $this->original['status'],
                'to' => $this->event->status,
            ];
        }
        
        return [
            'type' => 'attendance_event_updated',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'changes' => $changes,
            'url' => route('attendance.events.show', $this->event->id),
        ];
    }
}
