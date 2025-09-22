<?php

namespace Prasso\Church\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Prasso\Church\Models\PastoralVisit;

class PastoralVisitCompleted
{
    use Dispatchable, SerializesModels;

    public $visit;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Church\Models\PastoralVisit  $visit
     * @return void
     */
    public function __construct(PastoralVisit $visit)
    {
        $this->visit = $visit;
    }
}
