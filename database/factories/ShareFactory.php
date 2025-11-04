<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Share>
 */
class ShareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => \App\Models\Trip::factory(),
            'user_id' => \App\Models\User::factory(),
            'permission' => $this->faker->randomElement(['viewer', 'editor']),
            'token' => \Illuminate\Support\Str::random(32),
        ];
    }
}
