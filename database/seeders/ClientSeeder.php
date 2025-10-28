<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Client::factory(10)->create()->each(function ($client) {
            $user = \App\Models\User::factory()->create([
                'email' => $client->email, // Use client's email
                'password' => bcrypt('password'),
                'userable_id' => $client->id,
                'userable_type' => \App\Models\Client::class,
            ]);
            $client->user()->save($user);
        });
    }
}
