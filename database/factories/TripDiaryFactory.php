<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TripDiary>
 */
class TripDiaryFactory extends Factory
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
            'user_id' => User::factory(),
            'entry_date' => $this->faker->date(),
            'text' => $this->faker->paragraph(),
            'mood' => $this->faker->randomElement(['happy', 'excited', 'tired', 'relaxed', 'adventurous']),
        ];
    }
}
