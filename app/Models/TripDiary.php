<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TripDiary extends Model
{
    /** @use HasFactory<\Database\Factories\TripDiaryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trip_id',
        'user_id',
        'entry_date',
        'text',
        'mood',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    /**
     * Get the trip that owns the diary entry.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user that owns the diary entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the diary's comments.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Scope a query to filter by trip.
     */
    public function scopeForTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    /**
     * Scope a query to filter by entry date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('entry_date', $date);
    }

    /**
     * Scope a query to filter by mood.
     */
    public function scopeForMood($query, $mood)
    {
        return $query->where('mood', $mood);
    }
}
