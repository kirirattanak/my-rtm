<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        if (! Auth::id()) {
            return;
        }

        $commentable = $comment->commentable;

        ActivityLog::create([
            'subject_type' => get_class($commentable),
            'subject_id'   => $commentable->id,
            'user_id'      => Auth::id(),
            'action'       => 'commented',
            'data'         => ['title' => $commentable->title],
        ]);
    }
}
