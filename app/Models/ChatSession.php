<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ChatSession Model
 *
 * Represents a conversation session between user and AI agent.
 * Stores context, tracks activity, and links to trips.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $trip_id
 * @property string $session_type
 * @property array|null $context
 * @property bool $is_active
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $ended_at
 */
class ChatSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'trip_id',
        'session_type',
        'context',
        'is_active',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'is_active' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the chat session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trip associated with this session.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get all messages in this session.
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    /**
     * Get all agent actions triggered in this session.
     */
    public function agentActions()
    {
        return $this->hasMany(AgentAction::class);
    }

    /**
     * Get all translations performed in this session.
     */
    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * Get all recommendations generated in this session.
     */
    public function recommendations()
    {
        return $this->hasMany(TripRecommendation::class);
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include sessions of a specific type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('session_type', $type);
    }

    /**
     * End the chat session.
     */
    public function end(): void
    {
        $this->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);
    }

    /**
     * Add context to the session.
     */
    public function addContext(string $key, mixed $value): void
    {
        $context = $this->context ?? [];
        $context[$key] = $value;
        $this->update(['context' => $context]);
    }

    /**
     * Get a specific context value.
     */
    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Get the session duration in minutes.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }
}
