<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\PointDeVente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'nom' => fake()->name(),
            'telephone' => fake()->phoneNumber(),
        ];
    }
}
