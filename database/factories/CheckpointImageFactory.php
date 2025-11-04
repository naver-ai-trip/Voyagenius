<?php

namespace Database\Factories;

use App\Models\MapCheckpoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckpointImage>
 */
class CheckpointImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = Str::uuid();
        $checkpointId = $this->faker->numberBetween(1, 999);
        $tripId = $this->faker->numberBetween(1, 999);

        return [
            'map_checkpoint_id' => MapCheckpoint::factory(),
            'user_id' => User::factory(),
            'file_path' => "checkpoints/{$tripId}/{$checkpointId}/{$uuid}.jpg",
            'caption' => $this->faker->optional()->sentence(),
            'uploaded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }
}
