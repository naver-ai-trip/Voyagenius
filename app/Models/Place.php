<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Place Model
 *
 * Represents a place/POI from NAVER Maps.
 * Stores location data, reviews, and relationships to trips.
 */
class Place extends Model
{
    /** @use HasFactory<\Database\Factories\PlaceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'lat',
        'lng',
        'category',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    /**
     * Boot the model and register event listeners
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Place $place) {
            // Delete all polymorphic reviews when place is deleted
            $place->reviews()->delete();
            $place->favorites()->delete();
        });
    }

    /**
     * Get the reviews for this place (polymorphic).
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the favorites for this place (polymorphic).
     */
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Get the itinerary items that include this place.
     */
    public function itineraryItems()
    {
        return $this->hasMany(ItineraryItem::class);
    }

    /**
     * Get the checkpoints at this place.
     */
    public function checkpoints()
    {
        return $this->hasMany(MapCheckpoint::class);
    }

    /**
     * Get all tags for this place (polymorphic many-to-many).
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to search by name.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope a query to find nearby places within radius (km).
     * Uses Haversine formula for distance calculation.
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 5)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw(
            "*, (
                {$earthRadius} * acos(
                    cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(lat))
                )
            ) AS distance",
            [$lat, $lng, $lat]
        )
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }

    /**
     * Get average rating for this place.
     */
    public function getAverageRatingAttribute(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    /**
     * Get total review count for this place.
     */
    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->count();
    }
}
