<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with(['teams', 'roles'])->find($id);
    }

    public function all(array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['roles', 'team']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    public function search(string $term): Collection
    {
        return User::active()->search($term)->limit(10)->get();
    }

    public function byTeam(int $teamId): Collection
    {
        return User::whereHas('teams', fn($q) => $q->where('teams.id', $teamId))
            ->active()->get();
    }

    public function withWorkload(): Collection
    {
        return User::withCount([
            'assignedTasks as open_tasks_count' => fn($q) => $q->whereNotIn('status', ['done', 'cancelled']),
        ])->active()->orderByDesc('open_tasks_count')->get();
    }
}
