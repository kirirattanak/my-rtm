<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskObserver
{
    public function created(Task $task): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => Task::class,
            'subject_id'   => $task->id,
            'user_id'      => Auth::id(),
            'action'       => 'created',
            'data'         => ['title' => $task->title],
        ]);
    }

    public function updated(Task $task): void
    {
        if (! Auth::id()) return;

        $changed = $task->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) return;

        ActivityLog::create([
            'subject_type' => Task::class,
            'subject_id'   => $task->id,
            'user_id'      => Auth::id(),
            'action'       => 'updated',
            'data'         => [
                'title'  => $task->title,
                'fields' => array_keys($changed),
            ],
        ]);
    }

    public function deleted(Task $task): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => Task::class,
            'subject_id'   => $task->id,
            'user_id'      => Auth::id(),
            'action'       => 'deleted',
            'data'         => ['title' => $task->title],
        ]);
    }
}
