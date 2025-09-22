<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Prasso\Church\Models\AttendanceRecord;

class AttendanceUpdatedNotification extends AttendanceNotification
{
    use Queueable;

    /**
     * The attendance record instance.
     *
     * @var \Prasso\Church\Models\AttendanceRecord
     */
    public $record;

    /**
     * The original record data before changes.
     *
     * @var array
     */
    public $original;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @param  array  $original
     * @return void
     */
    public function __construct(AttendanceRecord $record, array $original)
    {
        $this->record = $record;
        $this->original = $original;
    }

    /**
     * Get the notification's subject.
     *
     * @return string
     */
    protected function getSubject()
    {
        $subject = 'Attendance Updated: ';
        
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
        ];

        if ($this->record->member) {
            $details['Member'] = $this->record->member->full_name;
        }
        
        if ($this->record->family) {
            $details['Family'] = $this->record->family->name;
        }
        
        // Show what changed
        $changes = [];
        
        if (isset($this->original['status']) && $this->original['status'] !== $this->record->status) {
            $changes[] = "Status: " . ucfirst($this->original['status'] ?? 'none') . " → " . ucfirst($this->record->status);
        }
        
        if (isset($this->original['check_in_time']) && $this->original['check_in_time'] != $this->record->check_in_time) {
            $originalTime = $this->record->check_in_time->copy()->setTimestamp(strtotime($this->original['check_in_time']));
            $changes[] = "Check-in Time: " . $originalTime->format('g:i A') . " → " . $this->record->check_in_time->format('g:i A');
        }
        
        if (isset($this->original['check_out_time'])) {
            $originalOutTime = $this->original['check_out_time'] ? 
                $this->record->check_out_time->copy()->setTimestamp(strtotime($this->original['check_out_time']))->format('g:i A') : 
                'None';
            $newOutTime = $this->record->check_out_time ? $this->record->check_out_time->format('g:i A') : 'None';
            
            if ($originalOutTime !== $newOutTime) {
                $changes[] = "Check-out Time: {$originalOutTime} → {$newOutTime}";
            }
        }
        
        if (isset($this->original['guest_count']) && $this->original['guest_count'] != $this->record->guest_count) {
            $changes[] = "Guest Count: " . ($this->original['guest_count'] ?? 0) . " → " . $this->record->guest_count;
        }
        
        if (count($changes) > 0) {
            $details['Changes'] = $changes;
        }
        
        if ($this->record->notes) {
            $details['Notes'] = $this->record->notes;
        }

        $url = route('attendance.records.show', $this->record->id);

        return $this->buildMailMessage(
            'Attendance Record Updated',
            'An attendance record has been updated with the following changes:',
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
        $changes = [];
        
        if (isset($this->original['status'])) {
            $changes['status'] = [
                'from' => $this->original['status'],
                'to' => $this->record->status,
            ];
        }
        
        if (isset($this->original['check_in_time'])) {
            $changes['check_in_time'] = [
                'from' => $this->original['check_in_time'],
                'to' => $this->record->check_in_time->toIso8601String(),
            ];
        }
        
        if (isset($this->original['check_out_time'])) {
            $changes['check_out_time'] = [
                'from' => $this->original['check_out_time'],
                'to' => $this->record->check_out_time ? $this->record->check_out_time->toIso8601String() : null,
            ];
        }
        
        if (isset($this->original['guest_count'])) {
            $changes['guest_count'] = [
                'from' => $this->original['guest_count'],
                'to' => $this->record->guest_count,
            ];
        }
        
        $data = [
            'type' => 'attendance_updated',
            'record_id' => $this->record->id,
            'event_id' => $this->record->event_id,
            'event_name' => $this->record->event->name,
            'changes' => $changes,
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
