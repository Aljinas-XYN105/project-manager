<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    public function view(User $user, TimeEntry $entry): bool
    {
        return $user->hasRole('admin') || $entry->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TimeEntry $entry): bool
    {
        return $user->hasRole('admin') || $entry->user_id === $user->id;
    }

    public function delete(User $user, TimeEntry $entry): bool
    {
        return $user->hasRole('admin') || $entry->user_id === $user->id;
    }
}
