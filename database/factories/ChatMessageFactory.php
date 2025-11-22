<?php

namespace Database\Factories;

use App\Models\ChatSession;
use App\Models\Place;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_session_id' => ChatSession::factory(),
            'from_role' => fake()->randomElement(['user', 'ai']),
            'message' => fake()->paragraph(3),
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'confidence' => fake()->randomFloat(2, 0.5, 1.0),
            ],
            'entity_type' => null,
            'entity_id' => null,
        ];
    }

    /**
     * Indicate that the message is from the user.
     */
    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_role' => 'user',
            'message' => fake()->randomElement([
                'I want to plan a trip to Seoul for 5 days',
                'Can you recommend some restaurants in Gangnam?',
                'What are the best places to visit in Busan?',
                'I need help creating an itinerary for my trip',
                'Show me popular attractions in Jeju Island',
            ]),
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'device' => fake()->randomElement(['web', 'mobile', 'tablet']),
            ],
        ]);
    }

    /**
     * Indicate that the message is from the AI.
     */
    public function fromAI(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_role' => 'ai',
            'message' => fake()->randomElement([
                'I can help you plan a wonderful trip to Seoul! Let me suggest some popular attractions and create a day-by-day itinerary.',
                'Based on your preferences, here are some highly-rated restaurants in Gangnam that match your style.',
                'Busan has many amazing places to visit. Would you like me to organize them into a multi-day itinerary?',
                'Let me create a personalized itinerary based on your travel dates and preferences.',
                'Jeju Island is beautiful! Here are some must-visit attractions with estimated visit times.',
            ]),
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'confidence' => fake()->randomFloat(2, 0.7, 1.0),
                'model' => 'agent-v1',
                'processing_time_ms' => fake()->numberBetween(200, 1500),
            ],
        ]);
    }

    /**
     * Indicate that the message references a Place entity.
     */
    public function withPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => Place::class,
            'entity_id' => Place::factory(),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'entity_name' => fake()->company(),
            ]),
        ]);
    }

    /**
     * Indicate that the message references a Trip entity.
     */
    public function withTrip(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => Trip::class,
            'entity_id' => Trip::factory(),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'entity_name' => fake()->sentence(3),
            ]),
        ]);
    }

    /**
     * Create a conversation sequence (user question + AI response).
     */
    public function conversation(): static
    {
        $session = ChatSession::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'chat_session_id' => $session->id,
        ]);
    }
}
