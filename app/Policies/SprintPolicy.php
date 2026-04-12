<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermission('sprints.view', $project);
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $user->hasPermission('sprints.view', $sprint->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermission('sprints.create', $project);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $user->hasPermission('sprints.edit', $sprint->project);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return $user->hasPermission('sprints.delete', $sprint->project);
    }
}
