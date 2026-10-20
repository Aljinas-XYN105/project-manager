<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

class ProjectNoteController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $notes = $project->notes()
            ->with(['user', 'task'])
            ->latest()
            ->get();

        return view('projects.notes', compact('project', 'notes'));
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
