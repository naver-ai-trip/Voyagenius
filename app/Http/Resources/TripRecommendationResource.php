<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripRecommendationResource extends JsonResource
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
            'recommendation_type' => $this->recommendation_type,
            'data' => $this->data,
            'confidence_score' => $this->confidence_score,
            'status' => $this->status,
            'applied_by' => $this->applied_by,
            'applied_at' => $this->applied_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships
            'trip' => new TripResource($this->whenLoaded('trip')),
            'appliedByUser' => new UserResource($this->whenLoaded('appliedBy')),
        ];
    }
}
