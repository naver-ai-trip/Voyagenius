<?php

namespace App\Policies;

use App\Models\TripRecommendation;
use App\Models\User;

class TripRecommendationPolicy
{
    /**
     * Determine whether the user can view any trip recommendations.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view recommendations for their trips
    }

    /**
     * Determine whether the user can view the trip recommendation.
     */
    public function view(User $user, TripRecommendation $tripRecommendation): bool
    {
        // User can view recommendations for their own trips
        return $user->id === $tripRecommendation->trip->user_id;
    }

    /**
     * Determine whether the user can create trip recommendations.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create recommendations (AI agent can create)
    }

    /**
     * Determine whether the user can update the trip recommendation.
     */
    public function update(User $user, TripRecommendation $tripRecommendation): bool
    {
        // Only trip owner can accept/reject recommendations
        return $user->id === $tripRecommendation->trip->user_id;
    }

    /**
     * Determine whether the user can delete the trip recommendation.
     */
    public function delete(User $user, TripRecommendation $tripRecommendation): bool
    {
        // Only trip owner can delete recommendations
        return $user->id === $tripRecommendation->trip->user_id;
    }
}
