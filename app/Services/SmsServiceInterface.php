<?php

namespace App\Services;

interface SmsServiceInterface
{
    public function send(string $to, string $message): bool;
}