<?php

namespace Prasso\Church\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Prasso\Church\Models\AttendanceEvent;

class AttendanceEventUpdated
{
    use Dispatchable, SerializesModels;

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
     * Create a new event instance.
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
}
