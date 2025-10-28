<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numeroCompte' => $this->faker->numerify('############'),
            'type' => $this->faker->randomElement(['Depot', 'Retrait', 'Transfert']),
            'montant' => $this->faker->randomFloat(2, 1000, 100000),
            'dateTransaction' => now(),
            'description' => $this->faker->sentence,
            'statut' => $this->faker->randomElement(['En attente', 'Validee', 'Annulee']),
            'compte_id' => \App\Models\Compte::factory(),
        ];
    }
}
