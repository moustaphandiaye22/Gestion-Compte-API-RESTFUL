<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalAccessClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier si le client d'accès personnel existe déjà
        $existingClient = DB::table('oauth_clients')->where('personal_access_client', 1)->first();

        if (!$existingClient) {
            // Insérer le client d'accès personnel requis par Passport
            $clientId = DB::table('oauth_clients')->insertGetId([
                'user_id' => null,
                'name' => 'Laravel Personal Access Client',
                'secret' => null,
                'provider' => null,
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insérer également dans oauth_personal_access_clients
            DB::table('oauth_personal_access_clients')->insert([
                'client_id' => $clientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Personal access client created successfully.');
        } else {
            $this->command->info('Personal access client already exists.');
        }
    }
}
