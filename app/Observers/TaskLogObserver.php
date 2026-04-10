<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Support\Facades\Auth;

class TaskLogObserver
{
    public function created(TaskLog $taskLog): void
    {
        if (! Auth::id()) {
            return;
        }

        ActivityLog::create([
            'subject_type' => Task::class,
            'subject_id'   => $taskLog->task_id,
            'user_id'      => Auth::id(),
            'action'       => 'logged_hours',
            'data'         => [
                'title' => $taskLog->task->title,
                'hours' => $taskLog->hours,
            ],
        ]);
    }
}
