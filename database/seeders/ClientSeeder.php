<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client
        $client = Client::create([
            'prenom' => 'Moustapha',
            'nom' => 'Ndiaye',
            'cni' => '1234567890123A',
            'telephone' => '+221777669595',
            'email' => 'viziodev03@gmail.com',
            'adresse' => 'Dakar, Senegal',
        ]);

        // Créer l'utilisateur associé
        User::create([
            'email' => 'viziodev03@gmail.com',
            'password' => Hash::make('password123'),
            'code' => 'CLIENT001',
            'userable_id' => $client->id,
            'userable_type' => Client::class,
        ]);

        // Créer un deuxième client pour les tests
        $client2 = Client::create([
            'prenom' => 'Fatou',
            'nom' => 'Diallo',
            'cni' => '9876543210987B',
            'telephone' => '+221771234569',
            'email' => 'fatou.diallo@example.com',
            'adresse' => 'Saint-Louis, Senegal',
        ]);

        User::create([
            'email' => 'fatou.diallo@example.com',
            'password' => Hash::make('password123'),
            'code' => 'CLIENT002',
            'userable_id' => $client2->id,
            'userable_type' => Client::class,
        ]);
    }
}
