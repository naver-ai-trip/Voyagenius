<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hotel Model
 *
 * Represents a hotel from Amadeus Hotel API.
 * Stores hotel data, location, ratings, and relationships to trips.
 */
class Hotel extends Model
{
    /** @use HasFactory<\Database\Factories\HotelFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amadeus_hotel_id',
        'name',
        'chain_code',
        'dupe_id',
        'rating',
        'city_code',
        'latitude',
        'longitude',
        'address',
        'contact',
        'description',
        'amenities',
        'media',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'rating' => 'float',
            'address' => 'array',
            'contact' => 'array',
            'description' => 'array',
            'amenities' => 'array',
            'media' => 'array',
        ];
    }

    /**
     * Boot the model and register event listeners
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Hotel $hotel) {
            // Delete all polymorphic reviews when hotel is deleted
            $hotel->reviews()->delete();
            $hotel->favorites()->delete();
        });
    }

    /**
     * Get the reviews for this hotel (polymorphic).
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the favorites for this hotel (polymorphic).
     */
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Get the itinerary items that include this hotel.
     */
    public function itineraryItems()
    {
        return $this->hasMany(ItineraryItem::class);
    }

    /**
     * Get the checkpoints at this hotel.
     */
    public function checkpoints()
    {
        return $this->hasMany(MapCheckpoint::class);
    }

    /**
     * Get all tags for this hotel (polymorphic many-to-many).
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Scope a query to filter by city code.
     */
    public function scopeCityCode($query, string $cityCode)
    {
        return $query->where('city_code', strtoupper($cityCode));
    }

    /**
     * Scope a query to filter by chain code.
     */
    public function scopeChainCode($query, string $chainCode)
    {
        return $query->where('chain_code', $chainCode);
    }

    /**
     * Scope a query to filter by minimum rating.
     */
    public function scopeMinRating($query, float $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /**
     * Scope a query to search by name.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope a query to find nearby hotels within radius (km).
     * Uses Haversine formula for distance calculation.
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 5)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw(
            "*, (
                {$earthRadius} * acos(
                    cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance",
            [$lat, $lng, $lat]
        )
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    /**
     * Get average rating for this hotel from reviews.
     */
    public function getAverageRatingAttribute(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    /**
     * Get total review count for this hotel.
     */
    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): ?string
    {
        if (!$this->address || !is_array($this->address)) {
            return null;
        }

        $parts = [];

        if (isset($this->address['lines']) && is_array($this->address['lines'])) {
            $parts = array_merge($parts, $this->address['lines']);
        }

        if (isset($this->address['cityName'])) {
            $parts[] = $this->address['cityName'];
        }

        if (isset($this->address['postalCode'])) {
            $parts[] = $this->address['postalCode'];
        }

        if (isset($this->address['countryCode'])) {
            $parts[] = $this->address['countryCode'];
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }
}
