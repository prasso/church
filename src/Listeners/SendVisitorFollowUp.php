<?php

namespace Prasso\Church\Listeners;

use Prasso\Church\Events\VisitorCreated;
use Prasso\Church\Services\ChurchMessagingService;

class SendVisitorFollowUp
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
     * @param  \Prasso\Church\Events\VisitorCreated  $event
     * @return void
     */
    public function handle(VisitorCreated $event)
    {
        // Only send follow-up if the visitor provided an email or phone
        if ($event->visitor->email || $event->visitor->phone) {
            $subject = 'Thanks for Visiting ' . config('app.name');
            $body = view('church::emails.visitor-followup', [
                'visitor' => $event->visitor,
                'message' => 'We enjoyed having you with us!',
            ])->render();

            $type = $event->visitor->email ? 'email' : 'sms';
            
            $this->messagingService->sendToVisitor(
                $event->visitor,
                $subject,
                $body,
                $type,
                ['template' => 'visitor_followup', 'category' => 'follow_up']
            );
        }
    }
}
