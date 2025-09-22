<?php

namespace Prasso\Church\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Prasso\Church\Models\AttendanceRecord;

class AttendanceDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * The attendance record instance.
     *
     * @var \Prasso\Church\Models\AttendanceRecord
     */
    public $record;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Church\Models\AttendanceRecord  $record
     * @return void
     */
    public function __construct(AttendanceRecord $record)
    {
        $this->record = $record;
    }
}
