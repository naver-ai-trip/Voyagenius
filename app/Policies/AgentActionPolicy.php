<?php

namespace App\Policies;

use App\Models\AgentAction;
use App\Models\User;

class AgentActionPolicy
{
    /**
     * Determine whether the user can view any agent actions.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view actions in their chat sessions
    }

    /**
     * Determine whether the user can view the agent action.
     */
    public function view(User $user, AgentAction $agentAction): bool
    {
        // User can view actions in their own chat sessions
        return $user->id === $agentAction->chatSession->user_id;
    }

    /**
     * Determine whether the user can create agent actions.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users (and AI agents) can create actions
    }

    /**
     * Determine whether the user can update the agent action.
     */
    public function update(User $user, AgentAction $agentAction): bool
    {
        // Only session owner can update actions (mark completed/failed)
        return $user->id === $agentAction->chatSession->user_id;
    }

    /**
     * Determine whether the user can delete the agent action.
     */
    public function delete(User $user, AgentAction $agentAction): bool
    {
        // Only session owner can delete actions
        return $user->id === $agentAction->chatSession->user_id;
    }
}
