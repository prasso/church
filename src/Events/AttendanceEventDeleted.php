<?php

namespace Prasso\Church\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Prasso\Church\Models\AttendanceEvent;

class AttendanceEventDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * The attendance event instance.
     *
     * @var \Prasso\Church\Models\AttendanceEvent
     */
    public $event;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    public function __construct(AttendanceEvent $event)
    {
        $this->event = $event;
    }
}
