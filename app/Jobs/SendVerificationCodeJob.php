<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\SmsServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVerificationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $client;

    /**
     * Create a new job instance.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsServiceInterface $smsService): void
    {
        try {
            // Vérifier que le client et son user existent toujours
            if (!$this->client || !$this->client->user) {
                Log::warning('Client or user not found for verification code job');
                return;
            }

            $message = "Votre code est: {$this->client->user->code}";
            $smsService->send($this->client->telephone, $message);
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
        }
    }
}