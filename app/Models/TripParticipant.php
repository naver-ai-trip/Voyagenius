<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TripParticipant Model
 *
 * Manages role-based collaboration for trips.
 * Roles: owner (full control), editor (can modify), viewer (read-only).
 */
class TripParticipant extends Model
{
    /** @use HasFactory<\Database\Factories\TripParticipantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trip_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    /**
     * Get the trip this participant belongs to.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user for this participant.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by role.
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Check if participant is the owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if participant can edit (owner or editor).
     */
    public function canEdit(): bool
    {
        return in_array($this->role, ['owner', 'editor']);
    }

    /**
     * Check if participant can only view.
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
}
