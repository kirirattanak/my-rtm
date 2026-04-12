<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;

class TestCasePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermission('tc.view', $project);
    }

    public function view(User $user, TestCase $tc): bool
    {
        return $user->hasPermission('tc.view', $tc->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermission('tc.create', $project);
    }

    public function update(User $user, TestCase $tc): bool
    {
        return $user->hasPermission('tc.edit', $tc->project);
    }

    public function delete(User $user, TestCase $tc): bool
    {
        return $user->hasPermission('tc.delete', $tc->project);
    }

    public function import(User $user, Project $project): bool
    {
        return $user->hasPermission('tc.import', $project);
    }

    public function export(User $user, Project $project): bool
    {
        return $user->hasPermission('tc.export', $project);
    }

    public function logRun(User $user, TestCase $tc): bool
    {
        return $user->hasPermission('test_runs.create', $tc->project);
    }
}
