<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use App\Models\Client;
use App\Services\SmsServiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ClientWelcomeMail;

class SendClientNotification
{
    protected SmsServiceInterface $smsService;

    public function __construct(SmsServiceInterface $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(ClientCreated $event): void
    {
           $client = $event->client;
           $password = $event->password;

           // Send email
           try {
               Mail::to($client->email)->send(new ClientWelcomeMail($client, $password));
           } catch (\Exception $e) {
               Log::error('Email sending failed: ' . $e->getMessage());
           }

           // Send SMS using service
           $this->smsService->send($client->telephone, "Votre code est: {$client->user->code}");
      }
}
