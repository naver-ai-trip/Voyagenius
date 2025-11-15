<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    /**
     * Determine whether the user can view any chat messages.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view messages in their chat sessions
    }

    /**
     * Determine whether the user can view the chat message.
     */
    public function view(User $user, ChatMessage $chatMessage): bool
    {
        // User can view messages in their own chat sessions
        return $user->id === $chatMessage->chatSession->user_id;
    }

    /**
     * Determine whether the user can create chat messages.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create messages in their sessions
    }

    /**
     * Determine whether the user can update the chat message.
     */
    public function update(User $user, ChatMessage $chatMessage): bool
    {
        // Only session owner can update messages
        return $user->id === $chatMessage->chatSession->user_id;
    }

    /**
     * Determine whether the user can delete the chat message.
     */
    public function delete(User $user, ChatMessage $chatMessage): bool
    {
        // Only session owner can delete messages
        return $user->id === $chatMessage->chatSession->user_id;
    }
}
