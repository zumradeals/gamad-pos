<?php

namespace Database\Factories;

use App\Models\Abonnement;
use App\Models\Entreprise;
use App\Models\Offre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Abonnement>
 */
class AbonnementFactory extends Factory
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
            'offre_id' => Offre::factory(),
            'statut' => Abonnement::STATUT_ACTIF,
            'date_debut' => now()->subMonth()->toDateString(),
            'date_echeance' => now()->addMonth()->toDateString(),
        ];
    }
}
