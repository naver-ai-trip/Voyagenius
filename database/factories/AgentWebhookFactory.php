<?php

namespace Database\Factories;

use App\Models\AgentWebhook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentWebhook>
 */
class AgentWebhookFactory extends Factory
{
    protected $model = AgentWebhook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->url(),
            'events' => fake()->randomElements(
                ['message.sent', 'recommendation.created', 'action.completed', 'session.started', 'session.ended'],
                fake()->numberBetween(1, 3)
            ),
            'secret' => Str::random(64),
            'is_active' => true,
            'retry_count' => 3,
            'timeout_seconds' => 30,
            'last_triggered_at' => null,
            'last_success_at' => null,
            'last_failure_at' => null,
            'last_error' => null,
        ];
    }

    /**
     * Indicate that the webhook is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the webhook has been triggered recently.
     */
    public function recentlyTriggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the webhook has successful deliveries.
     */
    public function withSuccessfulDeliveries(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => now()->subMinutes(5),
            'last_success_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Indicate that the webhook has failed deliveries.
     */
    public function withFailedDeliveries(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => now()->subMinutes(10),
            'last_failure_at' => now()->subMinutes(10),
            'last_error' => 'HTTP 500: Internal Server Error',
        ]);
    }

    /**
     * Webhook that only listens to message events.
     */
    public function messageEvents(): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => ['message.sent'],
        ]);
    }

    /**
     * Webhook that only listens to recommendation events.
     */
    public function recommendationEvents(): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => ['recommendation.created'],
        ]);
    }

    /**
     * Webhook with custom retry settings.
     */
    public function withCustomRetry(int $retries, int $timeout): static
    {
        return $this->state(fn (array $attributes) => [
            'retry_count' => $retries,
            'timeout_seconds' => $timeout,
        ]);
    }
}
