<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Creance;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Creance>
 */
class CreanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'vente_id' => Vente::factory(),
            'montant_initial' => fake()->randomFloat(2, 100, 10000),
            'echeance' => null,
            'statut' => Creance::STATUT_OUVERTE,
        ];
    }
}
