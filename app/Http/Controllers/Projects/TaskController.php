<?php

namespace App\Http\Controllers\Projects;

use App\Enums\BrPriority;
use App\Enums\EffortUnit;
use App\Enums\TaskCategory;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\TaskRequest;
use App\Http\Requests\Projects\TaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
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
            ->when($request->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->sprint_id, fn ($q, $v) => $v === 'none' ? $q->whereNull('sprint_id') : $q->where('sprint_id', $v))
            ->orderByRaw("CASE status WHEN 'done' THEN 1 WHEN 'cancelled' THEN 2 ELSE 0 END")
            ->orderBy('due_date')
            ->paginate(25)
            ->through(fn ($t) => TaskResource::list($t));

        $sprints = $project->sprints()->orderBy('start_date')->get(['id', 'name']);

        return Inertia::render('projects/tasks/Index', [
            'project' => $project->only('id', 'name'),
            'tasks'   => $tasks,
            'sprints' => $sprints->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name]),
            'filters' => $request->only('search', 'status', 'sprint_id'),
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
            'priorities'   => collect(BrPriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'categories'   => collect(TaskCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'effort_units' => collect(EffortUnit::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'linkable_brs' => $project->businessRequirements()->orderBy('number')->get()->map(fn ($br) => ['value' => $br->id, 'label' => "{$br->ref} — {$br->title}"]),
            'linkable_trs' => $project->technicalRequirements()->orderBy('number')->get()->map(fn ($tr) => ['value' => $tr->id, 'label' => "{$tr->ref} — {$tr->title}"]),
            'linkable_tcs' => $project->testCases()->orderBy('number')->get()->map(fn ($tc) => ['value' => $tc->id, 'label' => "{$tc->ref} — {$tc->title}"]),
        ]);
    }

    public function store(TaskRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $data = $request->validated();

        $task = $project->tasks()->create([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'sprint_id'       => $data['sprint_id'] ?? null,
            'effort_estimate' => $data['effort_estimate'] ?? null,
            'effort_unit'     => $data['effort_unit'],
            'status'          => $data['status'],
            'priority'        => $data['priority'],
            'category'        => $data['category'] ?? null,
            'due_date'        => $data['due_date'] ?? null,
            'start_date'      => $data['start_date'] ?? null,
            'end_date'        => $data['end_date'] ?? null,
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
            'task'    => TaskResource::detail($task),
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
                'priority'        => $task->priority->value,
                'category'        => $task->category?->value,
                'due_date'        => $task->due_date?->toDateString(),
                'start_date'      => $task->start_date?->toDateString(),
                'end_date'        => $task->end_date?->toDateString(),
                'assignee_id'     => $task->assignee_id,
                'linked_br_ids'   => $task->linkedBrs->pluck('id')->all(),
                'linked_tr_ids'   => $task->linkedTrs->pluck('id')->all(),
                'linked_tc_ids'   => $task->linkedTcs->pluck('id')->all(),
            ],
            'sprints'      => $project->sprints()->get()->map(fn ($s) => ['value' => $s->id, 'label' => $s->name]),
            'members'      => $project->projectMembers()->with('user:id,name')->get()->map(fn ($m) => ['value' => $m->user_id, 'label' => $m->user->name]),
            'statuses'     => collect(TaskStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities'   => collect(BrPriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'categories'   => collect(TaskCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'effort_units' => collect(EffortUnit::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'linkable_brs' => $project->businessRequirements()->orderBy('number')->get()->map(fn ($br) => ['value' => $br->id, 'label' => "{$br->ref} — {$br->title}"]),
            'linkable_trs' => $project->technicalRequirements()->orderBy('number')->get()->map(fn ($tr) => ['value' => $tr->id, 'label' => "{$tr->ref} — {$tr->title}"]),
            'linkable_tcs' => $project->testCases()->orderBy('number')->get()->map(fn ($tc) => ['value' => $tc->id, 'label' => "{$tc->ref} — {$tc->title}"]),
        ]);
    }

    public function update(TaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        $wasDone  = $task->status === TaskStatus::Done;
        $nowDone  = $data['status'] === TaskStatus::Done->value;

        $task->update([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'sprint_id'       => $data['sprint_id'] ?? null,
            'effort_estimate' => $data['effort_estimate'] ?? null,
            'effort_unit'     => $data['effort_unit'],
            'status'          => $data['status'],
            'priority'        => $data['priority'],
            'category'        => $data['category'] ?? null,
            'due_date'        => $data['due_date'] ?? null,
            'start_date'      => $data['start_date'] ?? null,
            'end_date'        => $data['end_date'] ?? null,
            'assignee_id'     => $data['assignee_id'] ?? null,
            'completed_at'    => ! $wasDone && $nowDone ? now() : ($wasDone && ! $nowDone ? null : $task->completed_at),
        ]);

        $task->linkedBrs()->sync($data['linked_br_ids'] ?? []);
        $task->linkedTrs()->sync($data['linked_tr_ids'] ?? []);
        $task->linkedTcs()->sync($data['linked_tc_ids'] ?? []);

        return redirect()->route('projects.tasks.show', [$project, $task])
            ->with('success', 'Task updated.');
    }

    public function updateStatus(TaskStatusRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        $wasDone = $task->status === TaskStatus::Done;
        $nowDone = $data['status'] === TaskStatus::Done->value;

        $task->update([
            'status'       => $data['status'],
            'completed_at' => ! $wasDone && $nowDone ? now() : ($wasDone && ! $nowDone ? null : $task->completed_at),
        ]);

        return back();
    }

    public function calendar(Project $project): Response
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $tasks = $project->tasks()
            ->with(['assignee:id,name'])
            ->get()
            ->map(fn ($t) => TaskResource::calendarItem($t));

        return Inertia::render('projects/tasks/Calendar', [
            'project' => $project->only('id', 'name'),
            'tasks'   => $tasks,
        ]);
    }

    public function gantt(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $tasks = $project->tasks()
            ->with(['assignee:id,name', 'sprint:id,name'])
            ->orderByRaw('start_date IS NULL, start_date')
            ->orderByRaw('due_date IS NULL, due_date')
            ->get()
            ->map(fn ($t) => TaskResource::ganttItem($t));

        return Inertia::render('projects/tasks/Gantt', [
            'project' => $project->only('id', 'name'),
            'tasks'   => $tasks,
        ]);
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('projects.tasks.index', $project)
            ->with('success', 'Task deleted.');
    }

}
