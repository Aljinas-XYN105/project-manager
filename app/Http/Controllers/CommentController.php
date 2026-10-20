<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService) {}

    public function store(StoreCommentRequest $request)
    {
        $type = $request->commentable_type;
        $commentable = match ($type) {
            'task'    => Task::findOrFail($request->commentable_id),
            'project' => Project::findOrFail($request->commentable_id),
            default   => abort(422, 'Invalid commentable type'),
        };

        $comment = $this->commentService->addComment($commentable, $request->validated(), $request->user());

        return response()->json(['comment' => $comment]);
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $request->validate(['body' => 'required|string|max:5000']);

        $updated = $this->commentService->updateComment($comment, $request->validated());

        return response()->json(['comment' => $updated]);
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return response()->json(['success' => true]);
    }

    public function pin(Comment $comment)
    {
        $this->authorize('pin', $comment);

        $updated = $this->commentService->pinComment($comment);

        return response()->json(['comment' => $updated, 'is_pinned' => $updated->is_pinned]);
    }
}
