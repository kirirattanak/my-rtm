<?php

namespace App\Policies;

use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\User;

class BusinessRequirementPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermission('br.view', $project);
    }

    public function view(User $user, BusinessRequirement $br): bool
    {
        return $user->hasPermission('br.view', $br->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermission('br.create', $project);
    }

    public function update(User $user, BusinessRequirement $br): bool
    {
        return $user->hasPermission('br.edit', $br->project);
    }

    public function delete(User $user, BusinessRequirement $br): bool
    {
        return $user->hasPermission('br.delete', $br->project);
    }

    public function changeStatus(User $user, BusinessRequirement $br): bool
    {
        return $user->hasPermission('br.change_status', $br->project);
    }

    public function import(User $user, Project $project): bool
    {
        return $user->hasPermission('br.import', $project);
    }

    public function export(User $user, Project $project): bool
    {
        return $user->hasPermission('br.export', $project);
    }
}
