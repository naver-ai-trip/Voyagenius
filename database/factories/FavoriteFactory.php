<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Favorite>
 */
class FavoriteFactory extends Factory
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
            'favoritable_type' => Place::class,
            'favoritable_id' => Place::factory(),
        ];
    }

    /**
     * Set the favoritable entity for polymorphic relationship
     */
    public function forFavoritable(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'favoritable_type' => $type,
            'favoritable_id' => $id,
        ]);
    }
}
