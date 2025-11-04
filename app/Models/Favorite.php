<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Favorite Model - Polymorphic favoriting system
 *
 * Users can favorite various entities:
 * - Places (NAVER Maps POI)
 * - Map Checkpoints (check-ins)
 * - Trips (entire trip plans)
 *
 * NAVER Integration:
 * - Can export favorites to NAVER Maps for offline access
 * - Favorites sync with NAVER account (future OAuth integration)
 * - Analytics for popular places via NAVER Maps API
 */
class Favorite extends Model
{
    /** @use HasFactory<\Database\Factories\FavoriteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favoritable_type',
        'favoritable_id',
    ];

    /**
     * Get the user who favorited
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the favoritable entity (polymorphic)
     */
    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope favorites by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope favorites by type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('favoritable_type', $type);
    }

    /**
     * Scope recent favorites
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
