<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fournisseur>
 */
class FournisseurFactory extends Factory
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
            'nom' => fake()->company(),
            'telephone' => fake()->numerify('+2376########'),
            'conditions_commerciales' => fake()->sentence(),
            'delais_habituels' => '15 jours',
        ];
    }
}
