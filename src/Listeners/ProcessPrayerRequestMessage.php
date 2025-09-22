<?php

namespace Prasso\Church\Listeners;

use Illuminate\Support\Facades\Log;
use Prasso\Church\Events\InboundMessageReceived;
use Prasso\Church\Services\SmsPrayerRequestService;

class ProcessPrayerRequestMessage
{
    /**
     * The SMS prayer request service.
     *
     * @var \Prasso\Church\Services\SmsPrayerRequestService
     */
    protected $prayerRequestService;

    /**
     * Create the event listener.
     *
     * @param  \Prasso\Church\Services\SmsPrayerRequestService  $prayerRequestService
     * @return void
     */
    public function __construct(SmsPrayerRequestService $prayerRequestService)
    {
        $this->prayerRequestService = $prayerRequestService;
    }

    /**
     * Handle the event.
     *
     * @param  \Prasso\Church\Events\InboundMessageReceived  $event
     * @return void
     */
    public function handle(InboundMessageReceived $event)
    {
        $inboundMessage = $event->inboundMessage;
        
        // Check if this is a prayer request
        if ($this->prayerRequestService->isPrayerRequest($inboundMessage)) {
            Log::info('Processing inbound message as prayer request', [
                'message_id' => $inboundMessage->id,
                'from' => $inboundMessage->from,
            ]);
            
            // Get campaign data if available
            $campaignData = [];
            if (!empty($inboundMessage->raw['campaign_id'])) {
                $campaignData['campaign_id'] = $inboundMessage->raw['campaign_id'];
                $campaignData['type'] = 'prayer_request';
            }
            
            // Process the prayer request
            $prayerRequest = $this->prayerRequestService->processPrayerRequestMessage($inboundMessage, $campaignData);
            
            // Send confirmation to the sender
            $this->prayerRequestService->sendConfirmation($prayerRequest);
            
            Log::info('Prayer request processed and confirmation sent', [
                'prayer_request_id' => $prayerRequest->id,
                'from' => $inboundMessage->from,
            ]);
        }
    }
}
