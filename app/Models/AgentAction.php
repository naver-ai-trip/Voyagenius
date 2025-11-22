<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AgentAction Model
 *
 * Tracks actions performed by the AI agent.
 * Provides audit trail and error tracking.
 *
 * @property int $id
 * @property int $chat_session_id
 * @property int|null $chat_message_id
 * @property string $action_type
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property array $action_data
 * @property string $status
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $executed_at
 */
class AgentAction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_session_id',
        'chat_message_id',
        'action_type',
        'entity_type',
        'entity_id',
        'action_data',
        'status',
        'error_message',
        'executed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_data' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    /**
     * Get the chat session this action belongs to.
     */
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Get the chat message that initiated this action.
     */
    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    /**
     * Get the related entity (polymorphic).
     */
    public function entity()
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Scope a query to only include actions of a specific type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope a query to only include actions with a specific status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending actions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed actions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed actions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark action as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark action as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'executed_at' => now(),
        ]);

        // Dispatch event for real-time notification
        event(new \App\Events\ActionCompleted($this));
    }

    /**
     * Mark action as failed with error message.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'executed_at' => now(),
        ]);
    }

    /**
     * Check if action is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if action is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if action failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get execution duration in milliseconds.
     */
    public function getExecutionDurationAttribute(): ?int
    {
        if (!$this->executed_at) {
            return null;
        }

        return $this->created_at->diffInMilliseconds($this->executed_at);
    }
}
