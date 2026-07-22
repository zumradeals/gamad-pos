<?php

namespace Database\Factories;

use App\Models\Depense;
use App\Models\PointDeVente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Depense>
 */
class DepenseFactory extends Factory
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
            'categorie' => fake()->randomElement(['loyer', 'transport', 'fournitures', 'electricite', 'autre']),
            'montant' => fake()->randomFloat(2, 10, 5000),
            'justificatif' => fake()->bothify('REF-####'),
            'user_id' => User::factory(),
            'statut' => Depense::STATUT_ENREGISTREE,
        ];
    }
}
