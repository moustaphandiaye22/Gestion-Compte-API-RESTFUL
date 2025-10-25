<?php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComptePolicy
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
    public function view(User $user, Compte $compte): bool
    {
        // Admin can view any account
        if ($user->userable_type === 'App\\Models\\Admin') {
            return true;
        }

        // Client can only view their own accounts
        if ($user->userable_type === 'App\\Models\\Client') {
            return $user->userable_id === $compte->client_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->userable_type === 'App\\Models\\Admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Compte $compte): bool
    {
        return $user->userable_type === 'App\\Models\\Admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Compte $compte): bool
    {
        return $user->userable_type === 'App\\Models\\Admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Compte $compte): bool
    {
        return $user->userable_type === 'App\\Models\\Admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Compte $compte): bool
    {
        return $user->userable_type === 'App\\Models\\Admin';
    }

    /**
     * Determine whether the user can view archives.
     */
    public function viewArchives(User $user): bool
    {
        return $user->userable_type === 'App\\Models\\Admin';
    }
}
