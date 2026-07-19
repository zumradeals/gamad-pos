<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The current PIN being used by the factory.
     */
    protected static ?string $pin;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'telephone' => fake()->unique()->numerify('+2376########'),
            'pin' => static::$pin ??= '1234',
            'entreprise_id' => null,
            'role' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Attach the user to an entreprise with the given role.
     */
    public function pourEntreprise(Entreprise $entreprise, RoleEnum $role): static
    {
        return $this->state(fn (array $attributes) => [
            'entreprise_id' => $entreprise->id,
            'role' => $role,
        ]);
    }
}
