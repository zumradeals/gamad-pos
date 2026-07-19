<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\PointDeVente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointDeVente>
 */
class PointDeVenteFactory extends Factory
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
            'nom' => fake()->streetName(),
            'adresse' => fake()->address(),
        ];
    }
}
