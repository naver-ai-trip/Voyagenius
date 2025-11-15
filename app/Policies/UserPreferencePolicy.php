<?php

namespace App\Policies;

use App\Models\UserPreference;
use App\Models\User;

class UserPreferencePolicy
{
    /**
     * Determine whether the user can view any user preferences.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own preferences
    }

    /**
     * Determine whether the user can view the user preference.
     */
    public function view(User $user, UserPreference $userPreference): bool
    {
        // User can view their own preferences
        return $user->id === $userPreference->user_id;
    }

    /**
     * Determine whether the user can create user preferences.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create their preferences
    }

    /**
     * Determine whether the user can update the user preference.
     */
    public function update(User $user, UserPreference $userPreference): bool
    {
        // Only preference owner can update
        return $user->id === $userPreference->user_id;
    }

    /**
     * Determine whether the user can delete the user preference.
     */
    public function delete(User $user, UserPreference $userPreference): bool
    {
        // Only preference owner can delete
        return $user->id === $userPreference->user_id;
    }
}
