<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveReviewed extends Notification
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $verb = $this->leave->status === 'approved' ? 'approved' : 'declined';

        return [
            'type'     => 'leave_reviewed',
            'message'  => "{$this->leave->reviewer?->name} {$verb} your {$this->leave->type->name} ({$this->leave->date_range})"
                .($this->leave->review_note ? ": “{$this->leave->review_note}”" : ''),
            'leave_id' => $this->leave->id,
            'url'      => route('leave.index'),
        ];
    }
}
