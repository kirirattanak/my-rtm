<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $assigner,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'task_assigned',
            'task_id'      => $this->task->id,
            'task_title'   => $this->task->title,
            'project_id'   => $this->task->project_id,
            'project_name' => $this->task->project->name,
            'assigner'     => $this->assigner->name,
            'url'          => route('projects.tasks.show', [$this->task->project_id, $this->task->id]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been assigned a task: {$this->task->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->assigner->name} assigned you a task in {$this->task->project->name}.")
            ->line("**{$this->task->title}**")
            ->action('View Task', route('projects.tasks.show', [$this->task->project_id, $this->task->id]))
            ->line('Log in to see the full details and get started.');
    }
}
