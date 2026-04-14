<?php

namespace App\Http\Controllers\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\BusinessRequirementRequest;
use App\Http\Requests\Projects\ImportFileRequest;
use App\Http\Requests\Projects\RequirementStatusRequest;
use App\Http\Resources\BusinessRequirementResource;
use App\Models\BusinessRequirement;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessRequirementController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [BusinessRequirement::class, $project]);

        $isBoard = $request->input('view') === 'board';

        $statusCounts = $project->businessRequirements()->statusCounts()->toArray();

        $canCreate = $request->user()->can('create', [BusinessRequirement::class, $project]);

        if ($isBoard) {
            $boardBrs = $project->businessRequirements()
                ->with('creator:id,name')
                ->withCount('technicalRequirements')
                ->withCount('blockedByBrs as blocking_count')
                ->withExists(['blockedByBrs as is_blocked' => fn ($q) => $q->where('status', '!=', 'implemented')])
                ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
                ->get()
                ->map(fn ($br) => BusinessRequirementResource::list($br));

            return Inertia::render('projects/requirements/BrIndex', [
                'project'      => $project->only('id', 'name'),
                'brs'          => null,
                'boardBrs'     => $boardBrs,
                'statusCounts' => $statusCounts,
                'can'          => ['create' => $canCreate],
            ]);
        }

        $brs = $project->businessRequirements()
            ->with('creator:id,name')
            ->withCount('technicalRequirements')
            ->withCount('blockedByBrs as blocking_count')
            ->withExists(['blockedByBrs as is_blocked' => fn ($q) => $q->where('status', '!=', 'implemented')])
            ->orderBy('number')
            ->paginate(25)
            ->through(fn ($br) => BusinessRequirementResource::list($br));

        return Inertia::render('projects/requirements/BrIndex', [
            'project'      => $project->only('id', 'name'),
            'brs'          => $brs,
            'boardBrs'     => null,
            'statusCounts' => $statusCounts,
            'can'          => ['create' => $canCreate],
        ]);
    }

    public function updateStatus(RequirementStatusRequest $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        $businessRequirement->update(['status' => $request->validated()['status']]);

        return back();
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', [BusinessRequirement::class, $project]);

        return Inertia::render('projects/requirements/BrCreate', [
            'project'   => $project->only('id', 'name'),
            'priorities' => collect(BrPriority::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'statuses'  => collect(RequirementStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function store(BusinessRequirementRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [BusinessRequirement::class, $project]);

        $data = $request->validated();

        $number = ($project->businessRequirements()->max('number') ?? 0) + 1;

        $project->businessRequirements()->create([
            ...$data,
            'number'     => $number,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('projects.requirements.business.index', $project)
            ->with('success', 'Business requirement created.');
    }

    public function show(Request $request, Project $project, BusinessRequirement $businessRequirement): Response
    {
        $this->authorize('view', $businessRequirement);

        $br = $businessRequirement->load([
            'creator:id,name',
            'technicalRequirements:id,number,title,status,type',
            'comments.user:id,name',
            'blockedByBrs:id,number,title,status',
            'blockingBrs:id,number,title,status',
        ]);

        $linkedTrIds     = $br->technicalRequirements->pluck('id')->toArray();
        $blockerIds      = $br->blockedByBrs->pluck('id')->toArray();

        $linkableTrs = $project->technicalRequirements()
            ->whereNotIn('id', $linkedTrIds)
            ->orderBy('number')
            ->get()
            ->map(fn ($tr) => ['id' => $tr->id, 'ref' => $tr->ref, 'title' => $tr->title]);

        $linkableBrs = $project->businessRequirements()
            ->where('id', '!=', $br->id)
            ->whereNotIn('id', $blockerIds)
            ->orderBy('number')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'ref' => $b->ref, 'title' => $b->title]);

        return Inertia::render('projects/requirements/BrShow', [
            'project'      => $project->only('id', 'name'),
            'linkable_trs' => $linkableTrs,
            'linkable_brs' => $linkableBrs,
            'br'           => BusinessRequirementResource::detail($br),
            'can'          => [
                'edit'   => $request->user()->can('update', $br),
                'delete' => $request->user()->can('delete', $br),
            ],
        ]);
    }

    public function edit(Project $project, BusinessRequirement $businessRequirement): Response
    {
        $this->authorize('update', $businessRequirement);

        return Inertia::render('projects/requirements/BrEdit', [
            'project' => $project->only('id', 'name'),
            'br'      => [
                'id'                => $businessRequirement->id,
                'ref'               => $businessRequirement->ref,
                'title'             => $businessRequirement->title,
                'description'       => $businessRequirement->description,
                'priority'          => $businessRequirement->priority->value,
                'status'            => $businessRequirement->status->value,
                'category'          => $businessRequirement->category,
                'tags'              => $businessRequirement->tags ?? [],
                'optimistic_hours'  => $businessRequirement->optimistic_hours,
                'most_likely_hours' => $businessRequirement->most_likely_hours,
                'pessimistic_hours' => $businessRequirement->pessimistic_hours,
            ],
            'priorities' => collect(BrPriority::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'statuses'  => collect(RequirementStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function update(BusinessRequirementRequest $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        $data = $request->validated();

        $businessRequirement->update($data);

        return redirect()->route('projects.requirements.business.show', [$project, $businessRequirement])
            ->with('success', 'Business requirement updated.');
    }

    public function destroy(Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('delete', $businessRequirement);

        $businessRequirement->delete();

        return redirect()->route('projects.requirements.business.index', $project)
            ->with('success', 'Business requirement deleted.');
    }

    public function graph(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [BusinessRequirement::class, $project]);

        $brs = $project->businessRequirements()
            ->get(['id', 'number', 'title', 'status', 'priority']);

        $edges = \Illuminate\Support\Facades\DB::table('br_dependencies')
            ->whereIn('blocking_br_id', $brs->pluck('id'))
            ->orWhereIn('blocked_br_id', $brs->pluck('id'))
            ->get(['blocking_br_id', 'blocked_br_id']);

        return Inertia::render('projects/requirements/BrGraph', [
            'project' => $project->only('id', 'name'),
            'nodes'   => $brs->map(fn ($br) => [
                'id'       => $br->id,
                'ref'      => $br->ref,
                'title'    => $br->title,
                'status'   => $br->status->value,
                'priority' => $br->priority->value,
            ]),
            'edges'   => $edges->map(fn ($e) => [
                'from' => $e->blocking_br_id,
                'to'   => $e->blocked_br_id,
            ]),
        ]);
    }

    public function importCreate(Project $project): Response
    {
        $this->authorize('create', [BusinessRequirement::class, $project]);

        return Inertia::render('projects/requirements/BrImport', [
            'project' => $project->only('id', 'name'),
            'result'  => session('import_result'),
        ]);
    }

    public function import(ImportFileRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [BusinessRequirement::class, $project]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        $rawHeader = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h)), $rawHeader);

        $validPriorities = BrPriority::values();
        $validStatuses   = RequirementStatus::values();

        $imported   = 0;
        $skipped    = [];
        $rowNum     = 1;
        $nextNumber = ($project->businessRequirements()->max('number') ?? 0) + 1;

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

            $project->businessRequirements()->create([
                'number'      => $nextNumber++,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null ?: null,
                'priority'    => in_array($data['priority'] ?? '', $validPriorities) ? $data['priority'] : 'medium',
                'status'      => in_array($data['status'] ?? '', $validStatuses)   ? $data['status']   : 'draft',
                'category'    => $data['category'] ?? null ?: null,
                'created_by'  => $request->user()->id,
            ]);
            $imported++;
        }

        fclose($handle);

        return redirect()->route('projects.requirements.business.import', $project)
            ->with('import_result', ['imported' => $imported, 'skipped' => $skipped]);
    }
}
