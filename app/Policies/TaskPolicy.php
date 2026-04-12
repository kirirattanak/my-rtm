<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermission('tasks.view', $project);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.view', $task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermission('tasks.create', $project);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit', $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.delete', $task->project);
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.change_status', $task->project);
    }

    public function logHours(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit', $task->project);
    }
}
