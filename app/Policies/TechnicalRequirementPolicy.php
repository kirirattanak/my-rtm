<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\User;

class TechnicalRequirementPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin || $project->hasMember($user);
    }

    public function view(User $user, TechnicalRequirement $tr): bool
    {
        return $user->role === UserRole::Admin || $tr->project->hasMember($user);
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $memberRole = $project->memberRole($user);

        return in_array($memberRole, [
            UserRole::ProjectManager,
            UserRole::BusinessAnalyst,
            UserRole::Developer,
        ]);
    }

    public function update(User $user, TechnicalRequirement $tr): bool
    {
        return $this->create($user, $tr->project);
    }

    public function delete(User $user, TechnicalRequirement $tr): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $memberRole = $tr->project->memberRole($user);

        return $memberRole === UserRole::ProjectManager;
    }
}
