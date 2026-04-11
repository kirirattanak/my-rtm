<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\TestSuite;
use App\Models\User;

class TestSuitePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin || $project->hasMember($user);
    }

    public function view(User $user, TestSuite $suite): bool
    {
        return $user->role === UserRole::Admin || $suite->project->hasMember($user);
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

    public function update(User $user, TestSuite $suite): bool
    {
        return $this->create($user, $suite->project);
    }

    public function delete(User $user, TestSuite $suite): bool
    {
        if ($user->role === UserRole::Admin) return true;

        return $suite->project->memberRole($user) === UserRole::ProjectManager;
    }
}
