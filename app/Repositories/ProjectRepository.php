<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findById(int $id): ?Project
    {
        return Project::with(['owner', 'members', 'columns'])->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    public function forUser(int $userId, array $filters = [], bool $isAdmin = false): LengthAwarePaginator
    {
        $query = Project::with(['owner', 'members']);

        if (!$isAdmin) {
            $query->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                  ->orWhereHas('members', fn($m) => $m->where('users.id', $userId))
                  ->orWhere('is_public', true);
            });
        }

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function byTeam(int $teamId, array $filters = []): Collection
    {
        $query = Project::with(['owner', 'members'])->where('team_id', $teamId);
        $this->applyFilters($query, $filters);
        return $query->latest()->get();
    }

    public function withStats(int $projectId): ?Project
    {
        return Project::with([
            'owner', 'members', 'columns.tasks.assignees',
            'labels', 'timeEntries',
        ])->find($projectId);
    }

    public function all(array $filters = []): LengthAwarePaginator
    {
        $query = Project::with(['owner', 'members']);
        $this->applyFilters($query, $filters);
        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
    }
}
