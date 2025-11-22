<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TripRecommendation>
 */
class TripRecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['place', 'itinerary', 'activity', 'dining', 'accommodation']);
        
        return [
            'trip_id' => Trip::factory(),
            'recommendation_type' => $type,
            'data' => $this->generateDataByType($type),
            'confidence_score' => fake()->randomFloat(2, 0.6, 1.0),
            'status' => fake()->randomElement(['pending', 'accepted', 'rejected']),
            'applied_by' => null,
            'applied_at' => null,
        ];
    }

    /**
     * Generate recommendation data based on type.
     */
    private function generateDataByType(string $type): array
    {
        return match($type) {
            'place' => [
                'name' => fake()->company(),
                'category' => fake()->randomElement(['restaurant', 'attraction', 'hotel', 'cafe']),
                'address' => fake()->address(),
                'reason' => 'Highly rated and matches your preferences',
                'estimated_cost' => fake()->numberBetween(10000, 100000),
                'estimated_duration' => fake()->numberBetween(30, 240),
            ],
            'itinerary' => [
                'day' => fake()->numberBetween(1, 7),
                'time_slot' => fake()->randomElement(['morning', 'afternoon', 'evening']),
                'activity' => fake()->sentence(4),
                'location' => fake()->city(),
                'reason' => 'Optimal timing based on your schedule',
            ],
            'activity' => [
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'category' => fake()->randomElement(['outdoor', 'cultural', 'entertainment', 'shopping']),
                'best_time' => fake()->randomElement(['morning', 'afternoon', 'evening', 'night']),
                'reason' => 'Popular activity in the area',
            ],
            'dining' => [
                'restaurant_name' => fake()->company(),
                'cuisine_type' => fake()->randomElement(['Korean', 'Japanese', 'Western', 'Chinese', 'Italian']),
                'price_range' => fake()->randomElement(['budget', 'moderate', 'upscale']),
                'specialties' => fake()->words(3),
                'reason' => 'Matches your dining preferences',
            ],
            'accommodation' => [
                'hotel_name' => fake()->company() . ' Hotel',
                'type' => fake()->randomElement(['hotel', 'hostel', 'guesthouse', 'resort']),
                'area' => fake()->city(),
                'price_per_night' => fake()->numberBetween(50000, 300000),
                'reason' => 'Well-located and within your budget',
            ],
            default => [
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
            ],
        };
    }

    /**
     * Indicate that the recommendation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'applied_by' => null,
            'applied_at' => null,
        ]);
    }

    /**
     * Indicate that the recommendation has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'applied_by' => User::factory(),
            'applied_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the recommendation has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'applied_by' => User::factory(),
            'applied_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a place recommendation.
     */
    public function place(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'place',
            'data' => $this->generateDataByType('place'),
        ]);
    }

    /**
     * Create an itinerary recommendation.
     */
    public function itinerary(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'itinerary',
            'data' => $this->generateDataByType('itinerary'),
        ]);
    }

    /**
     * Create a high-confidence recommendation.
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => fake()->randomFloat(2, 0.85, 1.0),
        ]);
    }
}
