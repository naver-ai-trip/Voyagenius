<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentActionResource extends JsonResource
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
            'chat_session_id' => $this->chat_session_id,
            'action_type' => $this->action_type,
            'status' => $this->status,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'input_data' => $this->input_data,
            'output_data' => $this->output_data,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed fields
            'execution_time_seconds' => $this->when($this->completed_at, function () {
                return $this->completed_at->diffInSeconds($this->started_at);
            }),
            
            // Polymorphic relationship
            'entity' => $this->when($this->entity, function () {
                return $this->entity;
            }),
        ];
    }
}
