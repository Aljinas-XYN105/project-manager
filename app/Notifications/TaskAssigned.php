<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task Assigned: {$this->task->title}")
            ->line("{$this->assignedBy->name} assigned you a task.")
            ->line("**Task:** {$this->task->title}")
            ->line("**Project:** {$this->task->project->name}")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Thank you for using Project Management!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'task_assigned',
            'message'     => "{$this->assignedBy->name} assigned you \"{$this->task->title}\"",
            'task_id'     => $this->task->id,
            'task_title'  => $this->task->title,
            'project_id'  => $this->task->project_id,
            'assigned_by' => $this->assignedBy->name,
            'url'         => "/tasks/{$this->task->id}",
        ];
    }
}
