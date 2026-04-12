<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\User;

class TechnicalRequirementPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermission('tr.view', $project);
    }

    public function view(User $user, TechnicalRequirement $tr): bool
    {
        return $user->hasPermission('tr.view', $tr->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermission('tr.create', $project);
    }

    public function update(User $user, TechnicalRequirement $tr): bool
    {
        return $user->hasPermission('tr.edit', $tr->project);
    }

    public function delete(User $user, TechnicalRequirement $tr): bool
    {
        return $user->hasPermission('tr.delete', $tr->project);
    }

    public function changeStatus(User $user, TechnicalRequirement $tr): bool
    {
        return $user->hasPermission('tr.change_status', $tr->project);
    }

    public function import(User $user, Project $project): bool
    {
        return $user->hasPermission('tr.import', $project);
    }

    public function export(User $user, Project $project): bool
    {
        return $user->hasPermission('tr.export', $project);
    }
}
