<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\TestCase;
use Illuminate\Support\Facades\Auth;

class TestCaseObserver
{
    public function created(TestCase $tc): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => TestCase::class,
            'subject_id'   => $tc->id,
            'user_id'      => Auth::id(),
            'action'       => 'created',
            'data'         => ['title' => $tc->title],
        ]);
    }

    public function updated(TestCase $tc): void
    {
        if (! Auth::id()) return;

        $changed = $tc->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) return;

        ActivityLog::create([
            'subject_type' => TestCase::class,
            'subject_id'   => $tc->id,
            'user_id'      => Auth::id(),
            'action'       => 'updated',
            'data'         => [
                'fields' => array_keys($changed),
                'old'    => array_intersect_key($tc->getOriginal(), $changed),
                'new'    => $changed,
            ],
        ]);
    }

    public function deleted(TestCase $tc): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => TestCase::class,
            'subject_id'   => $tc->id,
            'user_id'      => Auth::id(),
            'action'       => 'deleted',
            'data'         => ['title' => $tc->title],
        ]);
    }
}
