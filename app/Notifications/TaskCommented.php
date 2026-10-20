<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCommented extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly Comment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'task_commented',
            'message'    => "{$this->comment->user->name} commented on \"{$this->task->title}\"",
            'task_id'    => $this->task->id,
            'task_title' => $this->task->title,
            'comment_id' => $this->comment->id,
            'url'        => "/tasks/{$this->task->id}#comment-{$this->comment->id}",
        ];
    }
}
