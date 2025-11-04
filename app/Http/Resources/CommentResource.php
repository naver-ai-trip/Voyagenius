<?php

namespace App\Http\Resources;

use App\Models\MapCheckpoint;
use App\Models\Trip;
use App\Models\TripDiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'entity_type' => match ($this->entity_type) {
                Trip::class => 'trip',
                MapCheckpoint::class => 'map_checkpoint',
                TripDiary::class => 'trip_diary',
                default => $this->entity_type,
            },
            'entity_id' => $this->entity_id,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user'),
            'entity' => $this->whenLoaded('entity'),
        ];
    }
}
