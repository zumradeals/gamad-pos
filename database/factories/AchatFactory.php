<?php

namespace Database\Factories;

use App\Models\Achat;
use App\Models\Depot;
use App\Models\Entreprise;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achat>
 */
class AchatFactory extends Factory
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
            'fournisseur_id' => Fournisseur::factory(),
            'user_id' => User::factory(),
            'emplacement_type' => Depot::class,
            'emplacement_id' => Depot::factory(),
            'statut' => Achat::STATUT_VALIDEE,
            'montant_total' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
