<?php

namespace App\Policies;

use App\Models\AgentWebhook;
use App\Models\User;

class AgentWebhookPolicy
{
    /**
     * Determine whether the user can view any webhooks.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the webhook.
     */
    public function view(User $user, AgentWebhook $webhook): bool
    {
        return $user->id === $webhook->user_id;
    }

    /**
     * Determine whether the user can create webhooks.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the webhook.
     */
    public function update(User $user, AgentWebhook $webhook): bool
    {
        return $user->id === $webhook->user_id;
    }

    /**
     * Determine whether the user can delete the webhook.
     */
    public function delete(User $user, AgentWebhook $webhook): bool
    {
        return $user->id === $webhook->user_id;
    }
}
