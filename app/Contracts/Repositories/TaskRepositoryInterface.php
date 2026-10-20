<?php

namespace App\Contracts\Repositories;

use App\Models\Column;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function findById(int $id): ?Task;
    public function create(array $data): Task;
    public function update(Task $task, array $data): Task;
    public function delete(Task $task): bool;
    public function forProject(int $projectId, array $filters = []): LengthAwarePaginator;
    public function forUser(int $userId, array $filters = []): LengthAwarePaginator;
    public function getKanbanData(int $projectId): Collection;
    public function getCalendarData(int $projectId, int $year, int $month): Collection;
    public function reorder(int $columnId, array $orderedIds): void;
}
