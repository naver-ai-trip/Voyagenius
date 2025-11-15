<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * AgentWebhook Model
 * 
 * Represents a webhook endpoint registered by an AI agent
 * to receive real-time notifications about events.
 * 
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property array $events
 * @property string $secret
 * @property bool $is_active
 * @property int $retry_count
 * @property int $timeout_seconds
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property \Carbon\Carbon|null $last_success_at
 * @property \Carbon\Carbon|null $last_failure_at
 * @property string|null $last_error
 */
class AgentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'events',
        'secret',
        'is_active',
        'retry_count',
        'timeout_seconds',
        'last_triggered_at',
        'last_success_at',
        'last_failure_at',
        'last_error',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = Str::random(64);
            }
        });
    }

    /**
     * Get the user that owns the webhook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate signature for webhook payload
     */
    public function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->secret);
    }

    /**
     * Verify incoming webhook signature
     */
    public function verifySignature(string $signature, array $payload): bool
    {
        return hash_equals($signature, $this->generateSignature($payload));
    }

    /**
     * Check if webhook should receive this event
     */
    public function shouldReceiveEvent(string $eventName): bool
    {
        return $this->is_active && in_array($eventName, $this->events);
    }

    /**
     * Mark webhook as triggered
     */
    public function markTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Mark webhook delivery as successful
     */
    public function markSuccess(): void
    {
        $this->update([
            'last_success_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark webhook delivery as failed
     */
    public function markFailure(string $error): void
    {
        $this->update([
            'last_failure_at' => now(),
            'last_error' => $error,
        ]);
    }

    /**
     * Scope: Get active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get webhooks for specific event
     */
    public function scopeForEvent($query, string $eventName)
    {
        return $query->whereJsonContains('events', $eventName);
    }
}
