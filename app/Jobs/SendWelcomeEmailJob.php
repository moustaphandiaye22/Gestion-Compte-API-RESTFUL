<?php

namespace App\Jobs;

use App\Models\Client;
use App\Mail\ClientWelcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $client;
    public $password;

    /**
     * Create a new job instance.
     */
    public function __construct(Client $client, string $password)
    {
        $this->client = $client;
        $this->password = $password;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Vérifier que le client existe toujours
            if (!$this->client) {
                Log::warning('Client not found for welcome email job');
                return;
            }

            // Utiliser le mailer de fallback en production si nécessaire
            $mailer = app()->environment('production') ? 'production_safe' : null;

            Mail::mailer($mailer)->to($this->client->email)->send(new ClientWelcomeMail($this->client, $this->password));

            Log::info('Welcome email sent successfully to: ' . $this->client->email);
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            // Ne pas relancer l'exception pour éviter les retries inutiles
        }
    }
}