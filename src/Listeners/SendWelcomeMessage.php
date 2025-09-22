<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\MemberCreated;
use Prasso\Church\Services\ChurchMessagingService;

class SendWelcomeMessage
{
    /**
     * The messaging service instance.
     *
     * @var \Prasso\Church\Services\ChurchMessagingService
     */
    protected $messagingService;

    /**
     * Create the event listener.
     *
     * @param  \Prasso\Church\Services\ChurchMessagingService  $messagingService
     * @return void
     */
    public function __construct(ChurchMessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\MemberCreated  $event
     * @return void
     */
    public function handle(MemberCreated $event)
    {
        // Only send welcome email if the member has an email address
        if ($event->member->email) {
            $this->messagingService->sendWelcomeMessage($event->member);
        }
    }
}
