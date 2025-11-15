<?php

namespace Database\Factories;

use App\Models\ChatSession;
use App\Models\Place;
use App\Models\Trip;
use App\Models\ItineraryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentAction>
 */
class AgentActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actionType = fake()->randomElement([
            'create_trip',
            'search_places',
            'add_to_itinerary',
            'translate_content',
            'get_recommendations',
            'update_preferences',
        ]);

        $startedAt = fake()->dateTimeBetween('-1 hour', 'now');
        $completedAt = fake()->boolean(80) ? fake()->dateTimeBetween($startedAt, 'now') : null;

        return [
            'chat_session_id' => ChatSession::factory(),
            'action_type' => $actionType,
            'status' => $completedAt ? fake()->randomElement(['completed', 'failed']) : 'pending',
            'entity_type' => null,
            'entity_id' => null,
            'input_data' => $this->generateInputData($actionType),
            'output_data' => $completedAt ? $this->generateOutputData($actionType) : null,
            'error_message' => null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }

    /**
     * Generate input data based on action type.
     */
    private function generateInputData(string $actionType): array
    {
        return match($actionType) {
            'create_trip' => [
                'destination' => fake()->city() . ', ' . fake()->country(),
                'start_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
                'end_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
                'budget' => fake()->numberBetween(500000, 5000000),
            ],
            'search_places' => [
                'query' => fake()->words(2, true),
                'category' => fake()->randomElement(['restaurant', 'attraction', 'hotel']),
                'location' => fake()->city(),
            ],
            'add_to_itinerary' => [
                'place_name' => fake()->company(),
                'day' => fake()->numberBetween(1, 7),
                'time' => fake()->time('H:i'),
            ],
            'translate_content' => [
                'text' => fake()->sentence(),
                'source_language' => 'en',
                'target_language' => 'ko',
            ],
            'get_recommendations' => [
                'type' => fake()->randomElement(['places', 'activities', 'dining']),
                'preferences' => fake()->words(3),
            ],
            'update_preferences' => [
                'travel_style' => fake()->randomElement(['adventure', 'relaxation', 'cultural']),
                'budget_range' => fake()->randomElement(['budget', 'moderate', 'luxury']),
            ],
            default => [
                'query' => fake()->sentence(),
            ],
        };
    }

    /**
     * Generate output data based on action type.
     */
    private function generateOutputData(string $actionType): array
    {
        return match($actionType) {
            'create_trip' => [
                'trip_id' => fake()->numberBetween(1, 1000),
                'message' => 'Trip created successfully',
            ],
            'search_places' => [
                'results_count' => fake()->numberBetween(5, 50),
                'places' => array_map(fn() => [
                    'name' => fake()->company(),
                    'rating' => fake()->randomFloat(1, 3.0, 5.0),
                ], range(1, 5)),
            ],
            'add_to_itinerary' => [
                'itinerary_item_id' => fake()->numberBetween(1, 1000),
                'message' => 'Added to itinerary',
            ],
            'translate_content' => [
                'translated_text' => fake()->sentence(),
                'confidence' => fake()->randomFloat(2, 0.8, 1.0),
            ],
            'get_recommendations' => [
                'recommendations' => array_map(fn() => [
                    'title' => fake()->sentence(3),
                    'confidence' => fake()->randomFloat(2, 0.7, 1.0),
                ], range(1, 3)),
            ],
            'update_preferences' => [
                'message' => 'Preferences updated',
                'updated_fields' => ['travel_style', 'budget_range'],
            ],
            default => [
                'result' => 'Action completed',
            ],
        };
    }

    /**
     * Indicate that the action was completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
            'output_data' => $this->generateOutputData($attributes['action_type']),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the action failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'completed_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
            'output_data' => null,
            'error_message' => fake()->randomElement([
                'API rate limit exceeded',
                'Invalid input parameters',
                'External service unavailable',
                'Insufficient permissions',
            ]),
        ]);
    }

    /**
     * Indicate that the action is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_at' => null,
            'output_data' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the action created a Trip entity.
     */
    public function withTrip(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'create_trip',
            'entity_type' => Trip::class,
            'entity_id' => Trip::factory(),
            'input_data' => $this->generateInputData('create_trip'),
        ]);
    }

    /**
     * Indicate that the action involves a Place entity.
     */
    public function withPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'search_places',
            'entity_type' => Place::class,
            'entity_id' => Place::factory(),
            'input_data' => $this->generateInputData('search_places'),
        ]);
    }

    /**
     * Create a trip creation action.
     */
    public function createTrip(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'create_trip',
            'input_data' => $this->generateInputData('create_trip'),
        ]);
    }

    /**
     * Create a place search action.
     */
    public function searchPlaces(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'search_places',
            'input_data' => $this->generateInputData('search_places'),
        ]);
    }
}
