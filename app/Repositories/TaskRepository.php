<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Column;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface
{
    public function findById(int $id): ?Task
    {
        return Task::with([
            'project', 'column', 'creator', 'assignees', 'labels',
            'subtasks.assignees', 'timeEntries.user',
            'comments.user', 'comments.replies.user',
            'attachments.user', 'dependencies', 'dependents', 'parent',
        ])->find($id);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function forProject(int $projectId, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with(['assignees', 'labels', 'column', 'creator'])
            ->where('project_id', $projectId)
            ->whereNull('parent_id');

        $this->applyFilters($query, $filters);

        return $query->orderBy('position')->paginate($filters['per_page'] ?? 20);
    }

    public function forUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with(['project', 'assignees', 'labels', 'column'])
            ->whereNull('parent_id')
            ->where(fn($q) => $q->where('created_by', $userId)
                ->orWhereHas('assignees', fn($a) => $a->where('users.id', $userId)));

        $this->applyFilters($query, $filters);

        return $query->orderBy('due_date')->paginate($filters['per_page'] ?? 20);
    }

    public function getKanbanData(int $projectId): Collection
    {
        return Column::with([
            'tasks' => fn($q) => $q->whereNull('parent_id')
                ->with(['assignees', 'labels', 'subtasks'])
                ->orderBy('position'),
        ])
        ->where('project_id', $projectId)
        ->orderBy('position')
        ->get();
    }

    public function getCalendarData(int $projectId, int $year, int $month): Collection
    {
        return Task::with(['assignees', 'labels', 'project'])
            ->where('project_id', $projectId)
            ->whereNull('parent_id')
            ->whereNotNull('due_date')
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->get();
    }

    public function reorder(int $columnId, array $orderedIds): void
    {
        DB::transaction(function () use ($columnId, $orderedIds) {
            foreach ($orderedIds as $position => $taskId) {
                Task::where('id', $taskId)
                    ->where('column_id', $columnId)
                    ->update(['position' => $position]);
            }
        });
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['assignee'])) {
            $query->whereHas('assignees', fn($q) => $q->where('users.id', $filters['assignee']));
        }
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['due_from'])) {
            $query->where('due_date', '>=', $filters['due_from']);
        }
        if (!empty($filters['due_to'])) {
            $query->where('due_date', '<=', $filters['due_to']);
        }
        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }
    }
}
