<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\BusinessRequirement;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BrDependencyController extends Controller
{
    /**
     * Add a blocker to the given BR.
     * POST payload: { blocking_br_id: number }
     * Semantics: blocking_br_id must be resolved before $br can proceed.
     */
    public function store(Request $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        $data = $request->validate([
            'blocking_br_id' => [
                'required',
                'integer',
                Rule::exists('business_requirements', 'id')->where('project_id', $project->id),
            ],
        ]);

        $blockingId = (int) $data['blocking_br_id'];
        $blockedId  = $businessRequirement->id;

        if ($blockingId === $blockedId) {
            return back()->withErrors(['blocking_br_id' => 'A BR cannot depend on itself.']);
        }

        if ($this->wouldCreateCycle($blockingId, $blockedId)) {
            return back()->withErrors(['blocking_br_id' => 'This link would create a circular dependency.']);
        }

        DB::table('br_dependencies')->insertOrIgnore([
            'blocking_br_id' => $blockingId,
            'blocked_br_id'  => $blockedId,
            'created_by'     => $request->user()->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return back()->with('success', 'Dependency added.');
    }

    /**
     * Remove a blocker from the given BR.
     */
    public function destroy(Project $project, BusinessRequirement $businessRequirement, BusinessRequirement $blockingBr): RedirectResponse
    {
        $this->authorize('update', $businessRequirement);

        DB::table('br_dependencies')
            ->where('blocking_br_id', $blockingBr->id)
            ->where('blocked_br_id', $businessRequirement->id)
            ->delete();

        return back()->with('success', 'Dependency removed.');
    }

    /**
     * BFS from $newBlockedId following outgoing blocking edges.
     * If $newBlockingId is reachable, adding the link would create a cycle.
     */
    private function wouldCreateCycle(int $newBlockingId, int $newBlockedId): bool
    {
        $visited = [];
        $queue   = [$newBlockedId];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if ($current === $newBlockingId) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            $next = DB::table('br_dependencies')
                ->where('blocking_br_id', $current)
                ->pluck('blocked_br_id')
                ->toArray();

            foreach ($next as $id) {
                if (!isset($visited[$id])) {
                    $queue[] = $id;
                }
            }
        }

        return false;
    }
}
