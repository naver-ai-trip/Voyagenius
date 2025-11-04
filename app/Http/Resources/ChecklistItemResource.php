<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemResource extends JsonResource
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
            'trip_id' => $this->trip_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'is_checked' => $this->is_checked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'trip' => new TripResource($this->whenLoaded('trip')),
        ];
    }
}
