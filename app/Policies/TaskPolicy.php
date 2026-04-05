<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin || $project->hasMember($user);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->role === UserRole::Admin || $task->project->hasMember($user);
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->role === UserRole::Admin) return true;
        $role = $project->memberRole($user);
        return in_array($role, [
            UserRole::ProjectManager,
            UserRole::BusinessAnalyst,
            UserRole::Developer,
            UserRole::Tester,
        ]);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->create($user, $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->role === UserRole::Admin) return true;
        $role = $task->project->memberRole($user);
        return $role === UserRole::ProjectManager;
    }

    public function logHours(User $user, Task $task): bool
    {
        return $this->create($user, $task->project);
    }
}
