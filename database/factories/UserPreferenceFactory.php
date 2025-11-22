<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            'travel_style',
            'budget_range',
            'dietary_restrictions',
            'accommodation_type',
            'activity_level',
            'language_preference',
        ]);

        return [
            'user_id' => User::factory(),
            'preference_type' => $type,
            'value' => $this->generateValueByType($type),
            'priority' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Generate preference value based on type.
     */
    private function generateValueByType(string $type): array
    {
        return match($type) {
            'travel_style' => [
                'styles' => fake()->randomElements(['adventure', 'relaxation', 'cultural', 'food', 'nature', 'urban'], fake()->numberBetween(1, 3)),
                'primary' => fake()->randomElement(['adventure', 'relaxation', 'cultural', 'food', 'nature', 'urban']),
            ],
            'budget_range' => [
                'min' => fake()->numberBetween(50000, 200000),
                'max' => fake()->numberBetween(500000, 2000000),
                'currency' => 'KRW',
                'category' => fake()->randomElement(['budget', 'moderate', 'luxury']),
            ],
            'dietary_restrictions' => [
                'restrictions' => fake()->randomElements(['vegetarian', 'vegan', 'halal', 'kosher', 'gluten-free', 'lactose-free'], fake()->numberBetween(0, 2)),
                'allergies' => fake()->boolean(30) ? fake()->words(2) : [],
            ],
            'accommodation_type' => [
                'preferred_types' => fake()->randomElements(['hotel', 'hostel', 'guesthouse', 'resort', 'airbnb'], fake()->numberBetween(1, 3)),
                'must_have_amenities' => fake()->randomElements(['wifi', 'breakfast', 'gym', 'pool', 'parking'], fake()->numberBetween(1, 3)),
            ],
            'activity_level' => [
                'level' => fake()->randomElement(['low', 'moderate', 'high', 'very_high']),
                'daily_walking' => fake()->numberBetween(2, 20) . ' km',
                'early_riser' => fake()->boolean(),
            ],
            'language_preference' => [
                'preferred_languages' => fake()->randomElements(['en', 'ko', 'ja', 'zh', 'es'], fake()->numberBetween(1, 3)),
                'auto_translate' => fake()->boolean(80),
            ],
            default => [
                'value' => fake()->word(),
            ],
        };
    }

    /**
     * Create a travel style preference.
     */
    public function travelStyle(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_type' => 'travel_style',
            'value' => $this->generateValueByType('travel_style'),
        ]);
    }

    /**
     * Create a budget range preference.
     */
    public function budgetRange(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_type' => 'budget_range',
            'value' => $this->generateValueByType('budget_range'),
        ]);
    }

    /**
     * Create a dietary restrictions preference.
     */
    public function dietaryRestrictions(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_type' => 'dietary_restrictions',
            'value' => $this->generateValueByType('dietary_restrictions'),
        ]);
    }

    /**
     * Create a high priority preference.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(8, 10),
        ]);
    }

    /**
     * Create a low priority preference.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Create an accommodation type preference.
     */
    public function accommodationType(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_type' => 'accommodation_type',
            'value' => $this->generateValueByType('accommodation_type'),
        ]);
    }

    /**
     * Create an activity level preference.
     */
    public function activityLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'preference_type' => 'activity_level',
            'value' => $this->generateValueByType('activity_level'),
        ]);
    }
}
