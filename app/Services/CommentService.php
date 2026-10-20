<?php

namespace App\Services;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly NotificationService $notificationService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function addComment(Model $commentable, array $data, User $author): Comment
    {
        $comment = $this->commentRepository->create([
            'commentable_type' => get_class($commentable),
            'commentable_id'   => $commentable->getKey(),
            'user_id'          => $author->id,
            'parent_id'        => $data['parent_id'] ?? null,
            'body'             => $data['body'],
        ]);

        // Notify task assignees if commenting on a task
        if ($commentable instanceof \App\Models\Task) {
            $this->notificationService->sendTaskCommented($commentable, $comment);
        }

        $this->activityLogService->log('added a comment', $commentable, $author);

        return $comment->load(['user', 'replies.user']);
    }

    public function updateComment(Comment $comment, array $data): Comment
    {
        return $this->commentRepository->update($comment, [
            'body'      => $data['body'],
            'edited_at' => now(),
        ]);
    }

    public function deleteComment(Comment $comment): bool
    {
        return $this->commentRepository->delete($comment);
    }

    public function pinComment(Comment $comment): Comment
    {
        return $this->commentRepository->update($comment, ['is_pinned' => !$comment->is_pinned]);
    }

    public function getCommentsForEntity(Model $entity): Collection
    {
        return $this->commentRepository->forEntity(get_class($entity), $entity->getKey());
    }
}
