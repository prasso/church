<?php

namespace Prasso\Church\Observers;

use Prasso\Church\Models\AttendanceEvent;
use Prasso\Church\Events\AttendanceEventCreated;
use Prasso\Church\Events\AttendanceEventUpdated;
use Prasso\Church\Events\AttendanceEventDeleted;

class AttendanceEventObserver
{
    /**
     * Handle the AttendanceEvent "created" event.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    public function created(AttendanceEvent $event)
    {
        event(new AttendanceEventCreated($event));
    }

    /**
     * Handle the AttendanceEvent "updated" event.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    public function updated(AttendanceEvent $event)
    {
        event(new AttendanceEventUpdated($event));
    }

    /**
     * Handle the AttendanceEvent "deleted" event.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    public function deleted(AttendanceEvent $event)
    {
        event(new AttendanceEventDeleted($event));
    }

    /**
     * Handle the AttendanceEvent "forceDeleted" event.
     *
     * @param  \Prasso\Church\Models\AttendanceEvent  $event
     * @return void
     */
    public function forceDeleted(AttendanceEvent $event)
    {
        //
    }
}
