<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 week', '+3 months');
        $endDate = fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +2 weeks');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'destination_country' => fake()->country(),
            'destination_city' => fake()->city(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'planning',
            'is_group' => fake()->boolean(30), // 30% chance of being a group trip
            'progress' => fake()->randomElement([null, 'itinerary_complete', 'checklist_ready', 'ready_to_go']),
        ];
    }

    /**
     * Indicate that the trip is ongoing.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ongoing',
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(4),
        ]);
    }

    /**
     * Indicate that the trip is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonths(2)->addDays(7),
        ]);
    }

    /**
     * Indicate that the trip is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the trip is a group trip.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_group' => true,
        ]);
    }

    /**
     * Indicate that the trip is solo.
     */
    public function solo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_group' => false,
        ]);
    }
}
