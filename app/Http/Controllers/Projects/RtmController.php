<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RtmController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $brs = $project->businessRequirements()
            ->with([
                'technicalRequirements.testCases.runs',
            ])
            ->orderBy('number')
            ->get();

        $matrix = $brs->map(function ($br) {
            $trs = $br->technicalRequirements->map(function ($tr) {
                $testCases = $tr->testCases->map(function ($tc) {
                    $latestRun = $tc->runs->first();
                    return [
                        'id'           => $tc->id,
                        'ref'          => $tc->ref,
                        'title'        => $tc->title,
                        'status'       => $tc->status->value,
                        'status_label' => $tc->status->label(),
                        'latest_run'   => $latestRun?->status?->value,
                        'is_passing'   => $tc->runs->contains(fn ($r) => $r->status->value === 'pass'),
                    ];
                });

                $isCovered = $testCases->contains(fn ($tc) => $tc['is_passing']);

                return [
                    'id'           => $tr->id,
                    'ref'          => $tr->ref,
                    'title'        => $tr->title,
                    'type'         => $tr->type->value,
                    'type_label'   => $tr->type->label(),
                    'status'       => $tr->status->value,
                    'status_label' => $tr->status->label(),
                    'is_covered'   => $isCovered,
                    'test_cases'   => $testCases,
                ];
            });

            $allTrsCovered = $trs->isNotEmpty() && $trs->every(fn ($tr) => $tr['is_covered']);

            return [
                'id'             => $br->id,
                'ref'            => $br->ref,
                'title'          => $br->title,
                'priority'       => $br->priority->value,
                'priority_label' => $br->priority->label(),
                'status'         => $br->status->value,
                'status_label'   => $br->status->label(),
                'is_covered'     => $allTrsCovered,
                'trs'            => $trs,
            ];
        });

        return Inertia::render('projects/Rtm', [
            'project' => $project->only('id', 'name'),
            'matrix'  => $matrix,
            'can'     => [
                'export' => $request->user()->can('update', $project),
            ],
        ]);
    }
}
