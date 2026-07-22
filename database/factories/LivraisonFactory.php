<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Livraison;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Livraison>
 */
class LivraisonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vente_id' => Vente::factory(),
            'client_id' => Client::factory(),
            'lieu' => fake()->streetAddress(),
            'date_prevue' => null,
            'responsable_user_id' => null,
            'statut' => Livraison::STATUT_PLANIFIEE,
            'preuve' => null,
        ];
    }
}
