<?php

namespace App\Http\Controllers\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TestCaseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ImportFileRequest;
use App\Http\Requests\Projects\TestCaseRequest;
use App\Http\Resources\TestCaseResource;
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
            ->through(fn ($tc) => TestCaseResource::list($tc));

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

    public function store(TestCaseRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [TestCase::class, $project]);

        $data = $request->validated();

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
            'technicalRequirements:id,number,title,type,status',
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
            'tc'           => TestCaseResource::detail($tc),
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

    public function update(TestCaseRequest $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('update', $testCase);

        $data = $request->validated();

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

    public function importCreate(Project $project): Response
    {
        $this->authorize('create', [TestCase::class, $project]);

        return Inertia::render('projects/test-cases/TcImport', [
            'project' => $project->only('id', 'name'),
            'result'  => session('import_result'),
        ]);
    }

    public function import(ImportFileRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [TestCase::class, $project]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle));

        $validTypes      = TestCaseType::values();
        $validPriorities = BrPriority::values();
        $validStatuses   = RequirementStatus::values();

        $imported   = 0;
        $skipped    = [];
        $rowNum     = 1;
        $nextNumber = ($project->testCases()->max('number') ?? 0) + 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) < count($header)) {
                $skipped[] = ['row' => $rowNum, 'reason' => 'Too few columns'];
                continue;
            }
            $data = array_combine($header, array_map('trim', array_slice($row, 0, count($header))));

            if (empty($data['title'] ?? '')) {
                $skipped[] = ['row' => $rowNum, 'reason' => 'Missing title'];
                continue;
            }

            $project->testCases()->create([
                'number'          => $nextNumber++,
                'title'           => $data['title'],
                'description'     => $data['description'] ?? null ?: null,
                'expected_result' => $data['expected_result'] ?? null ?: null,
                'steps'           => [],
                'type'            => in_array($data['type'] ?? '', $validTypes)          ? $data['type']     : 'manual',
                'priority'        => in_array($data['priority'] ?? '', $validPriorities) ? $data['priority'] : 'medium',
                'status'          => in_array($data['status'] ?? '', $validStatuses)     ? $data['status']   : 'draft',
                'created_by'      => $request->user()->id,
            ]);
            $imported++;
        }

        fclose($handle);

        return redirect()->route('projects.test-cases.import', $project)
            ->with('import_result', ['imported' => $imported, 'skipped' => $skipped]);
    }
}
