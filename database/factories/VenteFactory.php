<?php

namespace Database\Factories;

use App\Models\PointDeVente;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vente>
 */
class VenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'point_de_vente_id' => PointDeVente::factory(),
            'user_id' => User::factory(),
            'statut' => Vente::STATUT_VALIDEE,
            'montant_total' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
