<?php

namespace App\Providers;

use App\Models\BusinessRequirement;
use App\Models\Comment;
use App\Models\TechnicalRequirement;
use App\Models\TestCase;
use App\Observers\BusinessRequirementObserver;
use App\Observers\TechnicalRequirementObserver;
use App\Observers\TestCaseObserver;
use App\Models\Sprint;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Policies\CommentPolicy;
use App\Policies\SprintPolicy;
use App\Policies\TaskPolicy;
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
        TechnicalRequirement::observe(TechnicalRequirementObserver::class);
        TestCase::observe(TestCaseObserver::class);

        Task::observe(TaskObserver::class);

        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Sprint::class, SprintPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
