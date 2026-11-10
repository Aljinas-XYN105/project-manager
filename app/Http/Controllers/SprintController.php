<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SprintController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $sprints = $project->sprints()->withCount('tasks')->get();

        $statusColors = [
            'planning'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
            'active'    => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
            'completed' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
            'cancelled' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
        ];

        return Inertia::render('Sprints/Index', [
            'project' => [
                'id'         => $project->id,
                'name'       => $project->name,
                'show_url'   => route('projects.show', $project),
                'create_url' => route('projects.sprints.create', $project),
            ],
            'can_update' => auth()->user()->can('update', $project),
            'sprints' => $sprints->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'goal'        => $s->goal,
                'status'      => $s->status,
                'status_label'=> ucfirst($s->status),
                'status_class'=> $statusColors[$s->status] ?? $statusColors['planning'],
                'is_overdue'  => $s->is_overdue ?? false,
                'is_active'   => $s->status === 'active',
                'is_planning' => $s->status === 'planning',
                'date_range'  => $s->start_date->format('M d') . ' – ' . $s->end_date->format('M d, Y'),
                'tasks_count' => $s->tasks_count,
                'progress'    => $s->progress ?? 0,
                'show_url'    => route('projects.sprints.show', [$project, $s]),
                'start_url'   => route('projects.sprints.start', [$project, $s]),
                'complete_url'=> route('projects.sprints.complete', [$project, $s]),
                'destroy_url' => route('projects.sprints.destroy', [$project, $s]),
            ])->values(),
        ]);
    }

    public function create(Project $project)
    {
        $this->authorize('update', $project);

        return Inertia::render('Sprints/Create', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'show_url'    => route('projects.show', $project),
                'sprints_url' => route('projects.sprints.index', $project),
                'store_url'   => route('projects.sprints.store', $project),
                'sprint_count'=> $project->sprints()->count(),
            ],
        ]);
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

        $statusColors = [
            'planning'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
            'active'    => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
            'completed' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
        ];

        $totalTasks = $sprint->tasks()->count();
        $doneTasks  = $sprint->tasks()->where('status', 'done')->count();
        $inProg     = $sprint->tasks()->where('status', 'in_progress')->count();
        $bugs       = $sprint->tasks()->where('type', 'bug')->count();
        $progress   = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

        return Inertia::render('Sprints/Show', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'show_url'    => route('projects.show', $project),
                'sprints_url' => route('projects.sprints.index', $project),
            ],
            'can_update' => auth()->user()->can('update', $project),
            'sprint' => [
                'id'            => $sprint->id,
                'name'          => $sprint->name,
                'goal'          => $sprint->goal,
                'status'        => $sprint->status,
                'status_label'  => ucfirst($sprint->status),
                'status_class'  => $statusColors[$sprint->status] ?? '',
                'is_active'     => $sprint->status === 'active',
                'is_planning'   => $sprint->status === 'planning',
                'date_range'    => $sprint->start_date->format('M d') . ' – ' . $sprint->end_date->format('M d, Y'),
                'start_url'     => route('projects.sprints.start', [$project, $sprint]),
                'complete_url'  => route('projects.sprints.complete', [$project, $sprint]),
                'add_task_url'  => route('projects.sprints.add-task', [$project, $sprint]),
            ],
            'kpi' => [
                'total_tasks' => $totalTasks,
                'in_progress' => $inProg,
                'done_tasks'  => $doneTasks,
                'bugs'        => $bugs,
                'progress'    => $progress,
            ],
            'columns' => $columns->map(fn ($col) => [
                'id'    => $col->id,
                'name'  => $col->name,
                'color' => $col->color,
                'tasks' => $col->tasks->map(fn ($task) => [
                    'id'          => $task->id,
                    'title'       => $task->title,
                    'ticket_key'  => $task->ticket_key,
                    'type'        => $task->type,
                    'type_icon'   => $task->type_icon ?? 'bi-check-square',
                    'type_color'  => $task->type_color ?? 'text-slate-500',
                    'priority'    => $task->priority ?? 'medium',
                    'show_url'    => route('projects.tasks.show', $task),
                    'remove_url'  => route('projects.sprints.remove-task', [$project, $sprint, $task]),
                    'labels'      => $task->labels->map(fn ($l) => ['name' => $l->name, 'color' => $l->color])->values(),
                    'assignees'   => $task->assignees->take(3)->map(fn ($a) => ['name' => $a->name, 'avatar_url' => $a->avatar_url])->values(),
                ])->values(),
            ])->values(),
            'backlog_tasks' => $backlogTasks->map(fn ($task) => [
                'id'         => $task->id,
                'title'      => $task->title,
                'ticket_key' => $task->ticket_key,
                'type'       => $task->type,
                'type_icon'  => $task->type_icon ?? 'bi-check-square',
                'type_color' => $task->type_color ?? 'text-slate-500',
                'priority'   => $task->priority ?? 'medium',
            ])->values(),
        ]);
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
