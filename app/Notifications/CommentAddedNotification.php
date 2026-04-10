<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Comment $comment,
        private readonly User $commenter,
        private readonly string $subjectTitle,
        private readonly string $subjectUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'comment_added',
            'commenter'     => $this->commenter->name,
            'subject_title' => $this->subjectTitle,
            'excerpt'       => \Str::limit($this->comment->body, 80),
            'url'           => $this->subjectUrl,
        ];
    }
}
