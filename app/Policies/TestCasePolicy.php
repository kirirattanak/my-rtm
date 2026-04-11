<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;

class TestCasePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin || $project->hasMember($user);
    }

    public function view(User $user, TestCase $tc): bool
    {
        return $user->role === UserRole::Admin || $tc->project->hasMember($user);
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->role === UserRole::Admin) return true;

        $memberRole = $project->memberRole($user);

        return in_array($memberRole, [
            UserRole::ProjectManager,
            UserRole::BusinessAnalyst,
            UserRole::Developer,
            UserRole::Tester,
        ]);
    }

    public function update(User $user, TestCase $tc): bool
    {
        return $this->create($user, $tc->project);
    }

    public function delete(User $user, TestCase $tc): bool
    {
        if ($user->role === UserRole::Admin) return true;

        return $tc->project->memberRole($user) === UserRole::ProjectManager;
    }

    public function logRun(User $user, TestCase $tc): bool
    {
        return $this->create($user, $tc->project);
    }
}
