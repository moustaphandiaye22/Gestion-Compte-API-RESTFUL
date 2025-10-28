<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $client = \App\Models\Client::factory()->create();

        return [
            'numeroCompte' => null, // Let the model boot method generate it
            'titulaire' => $client->titulaire, // Use client's titulaire
            'type' => $this->faker->randomElement(['Epargne', 'Cheque']),
            'devise' => 'FCFA',
            'dateCreation' => now()->toDateString(),
            'statut' => $this->faker->randomElement(['Actif', 'Bloque', 'Ferme']),
            'metadata' => json_encode(['notes' => $this->faker->sentence]),
            'client_id' => $client->id,
        ];
    }
}
