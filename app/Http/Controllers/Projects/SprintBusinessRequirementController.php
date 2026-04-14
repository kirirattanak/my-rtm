<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SprintBusinessRequirementController extends Controller
{
    public function store(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->authorize('update', $sprint);

        $data = $request->validate([
            'business_requirement_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('business_requirements', 'id')
                    ->where('project_id', $project->id),
            ],
        ]);

        $br = BusinessRequirement::findOrFail($data['business_requirement_id']);

        // Prevent adding the same BR to two active sprints of the same project
        $alreadyInSprint = $br->sprints()
            ->where('sprints.project_id', $project->id)
            ->where('sprints.id', '!=', $sprint->id)
            ->exists();

        if ($alreadyInSprint) {
            return back()->withErrors(['business_requirement_id' => 'This BR is already committed to another sprint in this project.']);
        }

        $sprint->businessRequirements()->syncWithoutDetaching([
            $br->id => ['added_by' => $request->user()->id],
        ]);

        return back()->with('success', "{$br->ref} added to sprint.");
    }

    public function destroy(Project $project, Sprint $sprint, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('update', $sprint);
        abort_unless($businessRequirement->project_id === $project->id, 403);

        $sprint->businessRequirements()->detach($businessRequirement->id);

        return back()->with('success', "{$businessRequirement->ref} removed from sprint.");
    }
}
