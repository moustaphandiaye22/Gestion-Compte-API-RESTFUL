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
            \App\Models\Transaction::factory(rand(1, 10))->create([
                'compte_id' => $compte->id,
            ]);
        });
    }
}
