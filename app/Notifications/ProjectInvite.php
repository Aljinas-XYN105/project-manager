<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectInvite extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
        public readonly User $invitedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been added to {$this->project->name}")
            ->line("{$this->invitedBy->name} added you to a project.")
            ->line("**Project:** {$this->project->name}")
            ->action('View Project', url("/projects/{$this->project->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'project_invite',
            'message'        => "{$this->invitedBy->name} added you to \"{$this->project->name}\"",
            'project_id'     => $this->project->id,
            'project_name'   => $this->project->name,
            'url'            => "/projects/{$this->project->id}",
        ];
    }
}
