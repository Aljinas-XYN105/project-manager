<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        if ($project->is_public) {
            return true;
        }

        return $user->hasRole('admin')
            || $project->owner_id === $user->id
            || $project->members()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($project->owner_id === $user->id) {
            return true;
        }

        $role = $project->members()->where('users.id', $user->id)->first()?->pivot?->role;
        return $role === 'manager';
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole('admin') || $project->owner_id === $user->id;
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function manageMember(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
