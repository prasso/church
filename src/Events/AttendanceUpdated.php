<?php

namespace Prasso\Church\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Prasso\Church\Models\AttendanceRecord;

class AttendanceUpdated
{
    use Dispatchable, SerializesModels;

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
     * Create a new event instance.
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
}
