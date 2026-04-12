<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TestSuite;
use App\Models\User;

class TestSuitePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermission('test_suites.view', $project);
    }

    public function view(User $user, TestSuite $suite): bool
    {
        return $user->hasPermission('test_suites.view', $suite->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermission('test_suites.create', $project);
    }

    public function update(User $user, TestSuite $suite): bool
    {
        return $user->hasPermission('test_suites.create', $suite->project);
    }

    public function delete(User $user, TestSuite $suite): bool
    {
        return $user->hasPermission('test_suites.delete', $suite->project);
    }
}
