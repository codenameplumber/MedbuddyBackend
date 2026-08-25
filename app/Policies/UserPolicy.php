<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->organisation_id === $target->organisation_id;
    }

    public function delete(User $user, User $target): bool
    {
        // Nobody deletes themselves out of the last admin seat.
        return $user->isAdmin() && $user->id !== $target->id;
    }
}
