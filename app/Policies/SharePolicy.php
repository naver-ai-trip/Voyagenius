<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\Trip;
use App\Models\User;

class SharePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Trip $trip): bool
    {
        // Only trip owner can view shares for the trip
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Trip $trip): bool
    {
        // Only trip owner can create share links
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Share $share): bool
    {
        // Only the user who created the share can delete it
        return $share->user_id === $user->id;
    }
}
