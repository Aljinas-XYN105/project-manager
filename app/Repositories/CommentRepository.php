<?php

namespace App\Repositories;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;

class CommentRepository implements CommentRepositoryInterface
{
    public function findById(int $id): ?Comment
    {
        return Comment::with(['user', 'replies.user'])->find($id);
    }

    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update($data);
        return $comment->fresh(['user', 'replies.user']);
    }

    public function delete(Comment $comment): bool
    {
        return $comment->delete();
    }

    public function forEntity(string $type, int $id): Collection
    {
        return Comment::with(['user', 'replies.user'])
            ->where('commentable_type', $type)
            ->where('commentable_id', $id)
            ->whereNull('parent_id')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at')
            ->get();
    }
}
