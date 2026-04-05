<?php

namespace App\Http\Controllers\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TestCaseType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestCaseController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [TestCase::class, $project]);

        $tcs = $project->testCases()
            ->with(['creator:id,name', 'assignee:id,name', 'runs' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('runs')
            ->orderBy('number')
            ->paginate(25)
            ->through(fn ($tc) => [
                'id'             => $tc->id,
                'ref'            => $tc->ref,
                'number'         => $tc->number,
                'title'          => $tc->title,
                'type'           => $tc->type->value,
                'type_label'     => $tc->type->label(),
                'priority'       => $tc->priority->value,
                'priority_label' => $tc->priority->label(),
                'status'         => $tc->status->value,
                'status_label'   => $tc->status->label(),
                'status_color'   => $tc->status->color(),
                'assignee'       => $tc->assignee,
                'creator'        => $tc->creator,
                'runs_count'     => $tc->runs_count,
                'latest_run'     => $tc->runs->first()?->status,
                'created_at'     => $tc->created_at,
            ]);

        return Inertia::render('projects/test-cases/Index', [
            'project' => $project->only('id', 'name'),
            'tcs'     => $tcs,
            'can'     => [
                'create' => $request->user()->can('create', [TestCase::class, $project]),
            ],
        ]);
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', [TestCase::class, $project]);

        $members = $project->members()->get()->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->name,
        ]);

        return Inertia::render('projects/test-cases/Create', [
            'project'  => $project->only('id', 'name'),
            'members'  => $members,
            'types'    => collect(TestCaseType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities' => collect(BrPriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses' => collect(RequirementStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [TestCase::class, $project]);

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'steps'           => 'nullable|array',
            'steps.*'         => 'string|max:500',
            'expected_result' => 'nullable|string',
            'type'            => 'required|in:' . implode(',', TestCaseType::values()),
            'priority'        => 'required|in:' . implode(',', BrPriority::values()),
            'status'          => 'required|in:' . implode(',', RequirementStatus::values()),
            'assignee_id'     => 'nullable|exists:users,id',
        ]);

        $number = ($project->testCases()->max('number') ?? 0) + 1;

        $project->testCases()->create([
            ...$data,
            'number'     => $number,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('projects.test-cases.index', $project)
            ->with('success', 'Test case created.');
    }

    public function show(Request $request, Project $project, TestCase $testCase): Response
    {
        $this->authorize('view', $testCase);

        $tc = $testCase->load([
            'creator:id,name',
            'assignee:id,name',
            'technicalRequirements:id,number,title,status',
            'runs.executor:id,name',
        ]);

        $linkableTrs = $project->technicalRequirements()
            ->whereNotIn('id', $tc->technicalRequirements->pluck('id'))
            ->orderBy('number')
            ->get()
            ->map(fn ($tr) => ['id' => $tr->id, 'ref' => $tr->ref, 'title' => $tr->title]);

        return Inertia::render('projects/test-cases/Show', [
            'project'      => $project->only('id', 'name'),
            'linkable_trs' => $linkableTrs,
            'tc'           => [
                'id'              => $tc->id,
                'ref'             => $tc->ref,
                'number'          => $tc->number,
                'title'           => $tc->title,
                'description'     => $tc->description,
                'steps'           => $tc->steps ?? [],
                'expected_result' => $tc->expected_result,
                'type'            => $tc->type->value,
                'type_label'      => $tc->type->label(),
                'priority'        => $tc->priority->value,
                'priority_label'  => $tc->priority->label(),
                'status'          => $tc->status->value,
                'status_label'    => $tc->status->label(),
                'status_color'    => $tc->status->color(),
                'assignee'        => $tc->assignee,
                'creator'         => $tc->creator,
                'created_at'      => $tc->created_at,
                'updated_at'      => $tc->updated_at,
                'technical_requirements' => $tc->technicalRequirements->map(fn ($tr) => [
                    'id'          => $tr->id,
                    'ref'         => $tr->ref,
                    'title'       => $tr->title,
                    'status'      => $tr->status->value,
                    'status_label' => $tr->status->label(),
                    'status_color' => $tr->status->color(),
                ]),
                'runs' => $tc->runs->map(fn ($r) => [
                    'id'         => $r->id,
                    'status'     => $r->status->value,
                    'status_label' => $r->status->label(),
                    'color'      => $r->status->color(),
                    'notes'      => $r->notes,
                    'executor'   => $r->executor,
                    'created_at' => $r->created_at,
                ]),
            ],
            'can' => [
                'edit'    => $request->user()->can('update', $tc),
                'delete'  => $request->user()->can('delete', $tc),
                'log_run' => $request->user()->can('logRun', $tc),
            ],
            'run_statuses' => collect(\App\Enums\TestRunStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'color' => $c->color(),
            ]),
        ]);
    }

    public function edit(Project $project, TestCase $testCase): Response
    {
        $this->authorize('update', $testCase);

        $members = $project->members()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('projects/test-cases/Edit', [
            'project'  => $project->only('id', 'name'),
            'members'  => $members,
            'tc'       => [
                'id'              => $testCase->id,
                'ref'             => $testCase->ref,
                'title'           => $testCase->title,
                'description'     => $testCase->description,
                'steps'           => $testCase->steps ?? [],
                'expected_result' => $testCase->expected_result,
                'type'            => $testCase->type->value,
                'priority'        => $testCase->priority->value,
                'status'          => $testCase->status->value,
                'assignee_id'     => $testCase->assignee_id,
            ],
            'types'    => collect(TestCaseType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities' => collect(BrPriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses' => collect(RequirementStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function update(Request $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('update', $testCase);

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'steps'           => 'nullable|array',
            'steps.*'         => 'string|max:500',
            'expected_result' => 'nullable|string',
            'type'            => 'required|in:' . implode(',', TestCaseType::values()),
            'priority'        => 'required|in:' . implode(',', BrPriority::values()),
            'status'          => 'required|in:' . implode(',', RequirementStatus::values()),
            'assignee_id'     => 'nullable|exists:users,id',
        ]);

        $testCase->update($data);

        return redirect()->route('projects.test-cases.show', [$project, $testCase])
            ->with('success', 'Test case updated.');
    }

    public function destroy(Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('delete', $testCase);

        $testCase->delete();

        return redirect()->route('projects.test-cases.index', $project)
            ->with('success', 'Test case deleted.');
    }
}
