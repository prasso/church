<?php

namespace Prasso\Church\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Prasso\Church\Events\InboundMessageReceived;
use Prasso\Messaging\Models\MsgInboundMessage;

class MessagingIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Listen for the created event on MsgInboundMessage model
        MsgInboundMessage::created(function ($inboundMessage) {
            // Dispatch our custom event
            event(new InboundMessageReceived($inboundMessage));
        });
    }
}
