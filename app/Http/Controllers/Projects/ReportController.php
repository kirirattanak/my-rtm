<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function health(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        // ── Business Requirements ──────────────────────────────────────────────
        $brStatusCounts = $project->businessRequirements()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $brTotal = array_sum($brStatusCounts);

        // ── Technical Requirements ─────────────────────────────────────────────
        $trStatusCounts = $project->technicalRequirements()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $trTotal = array_sum($trStatusCounts);

        // ── Test Cases ─────────────────────────────────────────────────────────
        $tcStatusCounts = $project->testCases()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $tcTotal = array_sum($tcStatusCounts);

        // Latest run result per test case (most recent run)
        $latestRunCounts = DB::table('test_runs')
            ->selectRaw('status, count(*) as count')
            ->whereIn('test_case_id', $project->testCases()->select('id'))
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('max(id)')
                    ->from('test_runs')
                    ->groupBy('test_case_id');
            })
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $notRunCount = $tcTotal - array_sum($latestRunCounts);

        // ── Tasks ──────────────────────────────────────────────────────────────
        $taskStatusCounts = $project->tasks()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $taskTotal = array_sum($taskStatusCounts);

        $overdueCount = $project->tasks()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        // ── Coverage (reuse existing logic) ───────────────────────────────────
        $trs = $project->technicalRequirements()
            ->withExists([
                'runs as has_passing_run' => fn ($q) => $q->where('status', 'pass'),
            ])
            ->get(['id']);

        $coveredTrCount = $trs->where('has_passing_run', true)->count();
        $trCoverage     = $trTotal > 0 ? round($coveredTrCount / $trTotal * 100) : 0;

        $coveredTrIds = $trs->where('has_passing_run', true)->pluck('id')->toArray();

        $brs        = $project->businessRequirements()->with('technicalRequirements:id')->get(['id']);
        $coveredBrs = $brs->filter(function ($br) use ($coveredTrIds) {
            $ids = $br->technicalRequirements->pluck('id');
            return $ids->isNotEmpty() && $ids->every(fn ($id) => in_array($id, $coveredTrIds));
        })->count();

        $brCoverage = $brTotal > 0 ? round($coveredBrs / $brTotal * 100) : 0;

        return Inertia::render('projects/Report', [
            'project' => $project->only('id', 'name'),
            'br'      => [
                'total'      => $brTotal,
                'by_status'  => $brStatusCounts,
                'coverage'   => $brCoverage,
                'covered'    => $coveredBrs,
            ],
            'tr'      => [
                'total'      => $trTotal,
                'by_status'  => $trStatusCounts,
                'coverage'   => $trCoverage,
                'covered'    => $coveredTrCount,
            ],
            'tc'      => [
                'total'      => $tcTotal,
                'by_status'  => $tcStatusCounts,
                'latest_runs'=> array_merge(['not_run' => $notRunCount], $latestRunCounts),
            ],
            'tasks'   => [
                'total'      => $taskTotal,
                'by_status'  => $taskStatusCounts,
                'overdue'    => $overdueCount,
            ],
        ]);
    }
}
