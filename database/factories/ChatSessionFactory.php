<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatSession>
 */
class ChatSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 week', 'now');
        
        return [
            'user_id' => User::factory(),
            'trip_id' => fake()->boolean(70) ? Trip::factory() : null, // 70% have associated trip
            'session_type' => fake()->randomElement(['trip_planning', 'itinerary_building', 'place_search', 'recommendation']),
            'context' => [
                'destination' => fake()->city() . ', ' . fake()->country(),
                'budget_range' => fake()->randomElement(['budget', 'moderate', 'luxury']),
                'travel_style' => fake()->randomElement(['adventure', 'relaxation', 'cultural', 'food', 'nature', 'urban']),
                'group_size' => fake()->numberBetween(1, 8),
            ],
            'is_active' => fake()->boolean(60), // 60% active sessions
            'started_at' => $startedAt,
            'ended_at' => fake()->boolean(40) ? fake()->dateTimeBetween($startedAt, 'now') : null,
        ];
    }

    /**
     * Indicate that the session is for trip planning.
     */
    public function tripPlanning(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'trip_planning',
            'trip_id' => null, // Planning sessions start without a trip
            'context' => array_merge($attributes['context'] ?? [], [
                'stage' => 'initial_planning',
            ]),
        ]);
    }

    /**
     * Indicate that the session is for itinerary building.
     */
    public function itineraryBuilding(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'itinerary_building',
            'trip_id' => Trip::factory(),
            'context' => array_merge($attributes['context'] ?? [], [
                'current_day' => fake()->numberBetween(1, 7),
                'preferences' => fake()->randomElement(['morning_person', 'night_owl', 'flexible']),
            ]),
        ]);
    }

    /**
     * Indicate that the session is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the session has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'ended_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }

    /**
     * Indicate that the session is for place search.
     */
    public function placeSearch(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'place_search',
            'context' => array_merge($attributes['context'] ?? [], [
                'search_query' => fake()->words(3, true),
                'category' => fake()->randomElement(['restaurant', 'attraction', 'hotel', 'cafe', 'shopping']),
            ]),
        ]);
    }

    /**
     * Indicate that the session is for recommendations.
     */
    public function recommendation(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'recommendation',
            'trip_id' => Trip::factory(),
            'context' => array_merge($attributes['context'] ?? [], [
                'recommendation_focus' => fake()->randomElement(['activities', 'dining', 'accommodation', 'transportation']),
            ]),
        ]);
    }
}
