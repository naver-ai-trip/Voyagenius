<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Tag Model
 * 
 * Polymorphic many-to-many tagging system for organizing trips, places, checkpoints.
 * Tags enable discovery, categorization, and search functionality.
 * 
 * Relationships:
 * - morphedByMany: trips, places, checkpoints (via taggables pivot)
 * 
 * Scopes:
 * - popular($minUsageCount): Get tags with minimum usage count, ordered by popularity
 * 
 * Methods:
 * - incrementUsage(): Increment usage_count (called when tag is attached)
 * - decrementUsage(): Decrement usage_count (called when tag is detached, min 0)
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name before creating
        static::creating(function ($tag) {
            if (empty($tag->slug) && !empty($tag->name)) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });
    }

    /**
     * Generate a URL-friendly slug from a name.
     * 
     * @param string $name
     * @return string
     */
    protected static function generateSlug(string $name): string
    {
        // Convert to lowercase, replace spaces with hyphens, remove special chars
        $slug = Str::lower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Get all trips that have this tag.
     */
    public function trips()
    {
        return $this->morphedByMany(Trip::class, 'taggable');
    }

    /**
     * Get all places that have this tag.
     */
    public function places()
    {
        return $this->morphedByMany(Place::class, 'taggable');
    }

    /**
     * Get all checkpoints that have this tag.
     */
    public function checkpoints()
    {
        return $this->morphedByMany(MapCheckpoint::class, 'taggable');
    }

    /**
     * Scope: Get popular tags (usage_count >= threshold).
     * Ordered by usage_count descending.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minUsageCount Minimum usage count threshold (default: 10)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $minUsageCount = 10)
    {
        return $query->where('usage_count', '>=', $minUsageCount)
            ->orderBy('usage_count', 'desc');
    }

    /**
     * Increment the usage count when a tag is attached to an entity.
     * 
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement the usage count when a tag is detached from an entity.
     * Will not go below zero.
     * 
     * @return void
     */
    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }
}
