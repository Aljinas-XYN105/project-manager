<?php

namespace App\Repositories;

use App\Contracts\Repositories\TimeEntryRepositoryInterface;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Collection;

class TimeEntryRepository implements TimeEntryRepositoryInterface
{
    public function findById(int $id): ?TimeEntry
    {
        return TimeEntry::with(['task', 'user', 'project'])->find($id);
    }

    public function create(array $data): TimeEntry
    {
        return TimeEntry::create($data);
    }

    public function update(TimeEntry $entry, array $data): TimeEntry
    {
        $entry->update($data);
        return $entry->fresh();
    }

    public function delete(TimeEntry $entry): bool
    {
        return $entry->delete();
    }

    public function running(int $userId): ?TimeEntry
    {
        return TimeEntry::with(['task.project'])
            ->where('user_id', $userId)
            ->whereNull('stopped_at')
            ->latest()
            ->first();
    }

    public function forDateRange(int $userId, string $from, string $to): Collection
    {
        return TimeEntry::with(['task', 'project'])
            ->where('user_id', $userId)
            ->byDateRange($from, $to)
            ->orderBy('started_at', 'desc')
            ->get();
    }

    public function summaryByProject(array $filters): Collection
    {
        $query = TimeEntry::with('project')
            ->selectRaw('project_id, SUM(duration_seconds) as total_seconds, COUNT(*) as entry_count')
            ->groupBy('project_id');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['from'])) {
            $query->where('started_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('started_at', '<=', $filters['to']);
        }

        return $query->get();
    }

    public function summaryByUser(array $filters): Collection
    {
        $query = TimeEntry::with('user')
            ->selectRaw('user_id, SUM(duration_seconds) as total_seconds, COUNT(*) as entry_count')
            ->groupBy('user_id');

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['from'])) {
            $query->where('started_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('started_at', '<=', $filters['to']);
        }

        return $query->get();
    }

    public function forTask(int $taskId): Collection
    {
        return TimeEntry::with(['user'])
            ->where('task_id', $taskId)
            ->orderByDesc('started_at')
            ->get();
    }

    public function forUser(int $userId, array $filters = []): Collection
    {
        $query = TimeEntry::with(['task', 'project'])
            ->where('user_id', $userId);

        if (!empty($filters['from'])) {
            $query->where('started_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('started_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('started_at')->get();
    }
}
