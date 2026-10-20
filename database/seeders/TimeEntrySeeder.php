<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimeEntrySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $tasks = Task::with('project')->get();

        if ($tasks->isEmpty()) return;

        foreach ($users->take(8) as $user) {
            // Create entries for past 30 days
            for ($day = 30; $day >= 1; $day--) {
                if (rand(0, 2) === 0) continue; // skip some days

                $task       = $tasks->random();
                $durationSec = rand(1800, 14400); // 30min–4h
                $startedAt  = now()->subDays($day)->setHour(rand(9, 16))->setMinute(rand(0,59));
                $stoppedAt  = (clone $startedAt)->addSeconds($durationSec);

                TimeEntry::create([
                    'task_id'          => $task->id,
                    'user_id'          => $user->id,
                    'project_id'       => $task->project_id,
                    'started_at'       => $startedAt,
                    'stopped_at'       => $stoppedAt,
                    'duration_seconds' => $durationSec,
                    'is_billable'      => rand(0,1),
                    'hourly_rate'      => rand(0,1) ? rand(50, 150) : null,
                ]);
            }
        }
    }
}
