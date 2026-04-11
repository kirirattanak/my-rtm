<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::ProjectManager]);
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(UserRole::ProjectManager)) {
            return true;
        }

        // Project-level PM can also edit
        return $project->memberRole($user) === UserRole::ProjectManager;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function manageMembers(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(UserRole::ProjectManager)) {
            return true;
        }

        return $project->memberRole($user) === UserRole::ProjectManager;
    }
}
