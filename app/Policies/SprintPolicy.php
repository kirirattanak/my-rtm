<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin || $project->hasMember($user);
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $user->role === UserRole::Admin || $sprint->project->hasMember($user);
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->role === UserRole::Admin) return true;
        $role = $project->memberRole($user);
        return in_array($role, [UserRole::ProjectManager]);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $this->create($user, $sprint->project);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return $this->create($user, $sprint->project);
    }
}
