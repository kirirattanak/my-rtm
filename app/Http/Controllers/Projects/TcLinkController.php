<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\LinkTrRequest;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;

class TcLinkController extends Controller
{
    public function store(LinkTrRequest $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('update', $testCase);

        $data = $request->validated();

        $testCase->technicalRequirements()->syncWithoutDetaching([$data['technical_requirement_id']]);

        return back()->with('success', 'Technical requirement linked.');
    }

    public function destroy(Project $project, TestCase $testCase, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('update', $testCase);

        $testCase->technicalRequirements()->detach($technicalRequirement->id);

        return back()->with('success', 'Technical requirement unlinked.');
    }
}
