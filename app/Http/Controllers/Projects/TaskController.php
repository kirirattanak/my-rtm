<?php

namespace App\Http\Controllers\Projects;

use App\Enums\EffortUnit;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\Task;
use App\Models\TechnicalRequirement;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $tasks = $project->tasks()
            ->with(['assignee:id,name', 'sprint:id,name'])
            ->orderByRaw("CASE status WHEN 'done' THEN 1 WHEN 'cancelled' THEN 2 ELSE 0 END")
            ->orderBy('due_date')
            ->get()
            ->map(fn ($t) => [
                'id'               => $t->id,
                'title'            => $t->title,
                'status'           => $t->status->value,
                'status_label'     => $t->status->label(),
                'effort_estimate'  => $t->effort_estimate,
                'effort_unit'      => $t->effort_unit->value,
                'effort_unit_short'=> $t->effort_unit->shortLabel(),
                'due_date'         => $t->due_date?->toDateString(),
                'assignee'         => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
                'sprint'           => $t->sprint ? ['id' => $t->sprint->id, 'name' => $t->sprint->name] : null,
            ]);

        return Inertia::render('projects/tasks/Index', [
            'project' => $project->only('id', 'name'),
            'tasks'   => $tasks,
            'can'     => [
                'create' => $request->user()->can('create', [Task::class, $project]),
            ],
        ]);
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', [Task::class, $project]);

        return Inertia::render('projects/tasks/Create', [
            'project'      => $project->only('id', 'name'),
            'sprints'      => $project->sprints()->get()->map(fn ($s) => ['value' => $s->id, 'label' => $s->name]),
            'members'      => $project->projectMembers()->with('user:id,name')->get()->map(fn ($m) => ['value' => $m->user_id, 'label' => $m->user->name]),
            'statuses'     => collect(TaskStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'effort_units' => collect(EffortUnit::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'linkable_brs' => $project->businessRequirements()->orderBy('number')->get()->map(fn ($br) => ['value' => $br->id, 'label' => "{$br->ref} — {$br->title}"]),
            'linkable_trs' => $project->technicalRequirements()->orderBy('number')->get()->map(fn ($tr) => ['value' => $tr->id, 'label' => "{$tr->ref} — {$tr->title}"]),
            'linkable_tcs' => $project->testCases()->orderBy('number')->get()->map(fn ($tc) => ['value' => $tc->id, 'label' => "{$tc->ref} — {$tc->title}"]),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'sprint_id'       => 'nullable|exists:sprints,id',
            'effort_estimate' => 'nullable|numeric|min:0',
            'effort_unit'     => 'required|in:' . implode(',', EffortUnit::values()),
            'status'          => 'required|in:' . implode(',', TaskStatus::values()),
            'due_date'        => 'nullable|date',
            'assignee_id'     => 'nullable|exists:users,id',
            'linked_br_ids'   => 'nullable|array',
            'linked_br_ids.*' => 'integer|exists:business_requirements,id',
            'linked_tr_ids'   => 'nullable|array',
            'linked_tr_ids.*' => 'integer|exists:technical_requirements,id',
            'linked_tc_ids'   => 'nullable|array',
            'linked_tc_ids.*' => 'integer|exists:test_cases,id',
        ]);

        $task = $project->tasks()->create([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'sprint_id'       => $data['sprint_id'] ?? null,
            'effort_estimate' => $data['effort_estimate'] ?? null,
            'effort_unit'     => $data['effort_unit'],
            'status'          => $data['status'],
            'due_date'        => $data['due_date'] ?? null,
            'assignee_id'     => $data['assignee_id'] ?? null,
            'created_by'      => $request->user()->id,
        ]);

        $task->linkedBrs()->sync($data['linked_br_ids'] ?? []);
        $task->linkedTrs()->sync($data['linked_tr_ids'] ?? []);
        $task->linkedTcs()->sync($data['linked_tc_ids'] ?? []);

        return redirect()->route('projects.tasks.index', $project)
            ->with('success', 'Task created.');
    }

    public function show(Request $request, Project $project, Task $task): Response
    {
        $this->authorize('view', $task);

        $task->load(['assignee:id,name', 'sprint:id,name', 'creator:id,name', 'logs.logger:id,name', 'linkedBrs', 'linkedTrs', 'linkedTcs']);

        return Inertia::render('projects/tasks/Show', [
            'project' => $project->only('id', 'name'),
            'task'    => [
                'id'                => $task->id,
                'title'             => $task->title,
                'description'       => $task->description,
                'status'            => $task->status->value,
                'status_label'      => $task->status->label(),
                'effort_estimate'   => $task->effort_estimate,
                'effort_unit'       => $task->effort_unit->value,
                'effort_unit_label' => $task->effort_unit->label(),
                'effort_unit_short' => $task->effort_unit->shortLabel(),
                'due_date'          => $task->due_date?->toDateString(),
                'assignee'          => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
                'sprint'            => $task->sprint ? ['id' => $task->sprint->id, 'name' => $task->sprint->name] : null,
                'creator'           => $task->creator,
                'created_at'        => $task->created_at->toDateString(),
                'completed_at'      => $task->completed_at?->toDateString(),
                'logged_hours'      => $task->totalLoggedHours(),
                'effective_actual'  => $task->effectiveActualHours(),
                'linked_brs'        => $task->linkedBrs->map(fn ($br) => ['id' => $br->id, 'ref' => $br->ref, 'title' => $br->title]),
                'linked_trs'        => $task->linkedTrs->map(fn ($tr) => ['id' => $tr->id, 'ref' => $tr->ref, 'title' => $tr->title]),
                'linked_tcs'        => $task->linkedTcs->map(fn ($tc) => ['id' => $tc->id, 'ref' => $tc->ref, 'title' => $tc->title]),
                'logs'              => $task->logs->map(fn ($log) => [
                    'id'          => $log->id,
                    'hours'       => $log->hours,
                    'notes'       => $log->notes,
                    'logger_name' => $log->logger->name,
                    'created_at'  => $log->created_at->toDateString(),
                ]),
            ],
            'can' => [
                'edit'     => $request->user()->can('update', $task),
                'delete'   => $request->user()->can('delete', $task),
                'logHours' => $request->user()->can('logHours', $task),
            ],
        ]);
    }

    public function edit(Project $project, Task $task): Response
    {
        $this->authorize('update', $task);

        $task->load(['linkedBrs', 'linkedTrs', 'linkedTcs']);

        return Inertia::render('projects/tasks/Edit', [
            'project'      => $project->only('id', 'name'),
            'task'         => [
                'id'              => $task->id,
                'title'           => $task->title,
                'description'     => $task->description,
                'sprint_id'       => $task->sprint_id,
                'effort_estimate' => $task->effort_estimate,
                'effort_unit'     => $task->effort_unit->value,
                'status'          => $task->status->value,
                'due_date'        => $task->due_date?->toDateString(),
                'assignee_id'     => $task->assignee_id,
                'linked_br_ids'   => $task->linkedBrs->pluck('id')->all(),
                'linked_tr_ids'   => $task->linkedTrs->pluck('id')->all(),
                'linked_tc_ids'   => $task->linkedTcs->pluck('id')->all(),
            ],
            'sprints'      => $project->sprints()->get()->map(fn ($s) => ['value' => $s->id, 'label' => $s->name]),
            'members'      => $project->projectMembers()->with('user:id,name')->get()->map(fn ($m) => ['value' => $m->user_id, 'label' => $m->user->name]),
            'statuses'     => collect(TaskStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'effort_units' => collect(EffortUnit::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'linkable_brs' => $project->businessRequirements()->orderBy('number')->get()->map(fn ($br) => ['value' => $br->id, 'label' => "{$br->ref} — {$br->title}"]),
            'linkable_trs' => $project->technicalRequirements()->orderBy('number')->get()->map(fn ($tr) => ['value' => $tr->id, 'label' => "{$tr->ref} — {$tr->title}"]),
            'linkable_tcs' => $project->testCases()->orderBy('number')->get()->map(fn ($tc) => ['value' => $tc->id, 'label' => "{$tc->ref} — {$tc->title}"]),
        ]);
    }

    public function update(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'sprint_id'       => 'nullable|exists:sprints,id',
            'effort_estimate' => 'nullable|numeric|min:0',
            'effort_unit'     => 'required|in:' . implode(',', EffortUnit::values()),
            'status'          => 'required|in:' . implode(',', TaskStatus::values()),
            'due_date'        => 'nullable|date',
            'assignee_id'     => 'nullable|exists:users,id',
            'linked_br_ids'   => 'nullable|array',
            'linked_br_ids.*' => 'integer|exists:business_requirements,id',
            'linked_tr_ids'   => 'nullable|array',
            'linked_tr_ids.*' => 'integer|exists:technical_requirements,id',
            'linked_tc_ids'   => 'nullable|array',
            'linked_tc_ids.*' => 'integer|exists:test_cases,id',
        ]);

        $wasDone  = $task->status === TaskStatus::Done;
        $nowDone  = $data['status'] === TaskStatus::Done->value;

        $task->update([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'sprint_id'       => $data['sprint_id'] ?? null,
            'effort_estimate' => $data['effort_estimate'] ?? null,
            'effort_unit'     => $data['effort_unit'],
            'status'          => $data['status'],
            'due_date'        => $data['due_date'] ?? null,
            'assignee_id'     => $data['assignee_id'] ?? null,
            'completed_at'    => ! $wasDone && $nowDone ? now() : ($wasDone && ! $nowDone ? null : $task->completed_at),
        ]);

        $task->linkedBrs()->sync($data['linked_br_ids'] ?? []);
        $task->linkedTrs()->sync($data['linked_tr_ids'] ?? []);
        $task->linkedTcs()->sync($data['linked_tc_ids'] ?? []);

        return redirect()->route('projects.tasks.show', [$project, $task])
            ->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', TaskStatus::values()),
        ]);

        $wasDone = $task->status === TaskStatus::Done;
        $nowDone = $data['status'] === TaskStatus::Done->value;

        $task->update([
            'status'       => $data['status'],
            'completed_at' => ! $wasDone && $nowDone ? now() : ($wasDone && ! $nowDone ? null : $task->completed_at),
        ]);

        return back();
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('projects.tasks.index', $project)
            ->with('success', 'Task deleted.');
    }

}
