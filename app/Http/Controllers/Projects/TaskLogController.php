<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskLogController extends Controller
{
    public function store(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('logHours', $task);

        $data = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:24',
            'notes' => 'nullable|string|max:500',
        ]);

        $task->logs()->create([
            'logged_by' => $request->user()->id,
            'hours'     => $data['hours'],
            'notes'     => $data['notes'] ?? null,
        ]);

        ActivityLog::create([
            'subject_type' => Task::class,
            'subject_id'   => $task->id,
            'user_id'      => $request->user()->id,
            'action'       => 'logged_hours',
            'data'         => ['title' => $task->title, 'hours' => $data['hours']],
        ]);

        return back()->with('success', 'Hours logged.');
    }
}
