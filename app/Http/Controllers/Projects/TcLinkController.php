<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TcLinkController extends Controller
{
    public function store(Request $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('update', $testCase);

        $data = $request->validate([
            'technical_requirement_id' => 'required|integer|exists:technical_requirements,id',
        ]);

        $tr = TechnicalRequirement::where('id', $data['technical_requirement_id'])
            ->where('project_id', $project->id)
            ->firstOrFail();

        $testCase->technicalRequirements()->syncWithoutDetaching([$tr->id]);

        return back()->with('success', 'Technical requirement linked.');
    }

    public function destroy(Project $project, TestCase $testCase, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('update', $testCase);

        $testCase->technicalRequirements()->detach($technicalRequirement->id);

        return back()->with('success', 'Technical requirement unlinked.');
    }
}
