<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\LinkTrRequest;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrLinkController extends Controller
{
    /**
     * Attach a TR to a BR.
     */
    public function store(LinkTrRequest $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        $data = $request->validated();

        $tr = TechnicalRequirement::where('id', $data['technical_requirement_id'])
            ->where('project_id', $project->id)
            ->firstOrFail();

        $businessRequirement->technicalRequirements()->syncWithoutDetaching([$tr->id]);

        return back()->with('success', 'Technical requirement linked.');
    }

    /**
     * Detach a TR from a BR.
     */
    public function destroy(Project $project, BusinessRequirement $businessRequirement, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        $businessRequirement->technicalRequirements()->detach($technicalRequirement->id);

        return back()->with('success', 'Technical requirement unlinked.');
    }
}
