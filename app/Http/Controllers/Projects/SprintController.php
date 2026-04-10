<?php

namespace App\Http\Controllers\Projects;

use App\Enums\EffortUnit;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\SprintRequest;
use App\Http\Resources\SprintResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Sprint;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SprintController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [Sprint::class, $project]);

        $sprints = $project->sprints()
            ->withCount('tasks')
            ->orderBy('start_date')
            ->paginate(20)
            ->through(fn ($s) => SprintResource::summary($s));

        return Inertia::render('projects/sprints/Index', [
            'project' => $project->only('id', 'name'),
            'sprints' => $sprints,
            'can'     => [
                'create' => $request->user()->can('create', [Sprint::class, $project]),
            ],
        ]);
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', [Sprint::class, $project]);

        return Inertia::render('projects/sprints/Create', [
            'project' => $project->only('id', 'name'),
        ]);
    }

    public function store(SprintRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Sprint::class, $project]);

        $data = $request->validated();

        $project->sprints()->create($data);

        return redirect()->route('projects.sprints.index', $project)
            ->with('success', 'Sprint created.');
    }

    public function show(Request $request, Project $project, Sprint $sprint): Response
    {
        $this->authorize('view', $sprint);

        $sprint->load(['tasks' => function ($q) {
            $q->with(['assignee:id,name', 'logs']);
        }]);

        // Workload: group tasks by assignee, sum effort per unit
        $workload = $sprint->tasks
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->map(function ($tasks) {
                $assignee = $tasks->first()->assignee;
                $byUnit = $tasks->groupBy(fn ($t) => $t->effort_unit->value);

                return [
                    'assignee_id'   => $assignee->id,
                    'assignee_name' => $assignee->name,
                    'points'        => [
                        'planned'  => $byUnit->get('points', collect())->sum('effort_estimate'),
                        'done'     => $byUnit->get('points', collect())->where('status', TaskStatus::Done)->sum('effort_estimate'),
                    ],
                    'hours'         => [
                        'planned'  => $byUnit->get('hours', collect())->sum('effort_estimate'),
                        'actual'   => $byUnit->get('hours', collect())->sum(fn ($t) => $t->effectiveActualHours() ?? 0),
                        'done'     => $byUnit->get('hours', collect())->where('status', TaskStatus::Done)->sum('effort_estimate'),
                    ],
                ];
            })->values();

        // Burndown data (hours-unit tasks only)
        $burndown = $this->buildBurndown($sprint);

        return Inertia::render('projects/sprints/Show', [
            'project'  => $project->only('id', 'name'),
            'sprint'   => [
                'id'         => $sprint->id,
                'name'       => $sprint->name,
                'start_date' => $sprint->start_date->toDateString(),
                'end_date'   => $sprint->end_date->toDateString(),
                'capacity'   => $sprint->capacity,
                'is_active'  => $sprint->isActive(),
                'tasks'      => $sprint->tasks->map(fn ($t) => TaskResource::sprintItem($t)),
            ],
            'workload'   => $workload,
            'burndown'   => $burndown,
            'can'        => [
                'edit'   => $request->user()->can('update', $sprint),
                'delete' => $request->user()->can('delete', $sprint),
            ],
        ]);
    }

    public function edit(Project $project, Sprint $sprint): Response
    {
        $this->authorize('update', $sprint);

        return Inertia::render('projects/sprints/Edit', [
            'project' => $project->only('id', 'name'),
            'sprint'  => [
                'id'         => $sprint->id,
                'name'       => $sprint->name,
                'start_date' => $sprint->start_date->toDateString(),
                'end_date'   => $sprint->end_date->toDateString(),
                'capacity'   => $sprint->capacity,
            ],
        ]);
    }

    public function update(SprintRequest $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorize('update', $sprint);

        $data = $request->validated();

        $sprint->update($data);

        return redirect()->route('projects.sprints.show', [$project, $sprint])
            ->with('success', 'Sprint updated.');
    }

    public function destroy(Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorize('delete', $sprint);

        $sprint->delete();

        return redirect()->route('projects.sprints.index', $project)
            ->with('success', 'Sprint deleted.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function buildBurndown(Sprint $sprint): array
    {
        $hourTasks = $sprint->tasks->where('effort_unit', EffortUnit::Hours);
        $totalPlanned = $hourTasks->sum('effort_estimate');

        $start = $sprint->start_date;
        $end = $sprint->end_date->min(now());
        $period = CarbonPeriod::create($start, $end);

        $labels = [];
        $ideal  = [];
        $actual = [];

        $sprintDays = $sprint->start_date->diffInDays($sprint->end_date);
        $dailyBurn  = $sprintDays > 0 ? $totalPlanned / $sprintDays : 0;

        foreach ($period as $i => $day) {
            $labels[] = $day->format('M d');

            // Ideal: linear burn
            $ideal[] = round(max(0, $totalPlanned - ($dailyBurn * $i)), 2);

            // Actual remaining: effort of tasks NOT completed by end of this day
            $remaining = $hourTasks->filter(function ($task) use ($day) {
                return $task->completed_at === null
                    || $task->completed_at->startOfDay()->gt($day->endOfDay());
            })->sum('effort_estimate');

            $actual[] = round((float) $remaining, 2);
        }

        return [
            'labels'        => $labels,
            'ideal'         => $ideal,
            'actual'        => $actual,
            'total_planned' => $totalPlanned,
        ];
    }
}
