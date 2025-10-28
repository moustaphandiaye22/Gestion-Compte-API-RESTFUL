<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use App\Jobs\SendWelcomeEmailJob;
use App\Jobs\SendVerificationCodeJob;

class SendClientNotification
{
    /**
     * Handle the event.
     */
    public function handle(ClientCreated $event): void
    {
        $client = $event->client;
        $password = $event->password;

        // Dispatch email job
        SendWelcomeEmailJob::dispatch($client, $password);

        // Dispatch SMS job
        SendVerificationCodeJob::dispatch($client);
    }
}
