<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MapCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'place_id',
        'user_id',
        'title',
        'lat',
        'lng',
        'checked_in_at',
        'note',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'checked_in_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CheckpointImage::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'entity');
    }

    /**
     * Get all tags for this checkpoint (polymorphic many-to-many).
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Scopes
     */
    
    public function scopeForTrip($query, int $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCheckedIn($query)
    {
        return $query->whereNotNull('checked_in_at');
    }

    /**
     * Find nearby checkpoints using Haversine formula
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param float $radiusKm Radius in kilometers
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        $earthRadiusKm = 6371;

        return $query->selectRaw("
            *,
            (
                {$earthRadiusKm} * acos(
                    cos(radians(?)) * cos(radians(lat)) *
                    cos(radians(lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(lat))
                )
            ) AS distance
        ", [$lat, $lng, $lat])
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }
}