<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

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
            'password' => static::$password ??= 'password',
            'provider' => 'native',
            'provider_id' => null,
            'avatar_path' => null,
            'trip_style' => fake()->randomElement(['adventure', 'relaxation', 'cultural', 'food', 'nature', 'urban']),
            'naver_id' => null,
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Indicate that the user registered via NAVER social auth.
     */
    public function naverAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'naver',
            'provider_id' => 'naver_' . fake()->unique()->numberBetween(100000, 999999),
            'naver_id' => 'naver_' . fake()->unique()->numberBetween(100000, 999999),
            'password' => null,
        ]);
    }

    /**
     * Indicate that the user registered via Google social auth.
     */
    public function googleAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'google',
            'provider_id' => 'google_' . fake()->unique()->numberBetween(100000, 999999),
            'password' => null,
        ]);
    }

    /**
     * Indicate that the user has an avatar.
     */
    public function withAvatar(): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar_path' => 'avatars/' . fake()->uuid() . '.jpg',
        ]);
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
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
