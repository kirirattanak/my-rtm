<?php

namespace App\Http\Controllers\Projects;

use App\Enums\TestRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\TestRunRequest;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestRunController extends Controller
{
    public function store(TestRunRequest $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('logRun', $testCase);

        $data = $request->validated();

        $testCase->runs()->create([
            'executed_by' => $request->user()->id,
            'status'      => $data['status'],
            'notes'       => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Test run logged.');
    }
}
