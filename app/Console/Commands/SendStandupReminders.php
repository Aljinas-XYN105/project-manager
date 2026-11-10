<?php

namespace App\Console\Commands;

use App\Models\Standup;
use App\Models\User;
use App\Notifications\StandupReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendStandupReminders extends Command
{
    protected $signature   = 'standups:send-reminders {--date= : Date to check (default: today)}';
    protected $description = 'Send standup submission reminders to active members who have not yet submitted';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        // Skip weekends
        if ($date->isWeekend()) {
            $this->info('Skipping weekend: '.$date->toDateString());
            return self::SUCCESS;
        }

        $submittedUserIds = Standup::whereDate('standup_date', $date)
            ->pluck('user_id');

        $missing = User::where('is_active', true)
            ->whereNotIn('id', $submittedUserIds)
            ->get();

        if ($missing->isEmpty()) {
            $this->info('All members have submitted for '.$date->toDateString());
            return self::SUCCESS;
        }

        $dateStr = $date->toDateString();
        foreach ($missing as $user) {
            $user->notify(new StandupReminder($dateStr));
        }

        $this->info("Sent reminders to {$missing->count()} member(s) for {$dateStr}.");

        return self::SUCCESS;
    }
}
