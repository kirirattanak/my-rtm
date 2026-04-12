<?php

namespace App\Policies;

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
        // Org owners can view any project within their organisation
        if ($user->isOrgOwner() && $user->organization_id === $project->organization_id) {
            return true;
        }
        return $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        // Admin has no organisation and cannot own projects
        if ($user->isAdmin()) {
            return false;
        }
        return $user->hasPermission('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.edit', $project);
    }

    public function delete(User $user, Project $project): bool
    {
        // Only org owners (of the project's org) can delete projects; admin cannot
        return $user->isOrgOwner() && $user->organization_id === $project->organization_id;
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->hasPermission('members.manage', $project);
    }
}
