<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequested extends Notification
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
