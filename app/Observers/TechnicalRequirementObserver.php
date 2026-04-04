<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\TechnicalRequirement;
use Illuminate\Support\Facades\Auth;

class TechnicalRequirementObserver
{
    public function created(TechnicalRequirement $tr): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => TechnicalRequirement::class,
            'subject_id'   => $tr->id,
            'user_id'      => Auth::id(),
            'action'       => 'created',
            'data'         => ['title' => $tr->title],
        ]);
    }

    public function updated(TechnicalRequirement $tr): void
    {
        if (! Auth::id()) return;

        $changed = $tr->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        ActivityLog::create([
            'subject_type' => TechnicalRequirement::class,
            'subject_id'   => $tr->id,
            'user_id'      => Auth::id(),
            'action'       => 'updated',
            'data'         => [
                'fields' => array_keys($changed),
                'old'    => array_intersect_key($tr->getOriginal(), $changed),
                'new'    => $changed,
            ],
        ]);
    }

    public function deleted(TechnicalRequirement $tr): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => TechnicalRequirement::class,
            'subject_id'   => $tr->id,
            'user_id'      => Auth::id(),
            'action'       => 'deleted',
            'data'         => ['title' => $tr->title],
        ]);
    }
}
