<?php

namespace App\Events;

use App\Models\TripRecommendation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast when a new recommendation is created
 * 
 * Enables real-time notification to users when AI generates
 * new trip recommendations.
 */
class RecommendationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TripRecommendation $recommendation;

    /**
     * Create a new event instance.
     */
    public function __construct(TripRecommendation $recommendation)
    {
        $this->recommendation = $recommendation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trip.' . $this->recommendation->trip_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'recommendation.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->recommendation->id,
            'trip_id' => $this->recommendation->trip_id,
            'recommendation_type' => $this->recommendation->recommendation_type,
            'confidence_score' => $this->recommendation->confidence_score,
            'status' => $this->recommendation->status,
            'created_at' => $this->recommendation->created_at->toIso8601String(),
        ];
    }
}
