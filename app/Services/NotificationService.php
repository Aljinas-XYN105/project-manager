<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function sendTaskAssigned(Task $task, User $assignee, User $assignedBy): void
    {
        $assignee->notify(new \App\Notifications\TaskAssigned($task, $assignedBy));
    }

    public function sendTaskCommented(Task $task, Comment $comment): void
    {
        $notifyUsers = $task->assignees->merge([$task->creator])->unique('id')
            ->reject(fn($u) => $u->id === $comment->user_id);

        foreach ($notifyUsers as $user) {
            $user->notify(new \App\Notifications\TaskCommented($task, $comment));
        }
    }

    public function sendTaskStatusChanged(Task $task, string $oldStatus, User $changedBy): void
    {
        $notifyUsers = $task->assignees->reject(fn($u) => $u->id === $changedBy->id);

        foreach ($notifyUsers as $user) {
            $user->notify(new \App\Notifications\TaskStatusChanged($task, $oldStatus, $changedBy));
        }
    }

    public function sendProjectInvite(Project $project, User $invitee, User $invitedBy): void
    {
        $invitee->notify(new \App\Notifications\ProjectInvite($project, $invitedBy));
    }

    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
