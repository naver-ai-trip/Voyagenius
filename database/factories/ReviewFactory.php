<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 *
 * Factory for creating Review test data with polymorphic relationships
 *
 * NAVER API Integration Notes:
 * - Generates random ratings (1-5) and comments
 * - Comments can be translated via PapagoService in tests/production
 * - Default reviewable is Place; use forReviewable() for other types
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reviewable_type' => Place::class,
            'reviewable_id' => Place::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Set specific reviewable entity
     */
    public function forReviewable(string $reviewableType, int $reviewableId): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableId,
        ]);
    }
}
