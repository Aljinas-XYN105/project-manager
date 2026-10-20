<?php

namespace App\Contracts\Repositories;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Collection;

interface TimeEntryRepositoryInterface
{
    public function findById(int $id): ?TimeEntry;
    public function create(array $data): TimeEntry;
    public function update(TimeEntry $entry, array $data): TimeEntry;
    public function delete(TimeEntry $entry): bool;
    public function running(int $userId): ?TimeEntry;
    public function forDateRange(int $userId, string $from, string $to): Collection;
    public function summaryByProject(array $filters): Collection;
    public function summaryByUser(array $filters): Collection;
    public function forTask(int $taskId): Collection;
    public function forUser(int $userId, array $filters = []): Collection;
}
