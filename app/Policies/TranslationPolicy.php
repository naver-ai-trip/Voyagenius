<?php

namespace App\Policies;

use App\Models\Translation;
use App\Models\User;

class TranslationPolicy
{
    /**
     * Determine whether the user can view the translation.
     */
    public function view(User $user, Translation $translation): bool
    {
        return $user->id === $translation->user_id;
    }

    /**
     * Determine whether the user can delete the translation.
     */
    public function delete(User $user, Translation $translation): bool
    {
        return $user->id === $translation->user_id;
    }
}
