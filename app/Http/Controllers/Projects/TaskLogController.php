<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\TaskLogRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskLogController extends Controller
{
    public function store(TaskLogRequest $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('logHours', $task);

        $data = $request->validated();

        $task->logs()->create([
            'logged_by' => $request->user()->id,
            'hours'     => $data['hours'],
            'notes'     => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Hours logged.');
    }
}
