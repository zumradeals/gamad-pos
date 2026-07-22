<?php

namespace Database\Factories;

use App\Models\Offre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offre>
 */
class OffreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'nom' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'limite_points_de_vente' => 1,
            'limite_utilisateurs' => 3,
        ];
    }
}
