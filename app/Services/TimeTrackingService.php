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
        // If a timer is already running for this exact task, return it
        $existing = TimeEntry::where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->where('status', 'running')
            ->first();
        if ($existing) {
            return $existing;
        }

        // Resume if previously paused for this task
        $paused = TimeEntry::where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->where('status', 'paused')
            ->latest()
            ->first();
        if ($paused) {
            return $this->resumeTimer($paused);
        }

        $entry = $this->timeEntryRepository->create([
            'user_id'     => $user->id,
            'task_id'     => $task->id,
            'project_id'  => $task->project_id,
            'description' => $description,
            'status'      => 'running',
            'started_at'  => now(),
        ]);

        $this->activityLogService->log("started timer on \"{$task->title}\"", $task, $user);

        return $entry;
    }

    public function pauseTimer(TimeEntry $entry): TimeEntry
    {
        if ($entry->status !== 'running') {
            return $entry;
        }

        $updated = $this->timeEntryRepository->update($entry, [
            'status'    => 'paused',
            'paused_at' => now(),
        ]);

        $this->activityLogService->log(
            "paused timer on \"{$entry->task->title}\"",
            $entry->task,
            $entry->user
        );

        return $updated;
    }

    public function resumeTimer(TimeEntry $entry): TimeEntry
    {
        if ($entry->status !== 'paused') {
            return $entry;
        }

        $additionalPaused = $entry->paused_at ? $entry->paused_at->diffInSeconds(now()) : 0;

        $updated = $this->timeEntryRepository->update($entry, [
            'status'          => 'running',
            'paused_at'       => null,
            'paused_seconds'  => ($entry->paused_seconds ?? 0) + $additionalPaused,
            'auto_paused'     => false,
        ]);

        $this->activityLogService->log(
            "resumed timer on \"{$entry->task->title}\"",
            $entry->task,
            $entry->user
        );

        return $updated;
    }

    public function stopTimer(TimeEntry $entry): TimeEntry
    {
        $now = now();
        $pausedSeconds = $entry->paused_seconds ?? 0;

        // If currently paused, add time since paused_at
        if ($entry->status === 'paused' && $entry->paused_at) {
            $pausedSeconds += $entry->paused_at->diffInSeconds($now);
        }

        $duration = max(0, $entry->started_at->diffInSeconds($now) - $pausedSeconds);

        $updated = $this->timeEntryRepository->update($entry, [
            'status'           => 'stopped',
            'stopped_at'       => $now,
            'paused_at'        => null,
            'paused_seconds'   => $pausedSeconds,
            'duration_seconds' => $duration,
        ]);

        $this->activityLogService->log(
            "stopped timer — logged {$updated->duration_formatted}",
            $entry->task,
            $entry->user
        );

        return $updated;
    }

    public function pauseAllRunning(User $user, bool $autoPaused = false): int
    {
        $running = TimeEntry::where('user_id', $user->id)->where('status', 'running')->get();
        foreach ($running as $entry) {
            $this->timeEntryRepository->update($entry, [
                'status'      => 'paused',
                'paused_at'   => now(),
                'auto_paused' => $autoPaused,
            ]);
        }
        return $running->count();
    }

    public function resumeAutoPaused(User $user): int
    {
        $paused = TimeEntry::where('user_id', $user->id)
            ->where('status', 'paused')
            ->where('auto_paused', true)
            ->get();

        foreach ($paused as $entry) {
            $this->resumeTimer($entry);
        }
        return $paused->count();
    }

    public function getActiveEntries(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return TimeEntry::where('user_id', $user->id)
            ->whereIn('status', ['running', 'paused'])
            ->with(['task.project'])
            ->orderBy('started_at')
            ->get();
    }

    public function createManualEntry(array $data, User $user): TimeEntry
    {
        $data['user_id']    = $user->id;
        $data['project_id'] = Task::findOrFail($data['task_id'])->project_id;

        if (isset($data['started_at']) && isset($data['stopped_at'])) {
            $data['duration_seconds'] = max(0, Carbon::parse($data['started_at'])
                ->diffInSeconds(Carbon::parse($data['stopped_at'])));
        }

        return $this->timeEntryRepository->create($data);
    }

    public function updateEntry(TimeEntry $entry, array $data): TimeEntry
    {
        if (isset($data['started_at']) && isset($data['stopped_at'])) {
            $data['duration_seconds'] = max(0, Carbon::parse($data['started_at'])
                ->diffInSeconds(Carbon::parse($data['stopped_at'])));
        }

        return $this->timeEntryRepository->update($entry, $data);
    }

    public function deleteEntry(TimeEntry $entry): bool
    {
        return $this->timeEntryRepository->delete($entry);
    }

    public function getRunningEntry(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)->where('status', 'running')->with(['task.project'])->latest()->first();
    }

    public function getEntriesForTask(Task $task): Collection
    {
        return $this->timeEntryRepository->forTask($task->id);
    }

    public function getSummaryForUser(User $user, Carbon $from, Carbon $to): array
    {
        $entries = $this->timeEntryRepository->forDateRange($user->id, $from->toDateTimeString(), $to->toDateTimeString());

        $totalSeconds = $entries->sum(fn($e) => max(0, $e->elapsed_seconds));

        return [
            'entries'       => $entries,
            'total_seconds' => $totalSeconds,
            'total_hours'   => round($totalSeconds / 3600, 2),
            'by_project'    => $entries->groupBy('project_id')->map(fn($g) => $g->sum(fn($e) => max(0, $e->elapsed_seconds))),
            'by_date'       => $entries->groupBy(fn($e) => $e->started_at->toDateString())
                ->map(fn($g) => $g->sum(fn($e) => max(0, $e->elapsed_seconds))),
        ];
    }
}
