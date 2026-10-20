<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasRole('admin')
            || $task->project->owner_id === $user->id
            || $task->project->members()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($task->created_by === $user->id) {
            return true;
        }

        return $task->assignees()->where('users.id', $user->id)->exists()
            || $task->project->members()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $role = $task->project->members()->where('users.id', $user->id)->first()?->pivot?->role;
        return $task->created_by === $user->id || $role === 'manager';
    }

    public function move(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function assign(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
