<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Notifications\CommentAddedNotification;
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

        // Notify the creator of the commented-on resource (if different from the commenter)
        if (
            isset($commentable->created_by) &&
            $commentable->created_by &&
            $commentable->created_by !== Auth::id()
        ) {
            $creator = $commentable->creator;
            if ($creator) {
                $subjectUrl = method_exists($commentable, 'getShowUrl')
                    ? $commentable->getShowUrl()
                    : url('/');

                $creator->notify(new CommentAddedNotification(
                    $comment,
                    Auth::user(),
                    $commentable->title,
                    $subjectUrl,
                ));
            }
        }
    }
}
