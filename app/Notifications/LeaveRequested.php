<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequested extends Notification
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $leave    = $this->leave;
        $applicant = $leave->user->name;
        $type     = $leave->type->name;
        $days     = rtrim(rtrim(number_format((float) $leave->days, 1), '0'), '.');
        $dayLabel = $leave->days == 1 ? 'day' : 'days';
        $reason   = $leave->reason ?: '—';
        $url      = route('leave.approvals');

        return (new MailMessage)
            ->subject("Leave Request: {$applicant} — {$type} ({$leave->date_range})")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$applicant} has submitted a leave request that requires your review.")
            ->line('')
            ->line("**Leave Type:** {$type}")
            ->line("**Dates:** {$leave->date_range}")
            ->line("**Duration:** {$days} {$dayLabel}".($leave->is_half_day ? ' (half day)' : ''))
            ->line("**Reason:** {$reason}")
            ->action('Review Leave Request', $url)
            ->line('You can approve or decline this request from the leave approvals page.')
            ->salutation('— '.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'leave_requested',
            'message'  => "{$this->leave->user->name} requested {$this->leave->days} day(s) of {$this->leave->type->name} ({$this->leave->date_range})",
            'leave_id' => $this->leave->id,
            'url'      => route('leave.approvals'),
        ];
    }
}
