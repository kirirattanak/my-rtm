<?php

namespace App\Http\Controllers\Projects;

use App\Enums\TestRunStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestRunController extends Controller
{
    public function store(Request $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('logRun', $testCase);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', TestRunStatus::values()),
            'notes'  => 'nullable|string|max:1000',
        ]);

        $testCase->runs()->create([
            'executed_by' => $request->user()->id,
            'status'      => $data['status'],
            'notes'       => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Test run logged.');
    }
}
