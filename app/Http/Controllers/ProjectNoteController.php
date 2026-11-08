<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectNoteController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Project $project): Response
    {
        $this->authorize('view', $project);

        $notes = $project->notes()
            ->with(['user', 'task'])
            ->latest()
            ->get();

        return Inertia::render('Projects/Notes', [
            'project' => [
                'id'       => $project->id,
                'name'     => $project->name,
                'show_url' => route('projects.show', $project),
                'kanban_url' => route('projects.tasks.kanban', $project),
                'notes_url'  => route('project-notes.index', $project),
            ],
            'notes' => $notes->map(fn ($n) => [
                'id'           => $n->id,
                'body'         => $n->body,
                'is_converted' => $n->is_converted,
                'task_id'      => $n->task_id,
                'task_url'     => $n->task ? route('projects.tasks.show', $n->task) : null,
                'user_name'    => $n->user?->name ?? '—',
                'user_avatar'  => $n->user?->avatar_url,
                'created_at'   => $n->created_at->diffForHumans(),
                'store_url'    => route('project-notes.store', $project),
                'update_url'   => route('project-notes.update', $n),
                'destroy_url'  => route('project-notes.destroy', $n),
                'convert_url'  => route('project-notes.convert', $n),
            ]),
            'auth_avatar' => auth()->user()->avatar_url,
            'store_url'   => route('project-notes.store', $project),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate(['body' => 'required|string|max:2000']);

        $note = $project->notes()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->body,
        ]);

        return response()->json(['note' => $note->load('user')]);
    }

    public function update(Request $request, ProjectNote $note)
    {
        $this->authorize('view', $note->project);

        $request->validate(['body' => 'required|string|max:2000']);

        $note->update(['body' => $request->body]);

        return response()->json(['note' => $note->fresh()]);
    }

    public function destroy(ProjectNote $note)
    {
        $this->authorize('view', $note->project);

        $note->delete();

        return response()->json(['success' => true]);
    }

    public function convert(Request $request, ProjectNote $note)
    {
        $this->authorize('view', $note->project);

        if ($note->is_converted) {
            return response()->json(['error' => 'Already converted'], 422);
        }

        $project = $note->project;
        $column  = $project->columns()->where('is_default', true)->first()
                ?? $project->columns()->orderBy('position')->first();

        $task = $this->taskService->createTask([
            'title'      => $note->body,
            'project_id' => $project->id,
            'column_id'  => $column->id,
            'status'     => 'todo',
            'priority'   => 'medium',
        ], $request->user());

        $note->update(['is_converted' => true, 'task_id' => $task->id]);

        return response()->json([
            'task'     => $task,
            'task_url' => route('projects.tasks.show', $task),
        ]);
    }
}
