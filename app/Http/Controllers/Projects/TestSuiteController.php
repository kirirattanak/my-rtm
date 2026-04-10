<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TestSuite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestSuiteController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [TestSuite::class, $project]);

        $suites = $project->testSuites()
            ->with('creator:id,name')
            ->withCount('businessRequirements')
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'description' => $s->description,
                'br_count'    => $s->business_requirements_count,
                'creator'     => $s->creator->name,
                'created_at'  => $s->created_at->diffForHumans(),
            ]);

        return Inertia::render('projects/test-suites/Index', [
            'project' => $project->only('id', 'name'),
            'suites'  => $suites,
            'can'     => [
                'create' => $request->user()->can('create', [TestSuite::class, $project]),
            ],
        ]);
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', [TestSuite::class, $project]);

        $brs = $project->businessRequirements()
            ->orderBy('number')
            ->get()
            ->map(fn ($br) => [
                'id'    => $br->id,
                'ref'   => $br->ref,
                'title' => $br->title,
            ]);

        return Inertia::render('projects/test-suites/Create', [
            'project' => $project->only('id', 'name'),
            'brs'     => $brs,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [TestSuite::class, $project]);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'br_ids'      => 'required|array|min:1',
            'br_ids.*'    => 'integer|exists:business_requirements,id',
        ]);

        // Ensure all selected BRs belong to this project
        $validBrIds = $project->businessRequirements()
            ->whereIn('id', $data['br_ids'])
            ->pluck('id')
            ->toArray();

        $suite = $project->testSuites()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by'  => $request->user()->id,
        ]);

        $suite->businessRequirements()->sync($validBrIds);

        return redirect()
            ->route('projects.test-suites.show', [$project, $suite])
            ->with('success', 'Test suite created.');
    }

    public function show(Request $request, Project $project, TestSuite $testSuite): Response
    {
        $this->authorize('view', $testSuite);

        $testSuite->load([
            'creator:id,name',
            'businessRequirements' => fn ($q) => $q->orderBy('number')->with([
                'technicalRequirements' => fn ($q) => $q->orderBy('number')->with([
                    'testCases' => fn ($q) => $q->orderBy('number')->with([
                        'runs' => fn ($q) => $q->with('executor:id,name')->latest(),
                    ]),
                ]),
            ]),
        ]);

        $brs = $testSuite->businessRequirements->map(function ($br) {
            $trs = $br->technicalRequirements->map(function ($tr) {
                $tcs = $tr->testCases->map(function ($tc) {
                    $latestRun = $tc->runs->first();
                    return [
                        'id'         => $tc->id,
                        'ref'        => $tc->ref,
                        'title'      => $tc->title,
                        'latest_run' => $latestRun?->status?->value,
                        'runs'       => $tc->runs->map(fn ($r) => [
                            'id'         => $r->id,
                            'status'     => $r->status->value,
                            'status_label' => $r->status->label(),
                            'notes'      => $r->notes,
                            'executor'   => $r->executor->name,
                            'created_at' => $r->created_at->diffForHumans(),
                        ]),
                    ];
                });

                return [
                    'id'         => $tr->id,
                    'ref'        => $tr->ref,
                    'title'      => $tr->title,
                    'test_cases' => $tcs,
                ];
            });

            return [
                'id'   => $br->id,
                'ref'  => $br->ref,
                'title' => $br->title,
                'trs'  => $trs,
            ];
        });

        // Summary counts across all TCs in the suite
        $allRuns = collect();
        foreach ($testSuite->businessRequirements as $br) {
            foreach ($br->technicalRequirements as $tr) {
                foreach ($tr->testCases as $tc) {
                    $allRuns->push($tc->runs->first()?->status?->value ?? 'not_run');
                }
            }
        }

        $summary = [
            'total'   => $allRuns->count(),
            'pass'    => $allRuns->filter(fn ($s) => $s === 'pass')->count(),
            'fail'    => $allRuns->filter(fn ($s) => $s === 'fail')->count(),
            'blocked' => $allRuns->filter(fn ($s) => $s === 'blocked')->count(),
            'skipped' => $allRuns->filter(fn ($s) => $s === 'skipped')->count(),
            'not_run' => $allRuns->filter(fn ($s) => $s === 'not_run')->count(),
        ];

        return Inertia::render('projects/test-suites/Show', [
            'project' => $project->only('id', 'name'),
            'suite'   => [
                'id'          => $testSuite->id,
                'name'        => $testSuite->name,
                'description' => $testSuite->description,
                'creator'     => $testSuite->creator->name,
                'created_at'  => $testSuite->created_at->diffForHumans(),
                'brs'         => $brs,
                'summary'     => $summary,
            ],
            'can' => [
                'delete' => $request->user()->can('delete', $testSuite),
            ],
        ]);
    }

    public function destroy(Request $request, Project $project, TestSuite $testSuite): RedirectResponse
    {
        $this->authorize('delete', $testSuite);

        $testSuite->delete();

        return redirect()
            ->route('projects.test-suites.index', $project)
            ->with('success', 'Test suite deleted.');
    }
}
