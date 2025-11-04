<?php

namespace App\Policies;

use App\Models\MapCheckpoint;
use App\Models\User;

class MapCheckpointPolicy
{
    /**
     * Determine whether the user can view any checkpoints.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the checkpoint.
     * User can view if they own the trip that the checkpoint belongs to.
     */
    public function view(User $user, MapCheckpoint $checkpoint): bool
    {
        // Load trip relationship if not already loaded
        if (!$checkpoint->relationLoaded('trip')) {
            $checkpoint->load('trip');
        }
        
        return $user->id === $checkpoint->trip->user_id;
    }

    /**
     * Determine whether the user can create checkpoints.
     * Trip ownership is validated in the StoreMapCheckpointRequest.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the checkpoint.
     * User can update if they own the checkpoint.
     */
    public function update(User $user, MapCheckpoint $checkpoint): bool
    {
        // Load trip relationship if not already loaded
        if (!$checkpoint->relationLoaded('trip')) {
            $checkpoint->load('trip');
        }
        
        return $user->id === $checkpoint->trip->user_id;
    }

    /**
     * Determine whether the user can delete the checkpoint.
     * User can delete if they own the checkpoint.
     */
    public function delete(User $user, MapCheckpoint $checkpoint): bool
    {
        // Load trip relationship if not already loaded
        if (!$checkpoint->relationLoaded('trip')) {
            $checkpoint->load('trip');
        }
        
        return $user->id === $checkpoint->trip->user_id;
    }
}
