<?php

namespace Database\Factories;

use App\Models\AppareilMemorise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppareilMemorise>
 */
class AppareilMemoriseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => Str::uuid()->toString(),
            'token' => Str::random(64),
            'memorized_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function revoque(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
