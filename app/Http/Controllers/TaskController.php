<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Http\Requests\Task\MoveTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\ActivityLog;
use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $tasks = $this->taskRepository->forProject($project->id, $request->only([
            'status', 'priority', 'assignee', 'search', 'due_from', 'due_to',
        ]));

        $members = $project->members;

        return view('tasks.index', compact('project', 'tasks', 'members'));
    }

    public function kanban(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $columns = $this->taskService->getKanbanBoard($project->id);
        $members = $project->members;

        return view('tasks.kanban', compact('project', 'columns', 'members'));
    }

    public function calendar(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $tasks = $this->taskService->getCalendarData($project->id, $year, $month);

        return view('tasks.calendar', compact('project', 'tasks', 'year', 'month'));
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task        = $this->taskRepository->findById($task->id);
        $members     = \App\Models\User::active()->orderBy('name')->get();
        $activityLogs = ActivityLog::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();

        return view('tasks.show', compact('task', 'members', 'activityLogs'));
    }

    public function backlog(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $tasks = Task::with(['assignees', 'labels', 'column'])
            ->where('project_id', $project->id)
            ->whereNull('parent_id')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('created_at')
            ->get();

        $columns = $project->columns()->orderBy('position')->get();

        return view('tasks.backlog', compact('project', 'tasks', 'columns'));
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'task_ids'   => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
            'action'     => 'required|in:status,delete',
            'value'      => 'nullable|string',
        ]);

        $tasks = Task::whereIn('id', $request->task_ids)->get();

        foreach ($tasks as $task) {
            $this->authorize('update', $task);
        }

        if ($request->action === 'status' && $request->value) {
            Task::whereIn('id', $request->task_ids)
                ->update(['status' => $request->value]);
        } elseif ($request->action === 'delete') {
            foreach ($tasks as $task) {
                $this->taskService->deleteTask($task);
            }
        }

        return response()->json(['success' => true, 'count' => $tasks->count()]);
    }

    public function create(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $column  = $project->columns()->where('is_default', true)->first() ?? $project->columns()->first();
        $members = \App\Models\User::active()->orderBy('name')->get();
        $labels  = $project->labels;

        return view('tasks.create', compact('project', 'column', 'members', 'labels'));
    }

    public function createGlobal(Request $request)
    {
        $projects = Project::whereHas('members', fn($q) => $q->where('users.id', $request->user()->id))
            ->orWhere('owner_id', $request->user()->id)
            ->with(['columns', 'members', 'labels'])
            ->orderBy('name')
            ->get();

        $defaultProject = $projects->firstWhere('id', $request->query('project_id')) ?? $projects->first();

        return view('tasks.create-global', compact('projects', 'defaultProject'));
    }

    public function storeGlobal(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'project_id'          => 'required|exists:projects,id',
            'column_id'           => 'nullable|exists:columns,id',
            'type'                => 'nullable|in:task,bug,feature,story,epic',
            'status'              => 'nullable|in:todo,in_progress,in_review,done,cancelled',
            'priority'            => 'nullable|in:low,medium,high,critical',
            'due_date'            => 'nullable|date',
            'estimated_hours'     => 'nullable|numeric|min:0',
            'story_points'        => 'nullable|integer|min:0|max:100',
            'assignee_ids'        => 'nullable|array',
            'assignee_ids.*'      => 'exists:users,id',
            'label_ids'           => 'nullable|array',
            'label_ids.*'         => 'exists:labels,id',
            'severity'            => 'nullable|in:blocker,critical,major,minor,trivial',
            'steps_to_reproduce'  => 'nullable|string',
            'expected_behavior'   => 'nullable|string',
            'actual_behavior'     => 'nullable|string',
            'environment'         => 'nullable|string|max:255',
            'sprint_id'           => 'nullable|exists:sprints,id',
        ]);

        $project = Project::findOrFail($data['project_id']);
        $this->authorize('view', $project);

        if (empty($data['column_id'])) {
            $data['column_id'] = $project->columns()->where('is_default', true)->first()?->id
                ?? $project->columns()->orderBy('position')->first()?->id;
        }

        $task = $this->taskService->createTask($data, $request->user());

        return redirect()->route('projects.tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    public function projectData(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return response()->json([
            'members' => \App\Models\User::active()->orderBy('name')->get()->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'avatar' => $m->avatar_url]),
            'labels'  => $project->labels->map(fn($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]),
            'columns' => $project->columns()->orderBy('position')->get()->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
            'sprints' => $project->sprints()->whereIn('status', ['planning', 'active'])->get()->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'status' => $s->status]),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project)
    {
        $task = $this->taskService->createTask($request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json(['task' => $task->load(['assignees', 'labels', 'column'])]);
        }

        return redirect()->route('projects.tasks.show', [$project, $task])
            ->with('success', 'Task created.');
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'type'       => 'required|in:task,bug,feature,story,epic',
            'project_id' => 'required|exists:projects,id',
            'priority'   => 'required|in:low,medium,high,critical',
        ]);

        $project = \App\Models\Project::findOrFail($validated['project_id']);
        $this->authorize('view', $project);

        // Put it in the first column of the project (backlog / to-do column)
        $column = $project->columns()->orderBy('position')->first();

        $task = $this->taskService->createTask(array_merge($validated, [
            'column_id'  => $column?->id,
            'status'     => 'todo',
            'created_by' => $request->user()->id,
        ]), $request->user());

        return redirect()->back()->with('success', 'Task "' . $task->title . '" added.');
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        $task    = $this->taskRepository->findById($task->id);
        $columns = $task->project->columns()->orderBy('position')->get();
        $members = \App\Models\User::active()->orderBy('name')->get();
        $labels  = $task->project->labels;
        $sprints = $task->project->sprints()->whereIn('status', ['planning', 'active'])->get();

        return view('tasks.edit', compact('task', 'columns', 'members', 'labels', 'sprints'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $updated = $this->taskService->updateTask($task, $request->validated());

        if ($request->wantsJson()) {
            return response()->json(['task' => $updated]);
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $projectId = $task->project_id;
        $this->taskService->deleteTask($task);

        return redirect()->route('projects.tasks.index', $projectId)
            ->with('success', 'Task deleted.');
    }

    public function move(MoveTaskRequest $request, Task $task)
    {
        $column = Column::findOrFail($request->column_id);
        $this->taskService->moveTask($task, $column, $request->position);

        return response()->json(['success' => true]);
    }

    public function addAssignee(Request $request, Task $task)
    {
        $this->authorize('update', $task);
        $request->validate(['user_id' => 'required|exists:users,id']);

        $user       = \App\Models\User::findOrFail($request->user_id);
        $currentIds = $task->assignees()->pluck('users.id')->toArray();

        if (!in_array($user->id, $currentIds)) {
            $this->taskService->assignUsers($task, [...$currentIds, $user->id], $request->user());
        }

        return response()->json([
            'id'     => $user->id,
            'name'   => $user->name,
            'avatar' => $user->avatar_url,
        ]);
    }

    public function removeAssignee(Request $request, Task $task, \App\Models\User $user)
    {
        $this->authorize('update', $task);

        $newIds = $task->assignees()->pluck('users.id')->reject(fn ($id) => $id === $user->id)->values()->toArray();
        $this->taskService->assignUsers($task, $newIds, $request->user());

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $request->validate(['status' => 'required|in:todo,in_progress,in_review,done,cancelled']);

        $updated = $this->taskService->updateTaskStatus($task, $request->status);

        return response()->json(['task' => $updated]);
    }

    public function bugs(Request $request)
    {
        $user = auth()->user();

        $query = Task::with(['project', 'assignees', 'creator'])
            ->where('type', 'bug')
            ->whereHas('project', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('users.id', $user->id));
                if ($user->hasRole('admin')) {
                    $q->orWhereNotNull('id');
                }
            });

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $bugs = $query->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'todo' THEN 1 WHEN 'in_review' THEN 2 WHEN 'done' THEN 3 ELSE 4 END")
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->paginate(25);

        // Projects for filter
        $projects = \App\Models\Project::where('owner_id', $user->id)
            ->orWhereHas('members', fn($m) => $m->where('users.id', $user->id))
            ->orderBy('name')
            ->get();

        return view('bugs.index', compact('bugs', 'projects'));
    }
}
