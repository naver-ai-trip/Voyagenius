<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckpointImageResource extends JsonResource
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
            'map_checkpoint_id' => $this->map_checkpoint_id,
            'user_id' => $this->user_id,
            'file_path' => $this->file_path,
            'url' => $this->url, // Uses model accessor
            'caption' => $this->caption,
            'uploaded_at' => $this->uploaded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'checkpoint' => new MapCheckpointResource($this->whenLoaded('checkpoint')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
