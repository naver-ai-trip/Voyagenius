<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Determine whether the user can view any trips.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own trips
    }

    /**
     * Determine whether the user can view the trip.
     */
    public function view(User $user, Trip $trip): bool
    {
        // User can view their own trips
        return $user->id === $trip->user_id;
    }

    /**
     * Determine whether the user can create trips.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create trips
    }

    /**
     * Determine whether the user can update the trip.
     */
    public function update(User $user, Trip $trip): bool
    {
        // Only trip owner can update
        return $user->id === $trip->user_id;
    }

    /**
     * Determine whether the user can delete the trip.
     */
    public function delete(User $user, Trip $trip): bool
    {
        // Only trip owner can delete
        return $user->id === $trip->user_id;
    }
}
