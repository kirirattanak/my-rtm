<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
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
            ->get()
            ->map(fn (Project $p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'description'    => $p->description,
                'status'         => $p->status->value,
                'status_label'   => $p->status->label(),
                'status_color'   => $p->status->color(),
                'owner'          => $p->owner->name,
                'start_date'     => $p->start_date?->toDateString(),
                'target_date'    => $p->target_date?->toDateString(),
                'members_count'  => $p->project_members_count,
            ]);

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

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(ProjectStatus::values())],
            'start_date'  => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

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
            'can' => [
                'edit'          => $request->user()->can('update', $project),
                'manageMembers' => $request->user()->can('manageMembers', $project),
                'delete'        => $request->user()->can('delete', $project),
            ],
        ]);
    }

    public function edit(Request $request, Project $project): Response
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

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(ProjectStatus::values())],
            'start_date'  => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

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
