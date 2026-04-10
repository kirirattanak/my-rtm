<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function export(Request $request, Project $project): StreamedResponse
    {
        $this->authorize('update', $project);

        $brs = $project->businessRequirements()
            ->with(['technicalRequirements.testCases.runs' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('number')
            ->get();

        $filename = 'rtm-' . \Str::slug($project->name) . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($brs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['BR Ref', 'BR Title', 'BR Status', 'TR Ref', 'TR Title', 'TR Type', 'TR Status', 'TC Ref', 'TC Title', 'TC Status', 'Latest Run']);

            foreach ($brs as $br) {
                if ($br->technicalRequirements->isEmpty()) {
                    fputcsv($handle, [$br->ref, $br->title, $br->status->value, '', '', '', '', '', '', '', '']);
                    continue;
                }

                foreach ($br->technicalRequirements as $tr) {
                    if ($tr->testCases->isEmpty()) {
                        fputcsv($handle, [$br->ref, $br->title, $br->status->value, $tr->ref, $tr->title, $tr->type->value, $tr->status->value, '', '', '', '']);
                        continue;
                    }

                    foreach ($tr->testCases as $tc) {
                        $latestRun = $tc->runs->first()?->status?->value ?? '';
                        fputcsv($handle, [
                            $br->ref, $br->title, $br->status->value,
                            $tr->ref, $tr->title, $tr->type->value, $tr->status->value,
                            $tc->ref, $tc->title, $tc->status->value, $latestRun,
                        ]);
                    }
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
