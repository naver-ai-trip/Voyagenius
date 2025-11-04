<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['trip_invite', 'comment_added', 'check_in', 'trip_shared', 'participant_added', 'review_added'];
        
        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement($types),
            'content' => $this->faker->sentence(),
            'data' => null,
            'read_at' => null,
        ];
    }
}
