<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoverageController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        // Load TRs with a boolean flag per test case indicating any passing run exists,
        // avoiding loading every run row just to check coverage.
        $trs = $project->technicalRequirements()
            ->with([
                'testCases' => fn ($q) => $q->withExists([
                    'runs as has_passing_run' => fn ($q) => $q->where('status', 'pass'),
                ]),
                'businessRequirements:id,number,title,priority,status',
            ])
            ->orderBy('number')
            ->get();

        $trRows = $trs->map(function ($tr) {
            $covered = $tr->testCases->contains(fn ($tc) => $tc->has_passing_run);

            return [
                'id'          => $tr->id,
                'ref'         => $tr->ref,
                'title'       => $tr->title,
                'status'      => $tr->status->value,
                'status_label' => $tr->status->label(),
                'tc_count'    => $tr->testCases->count(),
                'covered'     => $covered,
                'brs'         => $tr->businessRequirements->map(fn ($br) => [
                    'id'  => $br->id,
                    'ref' => $br->ref,
                ]),
            ];
        });

        $coveredTrCount = $trRows->where('covered', true)->count();
        $totalTrCount   = $trRows->count();
        $trCoverage     = $totalTrCount > 0 ? round($coveredTrCount / $totalTrCount * 100) : 0;

        // BR coverage: all linked TRs must be covered
        $brs = $project->businessRequirements()
            ->with('technicalRequirements:id')
            ->orderBy('number')
            ->get();

        $coveredTrIds = $trRows->where('covered', true)->pluck('id')->toArray();

        $brRows = $brs->map(function ($br) use ($coveredTrIds) {
            $trIds   = $br->technicalRequirements->pluck('id');
            $covered = $trIds->isNotEmpty() && $trIds->every(fn ($id) => in_array($id, $coveredTrIds));

            return [
                'id'          => $br->id,
                'ref'         => $br->ref,
                'title'       => $br->title,
                'tr_count'    => $trIds->count(),
                'covered'     => $covered,
                'status'      => $br->status->value,
                'status_label' => $br->status->label(),
            ];
        });

        $coveredBrCount = $brRows->where('covered', true)->count();
        $totalBrCount   = $brRows->count();
        $brCoverage     = $totalBrCount > 0 ? round($coveredBrCount / $totalBrCount * 100) : 0;

        return Inertia::render('projects/Coverage', [
            'project'   => $project->only('id', 'name'),
            'summary'   => [
                'tr_coverage'      => $trCoverage,
                'covered_trs'      => $coveredTrCount,
                'total_trs'        => $totalTrCount,
                'br_coverage'      => $brCoverage,
                'covered_brs'      => $coveredBrCount,
                'total_brs'        => $totalBrCount,
            ],
            'trs' => $trRows,
            'brs' => $brRows,
        ]);
    }
}
