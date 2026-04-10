<?php

namespace App\Http\Controllers\Projects;

use App\Enums\RequirementStatus;
use App\Enums\TrType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ImportFileRequest;
use App\Http\Requests\Projects\TechnicalRequirementRequest;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicalRequirementController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('viewAny', [TechnicalRequirement::class, $project]);

        $trs = $project->technicalRequirements()
            ->with('creator:id,name')
            ->withCount('businessRequirements')
            ->orderBy('number')
            ->paginate(25)
            ->through(fn ($tr) => [
                'id'           => $tr->id,
                'ref'          => $tr->ref,
                'number'       => $tr->number,
                'title'        => $tr->title,
                'type'         => $tr->type->value,
                'type_label'   => $tr->type->label(),
                'status'       => $tr->status->value,
                'status_label' => $tr->status->label(),
                'status_color' => $tr->status->color(),
                'creator'      => $tr->creator,
                'br_count'     => $tr->business_requirements_count,
                'created_at'   => $tr->created_at,
            ]);

        return Inertia::render('projects/requirements/TrIndex', [
            'project' => $project->only('id', 'name'),
            'trs'     => $trs,
            'can'     => [
                'create' => $request->user()->can('create', [TechnicalRequirement::class, $project]),
            ],
        ]);
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', [TechnicalRequirement::class, $project]);

        return Inertia::render('projects/requirements/TrCreate', [
            'project'  => $project->only('id', 'name'),
            'types'    => collect(TrType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'statuses' => collect(RequirementStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function store(TechnicalRequirementRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [TechnicalRequirement::class, $project]);

        $data = $request->validated();

        $number = ($project->technicalRequirements()->max('number') ?? 0) + 1;

        $project->technicalRequirements()->create([
            ...$data,
            'number'     => $number,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('projects.requirements.technical.index', $project)
            ->with('success', 'Technical requirement created.');
    }

    public function show(Request $request, Project $project, TechnicalRequirement $technicalRequirement): Response
    {
        $this->authorize('view', $technicalRequirement);

        $tr = $technicalRequirement->load([
            'creator:id,name',
            'businessRequirements:id,number,title,status,priority',
            'testCases.runs',
            'testCases.assignee:id,name',
            'comments.user:id,name',
        ]);

        return Inertia::render('projects/requirements/TrShow', [
            'project' => $project->only('id', 'name'),
            'tr'      => [
                'id'           => $tr->id,
                'ref'          => $tr->ref,
                'number'       => $tr->number,
                'title'        => $tr->title,
                'description'  => $tr->description,
                'type'         => $tr->type->value,
                'type_label'   => $tr->type->label(),
                'status'       => $tr->status->value,
                'status_label' => $tr->status->label(),
                'status_color' => $tr->status->color(),
                'creator'      => $tr->creator,
                'created_at'   => $tr->created_at,
                'updated_at'   => $tr->updated_at,
                'business_requirements' => $tr->businessRequirements->map(fn ($br) => [
                    'id'             => $br->id,
                    'ref'            => $br->ref,
                    'title'          => $br->title,
                    'status'         => $br->status->value,
                    'status_label'   => $br->status->label(),
                    'status_color'   => $br->status->color(),
                    'priority'       => $br->priority->value,
                    'priority_label' => $br->priority->label(),
                    'priority_color' => $br->priority->color(),
                ]),
                'test_cases' => $tr->testCases->map(fn ($tc) => [
                    'id'             => $tc->id,
                    'ref'            => $tc->ref,
                    'title'          => $tc->title,
                    'type'           => $tc->type->value,
                    'type_label'     => $tc->type->label(),
                    'priority'       => $tc->priority->value,
                    'priority_label' => $tc->priority->label(),
                    'status'         => $tc->status->value,
                    'status_label'   => $tc->status->label(),
                    'status_color'   => $tc->status->color(),
                    'assignee'       => $tc->assignee,
                    'latest_run'     => $tc->runs->first()?->status?->value,
                ]),
                'comments' => $tr->comments->map(fn ($c) => [
                    'id'         => $c->id,
                    'body'       => $c->body,
                    'user'       => $c->user,
                    'created_at' => $c->created_at,
                ]),
            ],
            'can' => [
                'edit'   => $request->user()->can('update', $tr),
                'delete' => $request->user()->can('delete', $tr),
            ],
        ]);
    }

    public function edit(Project $project, TechnicalRequirement $technicalRequirement): Response
    {
        $this->authorize('update', $technicalRequirement);

        return Inertia::render('projects/requirements/TrEdit', [
            'project' => $project->only('id', 'name'),
            'tr'      => [
                'id'          => $technicalRequirement->id,
                'ref'         => $technicalRequirement->ref,
                'title'       => $technicalRequirement->title,
                'description' => $technicalRequirement->description,
                'type'        => $technicalRequirement->type->value,
                'status'      => $technicalRequirement->status->value,
            ],
            'types'    => collect(TrType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'statuses' => collect(RequirementStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function update(TechnicalRequirementRequest $request, Project $project, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('update', $technicalRequirement);

        $data = $request->validated();

        $technicalRequirement->update($data);

        return redirect()->route('projects.requirements.technical.show', [$project, $technicalRequirement])
            ->with('success', 'Technical requirement updated.');
    }

    public function destroy(Project $project, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('delete', $technicalRequirement);

        $technicalRequirement->delete();

        return redirect()->route('projects.requirements.technical.index', $project)
            ->with('success', 'Technical requirement deleted.');
    }

    public function importCreate(Project $project): Response
    {
        $this->authorize('create', [TechnicalRequirement::class, $project]);

        return Inertia::render('projects/requirements/TrImport', [
            'project' => $project->only('id', 'name'),
            'result'  => session('import_result'),
        ]);
    }

    public function import(ImportFileRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [TechnicalRequirement::class, $project]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle));

        $validTypes    = TrType::values();
        $validStatuses = RequirementStatus::values();

        $imported   = 0;
        $skipped    = [];
        $rowNum     = 1;
        $nextNumber = ($project->technicalRequirements()->max('number') ?? 0) + 1;

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

            $project->technicalRequirements()->create([
                'number'      => $nextNumber++,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null ?: null,
                'type'        => in_array($data['type'] ?? '', $validTypes)      ? $data['type']   : 'functional',
                'status'      => in_array($data['status'] ?? '', $validStatuses) ? $data['status'] : 'draft',
                'created_by'  => $request->user()->id,
            ]);
            $imported++;
        }

        fclose($handle);

        return redirect()->route('projects.requirements.technical.import', $project)
            ->with('import_result', ['imported' => $imported, 'skipped' => $skipped]);
    }
}
