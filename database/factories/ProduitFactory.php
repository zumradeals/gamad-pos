<?php

namespace Database\Factories;

use App\Models\PointDeVente;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produit>
 */
class ProduitFactory extends Factory
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
            'nom' => fake()->words(2, true),
            'prix_vente' => fake()->randomFloat(2, 100, 10000),
            'unite' => fake()->randomElement(['unite', 'kg', 'litre', 'carton']),
        ];
    }
}
