<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Trip Model
 *
 * Represents a user's trip with destination, dates, and collaboration features.
 * Core entity for the trip planning platform.
 */
class Trip extends Model
{
    /** @use HasFactory<\Database\Factories\TripFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'destination_country',
        'destination_city',
        'start_date',
        'end_date',
        'status',
        'is_group',
        'progress',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_group' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the trip.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trip duration in days.
     */
    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get the favorites for this trip (polymorphic).
     */
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Get the participants for this trip.
     */
    public function participants()
    {
        return $this->hasMany(TripParticipant::class);
    }

    /**
     * Get the itinerary items for this trip.
     */
    public function itineraryItems()
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('day_number')->orderBy('start_time');
    }

    /**
     * Get the checkpoints for this trip.
     */
    public function checkpoints()
    {
        return $this->hasMany(MapCheckpoint::class);
    }

    /**
     * Get the checklist items for this trip.
     */
    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /**
     * Get the diary entries for this trip.
     */
    public function diaryEntries()
    {
        return $this->hasMany(TripDiary::class);
    }

    /**
     * Get the shares for this trip.
     */
    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    /**
     * Get all tags for this trip (polymorphic many-to-many).
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Scope a query to only include trips with a specific status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include upcoming trips.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->orderBy('start_date', 'asc');
    }

    /**
     * Scope a query to only include ongoing trips.
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('status', 'ongoing');
    }

    /**
     * Scope a query to only include past trips.
     */
    public function scopePast($query)
    {
        return $query->where('end_date', '<', now())
            ->orderBy('end_date', 'desc');
    }

    /**
     * Get the chat session associated with this trip.
     */
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Get all recommendations for this trip.
     */
    public function recommendations()
    {
        return $this->hasMany(TripRecommendation::class);
    }

    /**
     * Get pending recommendations for this trip.
     */
    public function pendingRecommendations()
    {
        return $this->hasMany(TripRecommendation::class)->where('status', 'pending');
    }

    /**
     * Get accepted recommendations for this trip.
     */
    public function acceptedRecommendations()
    {
        return $this->hasMany(TripRecommendation::class)->where('status', 'accepted');
    }
}

