<?php

namespace Database\Factories;

use App\Models\DetteFournisseur;
use App\Models\VersementFournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersementFournisseur>
 */
class VersementFournisseurFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dette_fournisseur_id' => DetteFournisseur::factory(),
            'montant' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
