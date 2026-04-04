<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\User;

class BusinessRequirementPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin || $project->hasMember($user);
    }

    public function view(User $user, BusinessRequirement $br): bool
    {
        return $user->role === UserRole::Admin || $br->project->hasMember($user);
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
        ]);
    }

    public function update(User $user, BusinessRequirement $br): bool
    {
        return $this->create($user, $br->project);
    }

    public function delete(User $user, BusinessRequirement $br): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $memberRole = $br->project->memberRole($user);

        return $memberRole === UserRole::ProjectManager;
    }
}
