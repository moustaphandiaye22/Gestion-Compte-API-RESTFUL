<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockSmsService implements SmsServiceInterface
{
    public function send(string $to, string $message): bool
    {
        Log::info("Mock SMS sent to {$to}: {$message}");
        return true;
    }
}