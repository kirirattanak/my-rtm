<?php

namespace App\Http\Controllers\Projects;

use App\Enums\EffortUnit;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\SprintRequest;
use App\Http\Resources\SprintResource;
use App\Http\Resources\TaskResource;
use App\Models\BusinessRequirement;
use App\Models\MemberMonthlyCapacity;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintVelocity;
use App\Models\Task;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $sprint->load([
            'tasks' => fn ($q) => $q->with(['assignee:id,name', 'logs']),
            'businessRequirements',
        ]);

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

        $members = $project->projectMembers()
            ->with('user:id,name')
            ->get()
            ->map(fn ($m) => ['value' => $m->user_id, 'label' => $m->user->name]);

        // PERT planning data
        $committedBrs    = $sprint->businessRequirements;
        $pertExpected    = $sprint->pertExpectedTotal();
        $pertStdDev      = $sprint->pertStdDev();
        $availableHours  = $sprint->availableHours();
        [$lo68, $hi68]   = $sprint->confidenceRange(1);
        [$lo95, $hi95]   = $sprint->confidenceRange(2);
        [$lo99, $hi99]   = $sprint->confidenceRange(3);

        // All project BRs not committed to ANY sprint in this project (or already in this sprint)
        $committedIds = $committedBrs->pluck('id');
        $allProjectBrIds = $project->businessRequirements()->pluck('id');
        $otherSprintBrIds = \DB::table('sprint_business_requirements')
            ->join('sprints', 'sprints.id', '=', 'sprint_business_requirements.sprint_id')
            ->where('sprints.project_id', $project->id)
            ->where('sprints.id', '!=', $sprint->id)
            ->pluck('business_requirement_id');

        $availableBrs = $project->businessRequirements()
            ->whereNotIn('id', $otherSprintBrIds)
            ->orderBy('number')
            ->get()
            ->map(fn (BusinessRequirement $br) => [
                'id'           => $br->id,
                'ref'          => $br->ref,
                'title'        => $br->title,
                'priority'     => $br->priority->value,
                'priority_label' => $br->priority->label(),
                'status'       => $br->status->value,
                'status_label' => $br->status->label(),
                'is_blocked'   => false,
                'has_pert'     => $br->hasPertEstimate(),
                'pert_expected' => $br->pertExpected(),
                'committed'    => $committedIds->contains($br->id),
            ]);

        // Member capacity for the sprint's months
        $capacityRecords = MemberMonthlyCapacity::where('project_id', $project->id)
            ->where(function ($q) use ($sprint) {
                $cursor = $sprint->start_date->copy()->startOfMonth();
                while ($cursor->lte($sprint->end_date)) {
                    $q->orWhere(fn ($inner) => $inner
                        ->where('year', $cursor->year)
                        ->where('month', $cursor->month));
                    $cursor->addMonth();
                }
            })
            ->with('user:id,name')
            ->get()
            ->map(fn ($r) => [
                'user_id'         => $r->user_id,
                'user_name'       => $r->user->name,
                'year'            => $r->year,
                'month'           => $r->month,
                'available_hours' => (float) $r->available_hours,
                'focus_factor'    => (float) $r->focus_factor,
                'effective_hours' => $r->effectiveHours(),
                'notes'           => $r->notes,
            ]);

        return Inertia::render('projects/sprints/Show', [
            'project'  => $project->only('id', 'name'),
            'sprint'   => [
                'id'         => $sprint->id,
                'name'       => $sprint->name,
                'start_date' => $sprint->start_date->toDateString(),
                'end_date'   => $sprint->end_date->toDateString(),
                'capacity'   => $sprint->capacity,
                'is_active'  => $sprint->isActive(),
                'is_closed'  => (bool) $sprint->velocity()->exists(),
                'tasks'      => $sprint->tasks->map(fn ($t) => TaskResource::sprintItem($t)),
            ],
            'members'         => $members,
            'workload'        => $workload,
            'burndown'        => $burndown,
            'planning'        => [
                'available_brs'   => $availableBrs,
                'pert_expected'   => round($pertExpected, 2),
                'pert_std_dev'    => round($pertStdDev, 2),
                'available_hours' => $availableHours,
                'buffer'          => round($availableHours - $pertExpected, 2),
                'confidence'      => [
                    '68' => ['low' => round($lo68, 2), 'high' => round($hi68, 2)],
                    '95' => ['low' => round($lo95, 2), 'high' => round($hi95, 2)],
                    '99' => ['low' => round($lo99, 2), 'high' => round($hi99, 2)],
                ],
                'unestimated_count' => $committedBrs->filter(fn ($br) => ! $br->hasPertEstimate())->count(),
            ],
            'capacity_records' => $capacityRecords,
            'can'        => [
                'edit'          => $request->user()->can('update', $sprint),
                'delete'        => $request->user()->can('delete', $sprint),
                'create_task'   => $request->user()->can('create', [Task::class, $project]),
                'change_status' => $request->user()->hasPermission('tasks.change_status', $project),
                'manage_capacity' => $request->user()->hasPermission('capacity.manage', $project),
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

    public function close(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorize('update', $sprint);

        if ($sprint->velocity()->exists()) {
            return back()->withErrors(['sprint' => 'Sprint is already closed.']);
        }

        $sprint->load('businessRequirements', 'tasks.logs');

        $committedBrs      = $sprint->businessRequirements;
        $pertExpected      = $sprint->pertExpectedTotal();
        $pertStdDev        = $sprint->pertStdDev();
        $availableHours    = $sprint->availableHours();
        $actualHoursLogged = $sprint->tasks->sum(fn ($t) => $t->effectiveActualHours() ?? 0);

        $brCountCompleted = $committedBrs->filter(
            fn (BusinessRequirement $br) => $br->status->value === 'implemented'
        )->count();

        SprintVelocity::create([
            'sprint_id'           => $sprint->id,
            'pert_expected_hours' => round($pertExpected, 2),
            'pert_std_dev'        => round($pertStdDev, 2),
            'available_hours'     => $availableHours,
            'actual_hours_logged' => round($actualHoursLogged, 2),
            'br_count_committed'  => $committedBrs->count(),
            'br_count_completed'  => $brCountCompleted,
        ]);

        return back()->with('success', 'Sprint closed and velocity recorded.');
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
