<?php

namespace Database\Factories;

use App\Models\LigneLivraison;
use App\Models\Livraison;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LigneLivraison>
 */
class LigneLivraisonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'livraison_id' => Livraison::factory(),
            'quantite' => fake()->randomFloat(3, 1, 100),
            'date' => now(),
        ];
    }
}
