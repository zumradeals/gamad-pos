<?php

namespace Database\Factories;

use App\Models\MouvementCaisse;
use App\Models\PointDeVente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MouvementCaisse>
 */
class MouvementCaisseFactory extends Factory
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
            'type' => MouvementCaisse::TYPE_ENTREE,
            'montant' => fake()->randomFloat(2, 10, 1000),
            'motif' => fake()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
