<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItineraryItem>
 */
class ItineraryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(6, 20);
        $startTime = sprintf('%02d:00:00', $startHour);
        $endTime = sprintf('%02d:00:00', $startHour + $this->faker->numberBetween(1, 4));

        return [
            'trip_id' => Trip::factory(),
            'title' => $this->faker->randomElement([
                'Visit Tokyo Tower',
                'Lunch at local restaurant',
                'Museum tour',
                'Shopping district',
                'Evening walk',
            ]),
            'day_number' => $this->faker->numberBetween(1, 7),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'place_id' => null,
            'note' => $this->faker->optional()->sentence(),
        ];
    }

    public function withPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'place_id' => Place::factory(),
        ]);
    }

    public function forDay(int $day): static
    {
        return $this->state(fn (array $attributes) => [
            'day_number' => $day,
        ]);
    }
}
