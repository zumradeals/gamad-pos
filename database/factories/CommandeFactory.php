<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Commande;
use App\Models\PointDeVente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commande>
 */
class CommandeFactory extends Factory
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
            'point_de_vente_id' => PointDeVente::factory(),
            'statut' => Commande::STATUT_EN_ATTENTE,
            'montant_total' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
