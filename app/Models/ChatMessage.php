<?php

namespace App\Models;

use App\Events\MessageSent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ChatMessage Model
 *
 * Represents a single message in a chat conversation.
 * Can be from user, assistant, or system.
 *
 * @property int $id
 * @property int $chat_session_id
 * @property string $from_role
 * @property string $message_type
 * @property string $content
 * @property array|null $metadata
 * @property array|null $references
 */
class ChatMessage extends Model
{
    use HasFactory;

    /**
     * The event map for the model.
     *
     * @var array<string, string>
     */
    protected $dispatchesEvents = [
        'created' => MessageSent::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_session_id',
        'from_role',
        'message_type',
        'content',
        'metadata',
        'references',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'references' => 'array',
        ];
    }

    /**
     * Get the chat session this message belongs to.
     */
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Get all agent actions initiated by this message.
     */
    public function agentActions()
    {
        return $this->hasMany(AgentAction::class);
    }

    /**
     * Scope a query to only include messages from a specific role.
     */
    public function scopeFromRole($query, string $role)
    {
        return $query->where('from_role', $role);
    }

    /**
     * Scope a query to only include messages of a specific type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Check if message is from user.
     */
    public function isFromUser(): bool
    {
        return $this->from_role === 'user';
    }

    /**
     * Check if message is from assistant.
     */
    public function isFromAssistant(): bool
    {
        return $this->from_role === 'assistant';
    }

    /**
     * Check if message is from system.
     */
    public function isFromSystem(): bool
    {
        return $this->from_role === 'system';
    }

    /**
     * Add a reference to an entity.
     */
    public function addReference(string $type, int $id, array $data = []): void
    {
        $references = $this->references ?? [];
        $references[] = [
            'type' => $type,
            'id' => $id,
            'data' => $data,
            'added_at' => now()->toISOString(),
        ];
        $this->update(['references' => $references]);
    }

    /**
     * Get references of a specific type.
     */
    public function getReferencesOfType(string $type): array
    {
        if (!$this->references) {
            return [];
        }

        return array_filter($this->references, fn($ref) => $ref['type'] === $type);
    }
}
