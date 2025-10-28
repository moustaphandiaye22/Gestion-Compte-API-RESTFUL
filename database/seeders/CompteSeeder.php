<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Compte::factory(20)->create()->each(function ($compte) {
            // Ensure initial deposit >= 10000
            \App\Models\Transaction::factory()->create([
                'compte_id' => $compte->id,
                'type' => 'Depot',
                'montant' => fake()->randomFloat(2, 10000, 50000),
                'statut' => 'Validee',
            ]);

            // Additional random transactions
            \App\Models\Transaction::factory(rand(0, 9))->create([
                'compte_id' => $compte->id,
            ]);
        });
    }
}
