<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'naver_place_id' => 'naver_' . fake()->unique()->numberBetween(100000, 999999),
            'name' => fake()->company() . ' ' . fake()->randomElement(['Tower', 'Museum', 'Park', 'Restaurant', 'Cafe', 'Temple', 'Castle']),
            'address' => fake()->address(),
            'lat' => fake()->latitude(33, 43), // Japan/Korea latitude range
            'lng' => fake()->longitude(125, 145), // Japan/Korea longitude range
            'category' => fake()->randomElement(['Tourism', 'Restaurant', 'Cafe', 'Shopping', 'Accommodation', 'Transportation', 'Entertainment']),
        ];
    }

    /**
     * Indicate that the place is a tourist attraction.
     */
    public function tourism(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Tourism',
        ]);
    }

    /**
     * Indicate that the place is a restaurant.
     */
    public function restaurant(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Restaurant',
        ]);
    }

    /**
     * Set a specific location (Tokyo Tower example).
     */
    public function tokyoTower(): static
    {
        return $this->state(fn (array $attributes) => [
            'naver_place_id' => 'naver_tokyo_tower',
            'name' => 'Tokyo Tower',
            'address' => '4 Chome-2-8 Shibakoen, Minato City, Tokyo',
            'lat' => 35.6585805,
            'lng' => 139.7454329,
            'category' => 'Tourism',
        ]);
    }
}
