<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'trip_id' => $this->trip_id,
            'session_type' => $this->session_type,
            'context' => $this->context,
            'is_active' => $this->is_active,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships (loaded when requested)
            'messages' => ChatMessageResource::collection($this->whenLoaded('messages')),
            'actions' => AgentActionResource::collection($this->whenLoaded('actions')),
            'trip' => new TripResource($this->whenLoaded('trip')),
        ];
    }
}
