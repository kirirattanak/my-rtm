<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $projects = Project::with('owner')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('projectMembers', fn ($q) => $q->where('user_id', $user->id)))
            ->withCount('projectMembers')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (Project $p) => ProjectResource::list($p));

        return Inertia::render('projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/Create', [
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();

        $project = Project::create([
            ...$data,
            'owner_id' => $request->user()->id,
        ]);

        // Owner is automatically added as a Project Manager member
        $project->projectMembers()->create([
            'user_id' => $request->user()->id,
            'role'    => UserRole::ProjectManager->value,
        ]);

        return to_route('projects.show', $project)
            ->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load(['owner', 'projectMembers.user']);

        // Coverage stats
        $trs = $project->technicalRequirements()->with('testCases.runs')->get();
        $coveredTrIds = $trs->filter(fn ($tr) =>
            $tr->testCases->contains(fn ($tc) =>
                $tc->runs->contains(fn ($r) => $r->status->value === 'pass')
            )
        )->pluck('id')->all();

        $brs = $project->businessRequirements()->with('technicalRequirements:id')->get();
        $coveredBrs = $brs->filter(fn ($br) =>
            $br->technicalRequirements->isNotEmpty() &&
            $br->technicalRequirements->every(fn ($tr) => in_array($tr->id, $coveredTrIds))
        )->count();

        $totalTrs = $trs->count();
        $totalBrs = $brs->count();
        $totalTcs = $project->testCases()->count();

        $coverage = [
            'br_count'    => $totalBrs,
            'tr_count'    => $totalTrs,
            'tc_count'    => $totalTcs,
            'covered_brs' => $coveredBrs,
            'covered_trs' => count($coveredTrIds),
            'br_coverage' => $totalBrs ? round(($coveredBrs / $totalBrs) * 100) : 0,
            'tr_coverage' => $totalTrs ? round((count($coveredTrIds) / $totalTrs) * 100) : 0,
        ];

        // Recent activity
        $brIds   = $brs->pluck('id');
        $trIds   = $trs->pluck('id');
        $tcIds   = $project->testCases()->pluck('id');
        $taskIds = $project->tasks()->pluck('id');

        $activity = \App\Models\ActivityLog::with('user:id,name')
            ->where(function ($q) use ($brIds, $trIds, $tcIds, $taskIds) {
                $q->where(function ($q) use ($brIds) {
                    $q->where('subject_type', \App\Models\BusinessRequirement::class)
                      ->whereIn('subject_id', $brIds);
                })->orWhere(function ($q) use ($trIds) {
                    $q->where('subject_type', \App\Models\TechnicalRequirement::class)
                      ->whereIn('subject_id', $trIds);
                })->orWhere(function ($q) use ($tcIds) {
                    $q->where('subject_type', \App\Models\TestCase::class)
                      ->whereIn('subject_id', $tcIds);
                })->orWhere(function ($q) use ($taskIds) {
                    $q->where('subject_type', \App\Models\Task::class)
                      ->whereIn('subject_id', $taskIds);
                });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id'            => $log->id,
                'action'        => $log->action,
                'subject_type'  => match ($log->subject_type) {
                    \App\Models\BusinessRequirement::class  => 'BR',
                    \App\Models\TechnicalRequirement::class => 'TR',
                    \App\Models\TestCase::class             => 'TC',
                    \App\Models\Task::class                 => 'Task',
                    default                                 => '?',
                },
                'subject_id'    => $log->subject_id,
                'subject_title' => $log->data['title'] ?? null,
                'path'          => match ($log->subject_type) {
                    \App\Models\BusinessRequirement::class  => route('projects.requirements.business.show', [$project->id, $log->subject_id]),
                    \App\Models\TechnicalRequirement::class => route('projects.requirements.technical.show', [$project->id, $log->subject_id]),
                    \App\Models\TestCase::class             => route('projects.test-cases.show', [$project->id, $log->subject_id]),
                    \App\Models\Task::class                 => route('projects.tasks.show', [$project->id, $log->subject_id]),
                    default                                 => '#',
                },
                'user_name'     => $log->user->name,
                'created_at'    => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('projects/Show', [
            'project' => [
                'id'           => $project->id,
                'name'         => $project->name,
                'description'  => $project->description,
                'status'       => $project->status->value,
                'status_label' => $project->status->label(),
                'status_color' => $project->status->color(),
                'owner'        => $project->owner->name,
                'start_date'   => $project->start_date?->toDateString(),
                'target_date'  => $project->target_date?->toDateString(),
                'created_at'   => $project->created_at->toDateString(),
                'members'      => $project->projectMembers->map(fn ($m) => [
                    'id'         => $m->id,
                    'user_id'    => $m->user_id,
                    'name'       => $m->user->name,
                    'email'      => $m->user->email,
                    'role'       => $m->role->value,
                    'role_label' => $m->role->label(),
                ]),
            ],
            'coverage' => $coverage,
            'activity' => $activity,
            'can' => [
                'edit'          => $request->user()->can('update', $project),
                'manageMembers' => $request->user()->can('manageMembers', $project),
                'delete'        => $request->user()->can('delete', $project),
            ],
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/Edit', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'status'      => $project->status->value,
                'start_date'  => $project->start_date?->toDateString(),
                'target_date' => $project->target_date?->toDateString(),
            ],
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validated();

        $project->update($data);

        return to_route('projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('dashboard')
            ->with('success', 'Project deleted.');
    }
}
