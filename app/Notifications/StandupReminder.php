<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class StandupReminder extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $date,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dateLabel = Carbon::parse($this->date)->format('l, F j');
        $url       = url('/standups/my/'.$this->date);

        return (new MailMessage)
            ->subject("Reminder: Submit your standup for {$dateLabel}")
            ->line("Hi {$notifiable->name},")
            ->line("You haven't submitted your standup for **{$dateLabel}** yet.")
            ->action('Submit Standup', $url)
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'standup_reminder',
            'message' => 'Reminder: submit your standup for '.Carbon::parse($this->date)->format('l, F j'),
            'date'    => $this->date,
            'url'     => '/standups/my/'.$this->date,
        ];
    }
}
