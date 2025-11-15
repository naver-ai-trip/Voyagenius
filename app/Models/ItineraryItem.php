<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ItineraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'title',
        'day_number',
        'start_time',
        'end_time',
        'place_id',
        'note',
    ];

    protected $casts = [
        'day_number' => 'integer',
    ];

    /**
     * Relationships
     */
    
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(TripRecommendation::class, 'recommendation_id');
    }

    /**
     * Scopes
     */
    
    public function scopeForDay($query, int $dayNumber)
    {
        return $query->where('day_number', $dayNumber);
    }

    public function scopeForTrip($query, int $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('day_number')->orderBy('start_time');
    }

    /**
     * Accessors
     */
    
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }
}