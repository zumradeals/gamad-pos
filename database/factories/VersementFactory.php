<?php

namespace Database\Factories;

use App\Models\Creance;
use App\Models\Versement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Versement>
 */
class VersementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creance_id' => Creance::factory(),
            'montant' => fake()->randomFloat(2, 10, 1000),
            'mode' => Versement::MODE_ESPECES,
        ];
    }
}
