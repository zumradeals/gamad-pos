<?php

namespace Database\Factories;

use App\Models\Achat;
use App\Models\DetteFournisseur;
use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetteFournisseur>
 */
class DetteFournisseurFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fournisseur_id' => Fournisseur::factory(),
            'achat_id' => Achat::factory(),
            'montant_initial' => fake()->randomFloat(2, 100, 10000),
            'statut' => DetteFournisseur::STATUT_OUVERTE,
        ];
    }
}
