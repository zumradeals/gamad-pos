<?php

namespace Database\Factories;

use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entreprise>
 */
class EntrepriseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->company(),
            'secteur_activite' => fake()->randomElement(['Commerce général', 'Alimentation', 'Quincaillerie', 'Textile']),
            'devise' => 'XAF',
            'pays' => 'Cameroun',
        ];
    }
}
