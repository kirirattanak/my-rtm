<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        // Admins can update any user; users can update themselves
        return $user->isAdmin() || $user->id === $target->id;
    }

    public function updateRole(User $user): bool
    {
        return $user->isAdmin();
    }

    public function deactivate(User $user, User $target): bool
    {
        // Admins can deactivate any user except themselves
        return $user->isAdmin() && $user->id !== $target->id;
    }

    public function invite(User $user): bool
    {
        return $user->isAdmin() || $user->isOrgOwner();
    }
}
