<?php

namespace Prasso\Church\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Prasso\Messaging\Models\MsgInboundMessage;

class InboundMessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The inbound message instance.
     *
     * @var \Prasso\Messaging\Models\MsgInboundMessage
     */
    public $inboundMessage;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Messaging\Models\MsgInboundMessage  $inboundMessage
     * @return void
     */
    public function __construct(MsgInboundMessage $inboundMessage)
    {
        $this->inboundMessage = $inboundMessage;
    }
}
