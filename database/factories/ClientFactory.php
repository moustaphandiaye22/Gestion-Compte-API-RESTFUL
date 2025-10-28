<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $senegalesePrefixes = ['30', '33', '70', '72', '75', '76', '77', '78'];
        $prefix = $this->faker->randomElement($senegalesePrefixes);
        $number = $this->faker->numerify('#######');

        return [
            'prenom' => $this->faker->firstName,
            'nom' => $this->faker->lastName,
            'cni' => $this->faker->unique()->numerify('#############'), // 13 digits
            'telephone' => '+221' . $prefix . $number,
            'email' => $this->faker->unique()->safeEmail,
            'adresse' => $this->faker->address,
        ];
    }
}
