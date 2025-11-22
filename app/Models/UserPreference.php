<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * UserPreference Model
 *
 * Stores user preferences for AI-driven personalization.
 * Supports various preference types with priority levels.
 *
 * @property int $id
 * @property int $user_id
 * @property string $preference_type
 * @property string $preference_key
 * @property array $preference_value
 * @property int $priority
 */
class UserPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'preference_type',
        'preference_key',
        'preference_value',
        'priority',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preference_value' => 'array',
            'priority' => 'integer',
        ];
    }

    /**
     * Get the user that owns this preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by preference type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('preference_type', $type);
    }

    /**
     * Scope a query to filter by preference key.
     */
    public function scopeKey($query, string $key)
    {
        return $query->where('preference_key', $key);
    }

    /**
     * Scope a query to order by priority (highest first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope a query to only include high priority preferences (7-10).
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 7);
    }

    /**
     * Get a specific value from the preference_value array.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->preference_value[$key] ?? $default;
    }

    /**
     * Set a specific value in the preference_value array.
     */
    public function setValue(string $key, mixed $value): void
    {
        $preferenceValue = $this->preference_value ?? [];
        $preferenceValue[$key] = $value;
        $this->update(['preference_value' => $preferenceValue]);
    }

    /**
     * Check if this is a high priority preference.
     */
    public function isHighPriority(): bool
    {
        return $this->priority >= 7;
    }

    /**
     * Check if this is a low priority preference.
     */
    public function isLowPriority(): bool
    {
        return $this->priority <= 3;
    }
}
