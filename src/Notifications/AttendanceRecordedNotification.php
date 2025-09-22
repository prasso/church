<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Prasso\Church\Models\AttendanceRecord;

class AttendanceRecordedNotification extends AttendanceNotification
{
    use Queueable;

    /**
     * The attendance record instance.
     *
     * @var \Prasso\Church\Models\AttendanceRecord
     */
    public $record;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    public function __construct(AttendanceRecord $record)
    {
        $this->record = $record;
    }

    /**
     * Get the notification's subject.
     *
     * @return string
     */
    protected function getSubject()
    {
        $subject = 'Attendance Recorded: ';
        
        if ($this->record->member) {
            $subject .= $this->record->member->full_name;
        } elseif ($this->record->family) {
            $subject .= $this->record->family->name . ' Family';
        } else {
            $subject .= 'Guest';
        }
        
        $subject .= ' - ' . $this->record->event->name;
        
        return $subject;
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
            'Event' => $this->record->event->name,
            'Date' => $this->record->event->start_time->format('F j, Y'),
            'Time' => $this->record->event->start_time->format('g:i A'),
            'Status' => ucfirst($this->record->status),
        ];

        if ($this->record->member) {
            $details['Member'] = $this->record->member->full_name;
        }
        
        if ($this->record->family) {
            $details['Family'] = $this->record->family->name;
        }
        
        if ($this->record->guest_count > 0) {
            $details['Guests'] = $this->record->guest_count;
        }
        
        if ($this->record->notes) {
            $details['Notes'] = $this->record->notes;
        }

        $url = route('attendance.records.show', $this->record->id);

        return $this->buildMailMessage(
            'Attendance Recorded',
            'An attendance record has been created with the following details:',
            $details,
            'View Record',
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
        $data = [
            'type' => 'attendance_recorded',
            'record_id' => $this->record->id,
            'event_id' => $this->record->event_id,
            'event_name' => $this->record->event->name,
            'status' => $this->record->status,
            'url' => route('attendance.records.show', $this->record->id),
        ];
        
        if ($this->record->member_id) {
            $data['member_id'] = $this->record->member_id;
            $data['member_name'] = $this->record->member->full_name ?? 'Unknown Member';
        }
        
        if ($this->record->family_id) {
            $data['family_id'] = $this->record->family_id;
            $data['family_name'] = $this->record->family->name ?? 'Unknown Family';
        }
        
        return $data;
    }
}
