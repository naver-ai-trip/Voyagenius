<?php

namespace App\Events;

use App\Models\AgentAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast when an agent action is completed
 * 
 * Enables real-time updates on long-running AI operations.
 */
class ActionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AgentAction $action;

    /**
     * Create a new event instance.
     */
    public function __construct(AgentAction $action)
    {
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-session.' . $this->action->chat_session_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'action.completed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->action->id,
            'chat_session_id' => $this->action->chat_session_id,
            'action_type' => $this->action->action_type,
            'status' => $this->action->status,
            'output_data' => $this->action->output_data,
            'completed_at' => $this->action->completed_at?->toIso8601String(),
        ];
    }
}
