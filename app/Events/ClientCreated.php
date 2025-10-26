<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreated
{
    use Dispatchable, SerializesModels;

    public $client;
    public $password;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, string $password)
    {
         $this->client = $client;
         $this->password = $password;
    }
}
