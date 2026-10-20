<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function all(array $filters = []): LengthAwarePaginator;
    public function search(string $term): Collection;
    public function byTeam(int $teamId): Collection;
    public function withWorkload(): Collection;
}
