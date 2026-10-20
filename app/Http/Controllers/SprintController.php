<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SprintController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $sprints = $project->sprints()->withCount('tasks')->get();
        $activeSprint = $sprints->where('status', 'active')->first();

        return view('sprints.index', compact('project', 'sprints', 'activeSprint'));
    }

    public function create(Project $project)
    {
        $this->authorize('update', $project);
        return view('sprints.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $sprint = $project->sprints()->create([
            ...$validated,
            'created_by' => auth()->id(),
            'status'     => 'planning',
        ]);

        return redirect()->route('projects.sprints.show', [$project, $sprint])
            ->with('success', "Sprint \"{$sprint->name}\" created.");
    }

    public function show(Project $project, Sprint $sprint)
    {
        $this->authorize('view', $project);

        $columns = $project->columns()->with([
            'tasks' => fn($q) => $q->where('sprint_id', $sprint->id)
                ->with(['assignees', 'labels', 'subtasks'])
                ->orderBy('position'),
        ])->orderBy('position')->get();

        $backlogTasks = Task::with(['assignees', 'labels'])
            ->where('project_id', $project->id)
            ->whereNull('sprint_id')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->get();

        return view('sprints.show', compact('project', 'sprint', 'columns', 'backlogTasks'));
    }

    public function start(Project $project, Sprint $sprint)
    {
        $this->authorize('update', $project);

        // Only one active sprint allowed
        if ($project->sprints()->where('status', 'active')->exists()) {
            return back()->with('error', 'There is already an active sprint. Complete it before starting a new one.');
        }

        $sprint->update(['status' => 'active', 'started_at' => now()]);

        return back()->with('success', "Sprint \"{$sprint->name}\" is now active.");
    }

    public function complete(Project $project, Sprint $sprint)
    {
        $this->authorize('update', $project);

        $incompleteTasks = $sprint->tasks()->whereNotIn('status', ['done', 'cancelled'])->count();

        $sprint->update(['status' => 'completed', 'completed_at' => now()]);

        $msg = "Sprint \"{$sprint->name}\" completed.";
        if ($incompleteTasks > 0) {
            $msg .= " {$incompleteTasks} incomplete task(s) moved back to backlog.";
            $sprint->tasks()->whereNotIn('status', ['done', 'cancelled'])
                ->update(['sprint_id' => null]);
        }

        return back()->with('success', $msg);
    }

    public function addTask(Request $request, Project $project, Sprint $sprint)
    {
        $this->authorize('update', $project);

        $request->validate(['task_ids' => 'required|array', 'task_ids.*' => 'integer']);

        Task::whereIn('id', $request->task_ids)
            ->where('project_id', $project->id)
            ->update(['sprint_id' => $sprint->id]);

        return response()->json(['success' => true]);
    }

    public function removeTask(Request $request, Project $project, Sprint $sprint, Task $task)
    {
        $this->authorize('update', $project);

        $task->update(['sprint_id' => null]);

        return response()->json(['success' => true]);
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        $this->authorize('update', $project);

        // Move sprint tasks back to backlog
        $sprint->tasks()->update(['sprint_id' => null]);
        $sprint->delete();

        return redirect()->route('projects.sprints.index', $project)
            ->with('success', 'Sprint deleted.');
    }
}
