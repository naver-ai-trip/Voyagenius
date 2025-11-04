<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChecklistItem>
 */
class ChecklistItemFactory extends Factory
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
            'content' => fake()->sentence(),
            'is_checked' => fake()->boolean(30), // 30% chance of being checked
        ];
    }

    /**
     * Indicate that the checklist item is checked.
     */
    public function checked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_checked' => true,
        ]);
    }

    /**
     * Indicate that the checklist item is unchecked.
     */
    public function unchecked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_checked' => false,
        ]);
    }
}
