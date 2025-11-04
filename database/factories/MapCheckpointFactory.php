<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MapCheckpoint>
 */
class MapCheckpointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'place_id' => null,
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement([
                'Tokyo Tower Visit',
                'Shibuya Crossing',
                'Mount Fuji Viewpoint',
                'Senso-ji Temple',
                'Akihabara Stop',
            ]),
            'lat' => $this->faker->latitude(35, 36),
            'lng' => $this->faker->longitude(139, 140),
            'checked_in_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'note' => $this->faker->optional()->sentence(),
        ];
    }

    public function withPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'place_id' => Place::factory(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_in_at' => now(),
        ]);
    }

    public function notCheckedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_in_at' => null,
        ]);
    }
}
