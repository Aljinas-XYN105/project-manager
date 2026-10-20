<?php

namespace App\Contracts\Repositories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function findById(int $id): ?Project;
    public function create(array $data): Project;
    public function update(Project $project, array $data): Project;
    public function delete(Project $project): bool;
    public function forUser(int $userId, array $filters = []): LengthAwarePaginator;
    public function byTeam(int $teamId, array $filters = []): Collection;
    public function withStats(int $projectId): ?Project;
    public function all(array $filters = []): LengthAwarePaginator;
}
