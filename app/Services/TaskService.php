<?php

namespace App\Services;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ActivityLogService $activityLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function createTask(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['created_by'] = $creator->id;

            // Set position to end of column
            $data['position'] = Task::where('column_id', $data['column_id'])->max('position') + 1;

            // Assign ticket number
            $project = Project::find($data['project_id']);
            if ($project) {
                $data['ticket_number'] = $project->nextTicketNumber();
            }

            $task = $this->taskRepository->create($data);

            if (!empty($data['assignee_ids'])) {
                $this->assignUsers($task, $data['assignee_ids'], $creator);
            }

            if (!empty($data['label_ids'])) {
                $this->syncLabels($task, $data['label_ids']);
            }

            $this->activityLogService->log(
                "created task \"{$task->title}\"",
                $task,
                $creator
            );

            Cache::forget("project_stats_{$task->project_id}");

            return $task->load(['assignees', 'labels', 'column']);
        });
    }

    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $oldStatus = $task->status;
            $actor     = auth()->user();

            // Log human-readable field changes
            foreach ([
                'title'    => ['title', fn ($v) => "\"{$v}\""],
                'priority' => ['priority', fn ($v) => $v],
                'type'     => ['type', fn ($v) => $v],
                'due_date' => ['due date', fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('M j, Y') : 'none'],
            ] as $field => [$label, $fmt]) {
                if (isset($data[$field]) && (string) $data[$field] !== (string) $task->$field) {
                    $old = $task->$field ? $fmt($task->$field) : 'none';
                    $new = $data[$field]  ? $fmt($data[$field])  : 'none';
                    $this->activityLogService->log("changed {$label} from {$old} to {$new}", $task, $actor);
                }
            }

            if (isset($data['status']) && $data['status'] === 'done' && $oldStatus !== 'done') {
                $data['completed_at'] = now();
            }

            $updated = $this->taskRepository->update($task, $data);

            // array_key_exists so an empty array (all unchecked) also clears assignees
            if (array_key_exists('assignee_ids', $data)) {
                $this->assignUsers($updated, (array) ($data['assignee_ids'] ?? []), $actor);
            }

            if (!empty($data['label_ids'])) {
                $this->syncLabels($updated, $data['label_ids']);
            }

            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                $this->notificationService->sendTaskStatusChanged($updated, $oldStatus, $actor);
                $this->activityLogService->log(
                    "changed status from {$oldStatus} to {$data['status']}",
                    $updated,
                    $actor
                );
            }

            Cache::forget("project_stats_{$task->project_id}");

            return $updated->load(['assignees', 'labels', 'column']);
        });
    }

    public function deleteTask(Task $task): bool
    {
        Cache::forget("project_stats_{$task->project_id}");
        $this->activityLogService->log("deleted task \"{$task->title}\"", $task, auth()->user());
        return $this->taskRepository->delete($task);
    }

    public function moveTask(Task $task, Column $newColumn, int $position): void
    {
        DB::transaction(function () use ($task, $newColumn, $position) {
            $oldColumnId = $task->column_id;

            // Shift tasks in new column down
            Task::where('column_id', $newColumn->id)
                ->where('position', '>=', $position)
                ->increment('position');

            $task->update([
                'column_id' => $newColumn->id,
                'position'  => $position,
            ]);

            // Re-sequence old column with a single CASE WHEN statement
            $remaining = Task::where('column_id', $oldColumnId)->orderBy('position')->pluck('id');
            if ($remaining->isNotEmpty()) {
                $cases = $remaining->map(fn ($id, $i) => "WHEN {$id} THEN {$i}")->implode(' ');
                DB::statement("UPDATE tasks SET position = CASE id {$cases} END WHERE column_id = {$oldColumnId}");
            }

            $this->activityLogService->log(
                "moved task \"{$task->title}\" to column \"{$newColumn->name}\"",
                $task,
                auth()->user()
            );
        });
    }

    public function updateTaskStatus(Task $task, string $status): Task
    {
        return $this->updateTask($task, ['status' => $status]);
    }

    public function assignUsers(Task $task, array $userIds, User $assignedBy): void
    {
        $oldIds = $task->assignees()->pluck('users.id')->toArray();

        $pivot = collect($userIds)->mapWithKeys(fn ($id) => [$id => ['assigned_by' => $assignedBy->id]])->all();
        $task->assignees()->sync($pivot);

        $added   = array_diff($userIds, $oldIds);
        $removed = array_diff($oldIds, $userIds);

        if ($added) {
            $names = User::whereIn('id', $added)->pluck('name')->join(', ');
            $this->activityLogService->log("assigned {$names}", $task, $assignedBy);
        }
        if ($removed) {
            $names = User::whereIn('id', $removed)->pluck('name')->join(', ');
            $this->activityLogService->log("unassigned {$names}", $task, $assignedBy);
        }

        // Notify only newly added assignees
        User::whereIn('id', $added)
            ->where('id', '!=', $assignedBy->id)
            ->get()
            ->each(fn ($assignee) => $this->notificationService->sendTaskAssigned($task, $assignee, $assignedBy));
    }

    public function syncLabels(Task $task, array $labelIds): void
    {
        $task->labels()->sync($labelIds);
    }

    public function getKanbanBoard(int $projectId): Collection
    {
        return $this->taskRepository->getKanbanData($projectId);
    }

    public function getCalendarData(int $projectId, int $year, int $month): Collection
    {
        return $this->taskRepository->getCalendarData($projectId, $year, $month);
    }

    public function reorderColumn(Column $column, array $orderedTaskIds): void
    {
        $this->taskRepository->reorder($column->id, $orderedTaskIds);
    }
}
