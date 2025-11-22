<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    /**
     * Determine whether the user can view any chat sessions.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own chat sessions
    }

    /**
     * Determine whether the user can view the chat session.
     */
    public function view(User $user, ChatSession $chatSession): bool
    {
        // User can view their own chat sessions
        return $user->id === $chatSession->user_id;
    }

    /**
     * Determine whether the user can create chat sessions.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create chat sessions
    }

    /**
     * Determine whether the user can update the chat session.
     */
    public function update(User $user, ChatSession $chatSession): bool
    {
        // Only session owner can update
        return $user->id === $chatSession->user_id;
    }

    /**
     * Determine whether the user can delete the chat session.
     */
    public function delete(User $user, ChatSession $chatSession): bool
    {
        // Only session owner can delete
        return $user->id === $chatSession->user_id;
    }
}
