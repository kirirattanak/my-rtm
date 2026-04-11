<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\BusinessRequirement;
use Illuminate\Support\Facades\Auth;

class BusinessRequirementObserver
{
    public function created(BusinessRequirement $br): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => BusinessRequirement::class,
            'subject_id'   => $br->id,
            'user_id'      => Auth::id(),
            'action'       => 'created',
            'data'         => ['title' => $br->title],
        ]);
    }

    public function updated(BusinessRequirement $br): void
    {
        if (! Auth::id()) return;

        $changed = $br->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        ActivityLog::create([
            'subject_type' => BusinessRequirement::class,
            'subject_id'   => $br->id,
            'user_id'      => Auth::id(),
            'action'       => 'updated',
            'data'         => [
                'fields' => array_keys($changed),
                'old'    => array_intersect_key($br->getOriginal(), $changed),
                'new'    => $changed,
            ],
        ]);
    }

    public function deleted(BusinessRequirement $br): void
    {
        if (! Auth::id()) return;

        ActivityLog::create([
            'subject_type' => BusinessRequirement::class,
            'subject_id'   => $br->id,
            'user_id'      => Auth::id(),
            'action'       => 'deleted',
            'data'         => ['title' => $br->title],
        ]);
    }
}
