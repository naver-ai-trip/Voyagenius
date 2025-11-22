<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
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
            'name' => $this->name,
            'category' => $this->category,
            'address' => $this->address,
            'road_address' => $this->road_address ?? $this->address,
            'latitude' => $this->lat,
            'longitude' => $this->lng,
            'phone' => $this->when(isset($this->phone), $this->phone),
            'description' => $this->when(isset($this->description), $this->description),
            'business_hours' => $this->when(isset($this->business_hours), $this->business_hours),
            'naver_link' => $this->naver_link ?? "https://place.naver.com/place/{$this->naver_place_id}",
            'average_rating' => $this->when($this->relationLoaded('reviews'), $this->average_rating),
            'review_count' => $this->when($this->relationLoaded('reviews'), $this->review_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
