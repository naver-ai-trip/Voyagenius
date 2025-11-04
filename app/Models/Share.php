<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Share extends Model
{
    /** @use HasFactory<\Database\Factories\ShareFactory> */
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'permission',
        'token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Share $share) {
            if (empty($share->token)) {
                $share->token = Str::random(32);
            }
        });
    }

    /**
     * Get the trip that this share belongs to.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who created the share.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by permission level.
     */
    public function scopeForPermission($query, string $permission)
    {
        return $query->where('permission', $permission);
    }

    /**
     * Scope a query to find by token.
     */
    public function scopeForToken($query, string $token)
    {
        return $query->where('token', $token);
    }
}
