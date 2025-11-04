<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CheckpointImage;
use App\Models\MapCheckpoint;

class CheckpointImagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CheckpointImage $checkpointImage): bool
    {
        // Load checkpoint if not already loaded
        $checkpointImage->loadMissing('checkpoint.trip');
        
        // User can view if they own the trip
        return $checkpointImage->checkpoint->trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     * Check is done in controller against checkpoint's trip
     */
    public function create(User $user, MapCheckpoint $checkpoint): bool
    {
        // Load trip if not already loaded
        $checkpoint->loadMissing('trip');
        
        // Only trip owner can upload images to checkpoints
        return $checkpoint->trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     * Only the image uploader can update caption
     */
    public function update(User $user, CheckpointImage $checkpointImage): bool
    {
        return $checkpointImage->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     * Image uploader OR trip owner can delete
     */
    public function delete(User $user, CheckpointImage $checkpointImage): bool
    {
        // Load checkpoint if not already loaded
        $checkpointImage->loadMissing('checkpoint.trip');
        
        // Image uploader or trip owner can delete
        return $checkpointImage->user_id === $user->id ||
               $checkpointImage->checkpoint->trip->user_id === $user->id;
    }
}
