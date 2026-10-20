<?php

namespace App\Services;

use App\Contracts\Repositories\TimeEntryRepositoryInterface;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TimeTrackingService
{
    public function __construct(
        private readonly TimeEntryRepositoryInterface $timeEntryRepository,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function startTimer(User $user, Task $task, ?string $description = null): TimeEntry
    {
        // Prevent duplicate running entries
        $running = $this->timeEntryRepository->running($user->id);
        if ($running) {
            $this->stopTimer($running);
        }

        $entry = $this->timeEntryRepository->create([
            'user_id'     => $user->id,
            'task_id'     => $task->id,
            'project_id'  => $task->project_id,
            'description' => $description,
            'started_at'  => now(),
        ]);

        $this->activityLogService->log(
            "started timer on \"{$task->title}\"",
            $task,
            $user
        );

        return $entry;
    }

    public function stopTimer(TimeEntry $entry): TimeEntry
    {
        $stoppedAt = now();
        $duration  = $stoppedAt->diffInSeconds($entry->started_at);

        $updated = $this->timeEntryRepository->update($entry, [
            'stopped_at'       => $stoppedAt,
            'duration_seconds' => $duration,
        ]);

        $this->activityLogService->log(
            "stopped timer — logged {$updated->duration_formatted}",
            $entry->task,
            $entry->user
        );

        return $updated;
    }

    public function createManualEntry(array $data, User $user): TimeEntry
    {
        $data['user_id']    = $user->id;
        $data['project_id'] = Task::findOrFail($data['task_id'])->project_id;

        if (isset($data['started_at']) && isset($data['stopped_at'])) {
            $data['duration_seconds'] = Carbon::parse($data['stopped_at'])
                ->diffInSeconds(Carbon::parse($data['started_at']));
        }

        return $this->timeEntryRepository->create($data);
    }

    public function updateEntry(TimeEntry $entry, array $data): TimeEntry
    {
        if (isset($data['started_at']) && isset($data['stopped_at'])) {
            $data['duration_seconds'] = Carbon::parse($data['stopped_at'])
                ->diffInSeconds(Carbon::parse($data['started_at']));
        }

        return $this->timeEntryRepository->update($entry, $data);
    }

    public function deleteEntry(TimeEntry $entry): bool
    {
        return $this->timeEntryRepository->delete($entry);
    }

    public function getRunningEntry(User $user): ?TimeEntry
    {
        return $this->timeEntryRepository->running($user->id);
    }

    public function getEntriesForTask(Task $task): Collection
    {
        return $this->timeEntryRepository->forTask($task->id);
    }

    public function getSummaryForUser(User $user, Carbon $from, Carbon $to): array
    {
        $entries = $this->timeEntryRepository->forDateRange($user->id, $from->toDateTimeString(), $to->toDateTimeString());

        $totalSeconds = $entries->sum('duration_seconds');

        return [
            'entries'       => $entries,
            'total_seconds' => $totalSeconds,
            'total_hours'   => round($totalSeconds / 3600, 2),
            'by_project'    => $entries->groupBy('project_id')->map(fn($g) => $g->sum('duration_seconds')),
            'by_date'       => $entries->groupBy(fn($e) => $e->started_at->toDateString())
                ->map(fn($g) => $g->sum('duration_seconds')),
        ];
    }
}
