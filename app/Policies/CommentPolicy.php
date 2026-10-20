<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->hasRole('admin') || $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->hasRole('admin') || $comment->user_id === $user->id) {
            return true;
        }

        // Managers can delete any comment
        $commentable = $comment->commentable;
        if ($commentable instanceof \App\Models\Task) {
            $role = $commentable->project->members()->where('users.id', $user->id)->first()?->pivot?->role;
            return $role === 'manager';
        }

        return false;
    }

    public function pin(User $user, Comment $comment): bool
    {
        $commentable = $comment->commentable;
        if ($commentable instanceof \App\Models\Task) {
            $role = $commentable->project->members()->where('users.id', $user->id)->first()?->pivot?->role;
            return $user->hasRole('admin') || $role === 'manager';
        }

        return $user->hasRole('admin');
    }
}
