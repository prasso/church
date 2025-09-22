<?php

namespace Prasso\Church\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Prasso\Church\Models\PrayerRequest;

class PrayerRequestCreated
{
    use Dispatchable, SerializesModels;

    public $prayerRequest;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @return void
     */
    public function __construct(PrayerRequest $prayerRequest)
    {
        $this->prayerRequest = $prayerRequest;
    }
}
