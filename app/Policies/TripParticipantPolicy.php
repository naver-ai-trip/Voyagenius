<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;

class TripParticipantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Trip $trip): bool
    {
        // Trip owner OR participant can view participants
        return $trip->user_id === $user->id ||
               $trip->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TripParticipant $tripParticipant): bool
    {
        // Trip owner OR participant can view participant details
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id ||
               $trip->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Trip $trip): bool
    {
        // Only trip owner can add participants
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TripParticipant $tripParticipant): bool
    {
        // Cannot change owner role
        if ($tripParticipant->role === 'owner') {
            return false;
        }

        // Only trip owner can update participant roles
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TripParticipant $tripParticipant): bool
    {
        // Cannot remove owner
        if ($tripParticipant->role === 'owner') {
            return false;
        }

        $trip = $tripParticipant->trip;

        // Trip owner can remove any participant OR participant can leave themselves
        return $trip->user_id === $user->id || $tripParticipant->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TripParticipant $tripParticipant): bool
    {
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TripParticipant $tripParticipant): bool
    {
        $trip = $tripParticipant->trip;
        return $trip->user_id === $user->id;
    }
}
