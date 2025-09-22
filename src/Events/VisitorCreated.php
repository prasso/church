<?php

namespace Prasso\Church\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Prasso\Church\Models\Visitor;

class VisitorCreated
{
    use Dispatchable;

    /**
     * The visitor instance.
     *
     * @var \Prasso\Church\Models\Visitor
     */
    public $visitor;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Church\Models\Visitor  $visitor
     * @return void
     */
    public function __construct(Visitor $visitor)
    {
        $this->visitor = $visitor;
    }
}
