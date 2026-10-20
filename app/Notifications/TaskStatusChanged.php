<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly string $oldStatus,
        public readonly User $changedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'task_status_changed',
            'message'    => "{$this->changedBy->name} moved \"{$this->task->title}\" to {$this->task->status}",
            'task_id'    => $this->task->id,
            'task_title' => $this->task->title,
            'old_status' => $this->oldStatus,
            'new_status' => $this->task->status,
            'url'        => "/tasks/{$this->task->id}",
        ];
    }
}
