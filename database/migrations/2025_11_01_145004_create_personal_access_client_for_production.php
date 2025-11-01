<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer les clients existants pour éviter les conflits
        $existingClients = DB::table('oauth_clients')->where('personal_access_client', 1)->get();

        foreach ($existingClients as $client) {
            DB::table('oauth_personal_access_clients')->where('client_id', $client->id)->delete();
            DB::table('oauth_clients')->where('id', $client->id)->delete();
        }

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer le client d'accès personnel
        $client = DB::table('oauth_clients')->where('personal_access_client', 1)->first();
        if ($client) {
            DB::table('oauth_personal_access_clients')->where('client_id', $client->id)->delete();
            DB::table('oauth_clients')->where('id', $client->id)->delete();
        }
    }
};
