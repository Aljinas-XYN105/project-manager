<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPosted extends Notification
{
    use Queueable;

    public function __construct(public readonly Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'announcement',
            'message'         => "New announcement: {$this->announcement->title}",
            'announcement_id' => $this->announcement->id,
            'url'             => route('announcements.index').'#announcement-'.$this->announcement->id,
        ];
    }
}
