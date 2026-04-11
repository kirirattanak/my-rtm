<?php

namespace App\Providers;

use App\Models\BusinessRequirement;
use App\Models\Comment;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\TestCase;
use App\Models\TaskLog;
use App\Models\User;
use App\Observers\BusinessRequirementObserver;
use App\Observers\CommentObserver;
use App\Observers\TechnicalRequirementObserver;
use App\Observers\TestCaseObserver;
use App\Models\Sprint;
use App\Models\Task;
use App\Observers\TaskLogObserver;
use App\Observers\TaskObserver;
use App\Policies\BusinessRequirementPolicy;
use App\Policies\CommentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SprintPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TechnicalRequirementPolicy;
use App\Policies\TestCasePolicy;
use App\Policies\TestSuitePolicy;
use App\Policies\UserPolicy;
use App\Models\TestSuite;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BusinessRequirement::observe(BusinessRequirementObserver::class);
        Comment::observe(CommentObserver::class);
        TechnicalRequirement::observe(TechnicalRequirementObserver::class);
        TestCase::observe(TestCaseObserver::class);
        Task::observe(TaskObserver::class);
        TaskLog::observe(TaskLogObserver::class);

        Gate::policy(BusinessRequirement::class, BusinessRequirementPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Sprint::class, SprintPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TechnicalRequirement::class, TechnicalRequirementPolicy::class);
        Gate::policy(TestCase::class, TestCasePolicy::class);
        Gate::policy(TestSuite::class, TestSuitePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
