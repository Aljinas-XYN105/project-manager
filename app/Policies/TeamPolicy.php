<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->hasRole('admin')
            || $team->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasRole('admin')
            || $team->owner_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $team->owner_id === $user->id;
    }

    public function manageMember(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }
}
