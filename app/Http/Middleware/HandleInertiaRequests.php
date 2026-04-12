<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Services\TierGate;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    private function shareTier(Request $request): ?array
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $org = $user->organization()->with('activeSubscription.tierOption')->first();
        if (!$org) {
            return null;
        }

        $gate = TierGate::for($org);

        return [
            'name'  => $org->activeSubscription?->tierOption?->tier,
            'label' => $org->activeSubscription?->tierOption?->tierLabel(),
            'seats' => $org->activeSubscription?->tierOption?->seats,
            'seats_used' => $org->activeSeatCount(),
            'features' => [
                'br'             => $user->isAdmin() || $gate->can('br'),
                'tr'             => $user->isAdmin() || $gate->can('tr'),
                'tc'             => $user->isAdmin() || $gate->can('tc'),
                'test_runs'      => $user->isAdmin() || $gate->can('test_runs'),
                'test_suites'    => $user->isAdmin() || $gate->can('test_suites'),
                'sprints'        => $user->isAdmin() || $gate->can('sprints'),
                'rtm'            => $user->isAdmin() || $gate->can('rtm'),
                'reports'        => $user->isAdmin() || $gate->can('reports'),
                'imports_exports' => $user->isAdmin() || $gate->can('imports_exports'),
            ],
        ];
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $projectId = $request->route('project');
        $currentProject = null;

        if ($projectId && $request->user()) {
            $project = $projectId instanceof Project
                ? $projectId
                : Project::find(is_object($projectId) ? $projectId->id : $projectId);

            if ($project) {
                $currentProject = ['id' => $project->id, 'name' => $project->name];
            }
        }

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user'     => $request->user(),
                'is_admin' => $request->user()?->isAdmin() ?? false,
            ],
            'tier' => fn () => $this->shareTier($request),
            'currentProject' => $currentProject,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'unreadNotificationsCount' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
