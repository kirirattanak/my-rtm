<?php

namespace App\Http\Controllers\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Http\Controllers\Controller;
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

        $brs = $project->businessRequirements()
            ->with('creator:id,name')
            ->orderBy('number')
            ->get()
            ->map(fn ($br) => [
                'id'             => $br->id,
                'ref'            => $br->ref,
                'number'         => $br->number,
                'title'          => $br->title,
                'priority'       => $br->priority->value,
                'priority_label' => $br->priority->label(),
                'priority_color' => $br->priority->color(),
                'status'         => $br->status->value,
                'status_label'   => $br->status->label(),
                'status_color'   => $br->status->color(),
                'category'       => $br->category,
                'creator'        => $br->creator,
                'tr_count'       => $br->technicalRequirements()->count(),
                'created_at'     => $br->created_at,
            ]);

        return Inertia::render('projects/requirements/BrIndex', [
            'project' => $project->only('id', 'name'),
            'brs'     => $brs,
            'can'     => [
                'create' => $request->user()->can('create', [BusinessRequirement::class, $project]),
            ],
        ]);
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

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [BusinessRequirement::class, $project]);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'required|in:' . implode(',', BrPriority::values()),
            'status'      => 'required|in:' . implode(',', RequirementStatus::values()),
            'category'    => 'nullable|string|max:100',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
        ]);

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
        ]);

        $linkedTrIds = $br->technicalRequirements->pluck('id')->toArray();

        $linkableTrs = $project->technicalRequirements()
            ->whereNotIn('id', $linkedTrIds)
            ->orderBy('number')
            ->get()
            ->map(fn ($tr) => [
                'id'    => $tr->id,
                'ref'   => $tr->ref,
                'title' => $tr->title,
            ]);

        return Inertia::render('projects/requirements/BrShow', [
            'project'     => $project->only('id', 'name'),
            'linkable_trs' => $linkableTrs,
            'br'          => [
                'id'             => $br->id,
                'ref'            => $br->ref,
                'number'         => $br->number,
                'title'          => $br->title,
                'description'    => $br->description,
                'priority'       => $br->priority->value,
                'priority_label' => $br->priority->label(),
                'priority_color' => $br->priority->color(),
                'status'         => $br->status->value,
                'status_label'   => $br->status->label(),
                'status_color'   => $br->status->color(),
                'category'       => $br->category,
                'tags'           => $br->tags ?? [],
                'creator'        => $br->creator,
                'created_at'     => $br->created_at,
                'updated_at'     => $br->updated_at,
                'technical_requirements' => $br->technicalRequirements->map(fn ($tr) => [
                    'id'          => $tr->id,
                    'ref'         => $tr->ref,
                    'title'       => $tr->title,
                    'status'      => $tr->status->value,
                    'status_label' => $tr->status->label(),
                    'status_color' => $tr->status->color(),
                    'type'        => $tr->type->value,
                    'type_label'  => $tr->type->label(),
                ]),
                'comments' => $br->comments->map(fn ($c) => [
                    'id'         => $c->id,
                    'body'       => $c->body,
                    'user'       => $c->user,
                    'created_at' => $c->created_at,
                ]),
            ],
            'can' => [
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
                'id'          => $businessRequirement->id,
                'ref'         => $businessRequirement->ref,
                'title'       => $businessRequirement->title,
                'description' => $businessRequirement->description,
                'priority'    => $businessRequirement->priority->value,
                'status'      => $businessRequirement->status->value,
                'category'    => $businessRequirement->category,
                'tags'        => $businessRequirement->tags ?? [],
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

    public function update(Request $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'required|in:' . implode(',', BrPriority::values()),
            'status'      => 'required|in:' . implode(',', RequirementStatus::values()),
            'category'    => 'nullable|string|max:100',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
        ]);

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
}
