<?php

namespace Database\Factories;

use App\Models\Depot;
use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Depot>
 */
class DepotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entreprise_id' => Entreprise::factory(),
            'nom' => fake()->words(2, true),
            'adresse' => fake()->streetAddress(),
        ];
    }
}
