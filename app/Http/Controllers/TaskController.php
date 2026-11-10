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
use Inertia\Inertia;

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

        return Inertia::render('Tasks/Index', [
            'project' => [
                'id'         => $project->id,
                'name'       => $project->name,
                'show_url'   => route('projects.show', $project),
                'create_url' => route('projects.tasks.create', $project),
                'kanban_url' => route('projects.tasks.kanban', $project),
                'calendar_url' => route('projects.tasks.calendar', $project),
                'backlog_url'  => route('projects.tasks.backlog', $project),
                'index_url'    => route('projects.tasks.index', $project),
            ],
            'tasks' => $tasks->through(fn ($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'ticket_key'  => $t->ticket_key,
                'status'      => $t->status,
                'priority'    => $t->priority,
                'type'        => $t->type,
                'type_icon'   => $t->type_icon,
                'type_color'  => $t->type_color,
                'due_date'    => $t->due_date?->format('M j'),
                'is_overdue'  => $t->is_overdue,
                'assignees'   => $t->assignees->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'avatar_url' => $a->avatar_url]),
                'show_url'    => route('projects.tasks.show', $t),
                'edit_url'    => route('tasks.edit', $t),
                'destroy_url' => route('tasks.destroy', $t),
            ]),
            'members' => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'filters' => $request->only(['status', 'priority', 'assignee', 'search']),
        ]);
    }

    public function kanban(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $columns = $this->taskService->getKanbanBoard($project->id);
        $members = \App\Models\User::active()->orderBy('name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]);
        $labels  = $project->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]);

        $serializedColumns = collect($columns)->map(fn ($col) => [
            'id'    => $col['id'],
            'name'  => $col['name'],
            'color' => $col['color'] ?? null,
            'wip_limit' => $col['wip_limit'] ?? null,
            'tasks' => collect($col['tasks'] ?? [])->map(fn ($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'ticket_key'  => $t->ticket_key,
                'type'        => $t->type,
                'type_icon'   => $t->type_icon,
                'type_color'  => $t->type_color,
                'priority'    => $t->priority,
                'status'      => $t->status,
                'due_date'    => $t->due_date?->format('M j'),
                'is_overdue'  => $t->is_overdue,
                'assignees'   => $t->assignees->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'avatar_url' => $a->avatar_url]),
                'show_url'    => route('projects.tasks.show', $t),
                'edit_url'    => route('tasks.edit', $t),
                'move_url'    => route('tasks.move', $t),
            ])->values(),
        ])->values();

        return Inertia::render('Tasks/Kanban', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'color'       => $project->color,
                'show_url'    => route('projects.show', $project),
                'index_url'   => route('projects.tasks.index', $project),
                'backlog_url' => route('projects.tasks.backlog', $project),
                'calendar_url'=> route('projects.tasks.calendar', $project),
                'create_url'  => route('projects.tasks.create', $project),
                'store_url'   => route('projects.tasks.store', $project),
            ],
            'columns' => $serializedColumns,
            'members' => $members,
            'labels'  => $labels,
        ]);
    }

    public function calendar(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $tasks = $this->taskService->getCalendarData($project->id, $year, $month);

        $serializedTasks = collect($tasks)->map(fn ($t) => [
            'id'         => $t->id,
            'title'      => $t->title,
            'ticket_key' => $t->ticket_key,
            'type_icon'  => $t->type_icon,
            'type_color' => $t->type_color,
            'priority'   => $t->priority,
            'status'     => $t->status,
            'due_date'   => $t->due_date?->format('Y-m-d'),
            'is_overdue' => $t->is_overdue,
            'show_url'   => route('projects.tasks.show', $t),
        ])->values();

        return Inertia::render('Tasks/Calendar', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'show_url'    => route('projects.show', $project),
                'index_url'   => route('projects.tasks.index', $project),
                'kanban_url'  => route('projects.tasks.kanban', $project),
                'backlog_url' => route('projects.tasks.backlog', $project),
                'create_url'  => route('projects.tasks.create', $project),
            ],
            'tasks' => $serializedTasks,
            'year'  => $year,
            'month' => $month,
        ]);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task = $this->taskRepository->findById($task->id);
        $user = auth()->user();

        $members = \App\Models\User::active()->orderBy('name')->get();

        $activityLogs = ActivityLog::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();

        $timeEntries = $task->timeEntries ?? collect();

        return Inertia::render('Tasks/Show', [
            'task' => [
                'id'                  => $task->id,
                'title'               => $task->title,
                'description'         => $task->description,
                'type'                => $task->type,
                'type_icon'           => $task->type_icon,
                'type_color'          => $task->type_color,
                'priority'            => $task->priority,
                'status'              => $task->status,
                'ticket_key'          => $task->ticket_key,
                'is_overdue'          => $task->is_overdue,
                'due_date'            => $task->due_date?->format('Y-m-d'),
                'estimated_hours'     => $task->estimated_hours,
                'story_points'        => $task->story_points,
                'severity'            => $task->severity,
                'environment'         => $task->environment,
                'steps_to_reproduce'  => $task->steps_to_reproduce,
                'expected_behavior'   => $task->expected_behavior,
                'actual_behavior'     => $task->actual_behavior,
                'created_at'          => $task->created_at->diffForHumans(),
                'project'             => [
                    'id'       => $task->project->id,
                    'name'     => $task->project->name,
                    'show_url' => route('projects.show', $task->project),
                    'store_subtask_url' => route('projects.tasks.store', $task->project),
                ],
                'creator'  => $task->creator ? ['name' => $task->creator->name] : null,
                'sprint'   => $task->sprint ? [
                    'id'       => $task->sprint->id,
                    'name'     => $task->sprint->name,
                    'show_url' => route('projects.sprints.show', [$task->project, $task->sprint]),
                ] : null,
                'assignees' => $task->assignees->map(fn ($a) => [
                    'id' => $a->id, 'name' => $a->name, 'avatar_url' => $a->avatar_url,
                ]),
                'labels' => $task->labels->map(fn ($l) => [
                    'id' => $l->id, 'name' => $l->name, 'color' => $l->color,
                ]),
                'subtasks' => ($task->subtasks ?? collect())->map(fn ($s) => [
                    'id'       => $s->id,
                    'title'    => $s->title,
                    'status'   => $s->status,
                    'show_url' => route('projects.tasks.show', $s),
                ]),
                'comments' => ($task->comments ?? collect())->map(fn ($c) => [
                    'id'         => $c->id,
                    'body'       => $c->body,
                    'created_at' => $c->created_at->diffForHumans(),
                    'user'       => $c->user ? ['name' => $c->user->name, 'avatar_url' => $c->user->avatar_url] : null,
                ]),
                'attachments' => ($task->attachments ?? collect())->map(fn ($a) => [
                    'id'             => $a->id,
                    'original_name'  => $a->original_name,
                    'mime_type'      => $a->mime_type,
                    'size_formatted' => $a->size_formatted,
                    'is_image'       => $a->is_image,
                    'is_video'       => $a->is_video,
                    'url'            => $a->url,
                    'uploader_name'  => $a->user?->name ?? '—',
                    'created_ago'    => $a->created_at->diffForHumans(),
                    'destroy_url'    => route('attachments.destroy', $a),
                ]),
                'time_entries' => $timeEntries->map(fn ($e) => [
                    'id'       => $e->id,
                    'started'  => $e->started_at?->format('M j, g:ia'),
                    'duration' => $e->duration_formatted ?? ($e->duration_minutes ? floor($e->duration_minutes/60).'h '.($e->duration_minutes%60).'m' : '—'),
                    'user'     => $e->user?->name ?? '',
                ])->values(),
                'total_logged_minutes' => $timeEntries->sum('duration_minutes'),
                'column_id' => $task->column_id,
                'parent_id' => $task->parent_id,
                'parent' => $task->parent ? [
                    'id'         => $task->parent->id,
                    'title'      => $task->parent->title,
                    'ticket_key' => $task->parent->ticket_key,
                    'show_url'   => route('projects.tasks.show', $task->parent),
                ] : null,
                // URLs
                'update_url'           => route('tasks.update', $task),
                'destroy_url'          => route('tasks.destroy', $task),
                'status_url'           => route('tasks.status', $task),
                'assignees_add_url'    => route('tasks.assignees.add', $task),
                'assignees_remove_base'=> url('tasks/'.$task->id.'/assignees'),
                'attachments_store_url'=> route('attachments.store', $task),
                'comments_store_url'   => route('comments.store'),
                'edit_url'             => route('tasks.edit', $task),
            ],
            'members'    => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'avatar_url' => $m->avatar_url]),
            'activity'   => $activityLogs->map(fn ($l) => [
                'id'          => $l->id,
                'description' => $l->description,
                'created_at'  => $l->created_at->diffForHumans(),
                'causer'      => $l->causer ? ['name' => $l->causer->name] : null,
            ]),
            'can_update' => $user->can('update', $task),
            'can_delete' => $user->can('delete', $task),
        ]);
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

        return Inertia::render('Tasks/Backlog', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'show_url'    => route('projects.show', $project),
                'index_url'   => route('projects.tasks.index', $project),
                'kanban_url'  => route('projects.tasks.kanban', $project),
                'create_url'  => route('projects.tasks.create', $project),
                'store_url'   => route('projects.tasks.store', $project),
            ],
            'tasks' => $tasks->map(fn ($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'ticket_key'  => $t->ticket_key,
                'type'        => $t->type,
                'type_icon'   => $t->type_icon,
                'type_color'  => $t->type_color,
                'priority'    => $t->priority,
                'status'      => $t->status,
                'due_date'    => $t->due_date?->format('M j'),
                'is_overdue'  => $t->is_overdue,
                'column'      => $t->column ? ['id' => $t->column->id, 'name' => $t->column->name] : null,
                'assignees'   => $t->assignees->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'avatar_url' => $a->avatar_url]),
                'labels'      => $t->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]),
                'show_url'    => route('projects.tasks.show', $t),
                'edit_url'    => route('tasks.edit', $t),
                'destroy_url' => route('tasks.destroy', $t),
                'move_url'    => route('tasks.move', $t),
            ]),
            'columns' => $columns->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ]);
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
        $sprints = $project->sprints()->whereIn('status', ['planning', 'active'])->get();

        return Inertia::render('Tasks/Create', [
            'project' => [
                'id'        => $project->id,
                'name'      => $project->name,
                'show_url'  => route('projects.show', $project),
                'store_url' => route('projects.tasks.store', $project),
                'index_url' => route('projects.tasks.index', $project),
            ],
            'column'  => $column ? ['id' => $column->id, 'name' => $column->name] : null,
            'columns' => $project->columns()->orderBy('position')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'members' => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'avatar_url' => $m->avatar_url]),
            'labels'  => $labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]),
            'sprints' => $sprints->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);
    }

    public function createGlobal(Request $request)
    {
        $user = $request->user();
        $projectQuery = Project::with(['columns', 'members', 'labels'])->orderBy('name');

        if (! $user->can('tasks.view-all')) {
            $projectQuery->where(fn ($q) =>
                $q->whereHas('members', fn ($m) => $m->where('users.id', $user->id))
                  ->orWhere('owner_id', $user->id)
            );
        }

        $projects = $projectQuery->get();
        $defaultProject = $projects->firstWhere('id', $request->query('project_id')) ?? $projects->first();

        return Inertia::render('Tasks/CreateGlobal', [
            'projects' => $projects->map(fn ($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'store_url' => route('projects.tasks.store', $p),
                'columns'   => $p->columns->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'is_default' => $c->is_default]),
                'members'   => $p->members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
                'labels'    => $p->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]),
            ]),
            'default_project_id' => $defaultProject?->id,
            'store_global_url'   => route('tasks.store'),
            'members_all'        => \App\Models\User::active()->orderBy('name')->get()->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'avatar_url' => $m->avatar_url]),
        ]);
    }

    public function storeGlobal(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'project_id'          => 'required|exists:projects,id',
            'column_id'           => 'nullable|exists:columns,id',
            'type'                => 'nullable|in:task,bug,feature,story,epic',
            'status'              => 'nullable|in:todo,in_progress,qa_testing,in_review,done,cancelled',
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

    public function panelData(Task $task)
    {
        $this->authorize('view', $task);
        $task->load(['project', 'column', 'assignees', 'labels', 'attachments.user']);

        return response()->json([
            'task'    => [
                'id'              => $task->id,
                'title'           => $task->title,
                'description'     => $task->description,
                'type'            => $task->type,
                'priority'        => $task->priority,
                'status'          => $task->status,
                'column_id'       => $task->column_id,
                'due_date'        => $task->due_date?->format('Y-m-d'),
                'estimated_hours' => $task->estimated_hours,
                'ticket_key'      => $task->ticket_key,
                'show_url'        => route('projects.tasks.show', $task),
                'edit_url'        => route('tasks.edit', $task),
                'project_name'    => $task->project->name,
                'assignee_ids'    => $task->assignees->pluck('id'),
                'label_ids'       => $task->labels->pluck('id'),
                'type_icon'       => $task->type_icon,
                'type_color'      => $task->type_color,
                'is_overdue'      => $task->is_overdue,
            ],
            'columns' => $task->project->columns()->orderBy('position')->get(['id','name','color']),
            'members' => \App\Models\User::active()->orderBy('name')->get()->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'avatar' => $u->avatar_url,
            ]),
            'labels'      => $task->project->labels()->get(['id','name','color']),
            'attachments' => $task->attachments->map(fn ($a) => [
                'id'             => $a->id,
                'original_name'  => $a->original_name,
                'mime_type'      => $a->mime_type,
                'size_formatted' => $a->size_formatted,
                'is_image'       => $a->is_image,
                'is_video'       => $a->is_video,
                'url'            => $a->url,
                'uploader_name'  => $a->user->name,
            ]),
        ]);
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'type'             => 'required|in:task,bug,feature,story,epic',
            'project_id'       => 'required|exists:projects,id',
            'priority'         => 'required|in:low,medium,high,critical',
            'description'      => 'nullable|string|max:5000',
            'due_date'         => 'nullable|date',
            'estimated_hours'  => 'nullable|numeric|min:0',
            'assignee_ids'     => 'nullable|array',
            'assignee_ids.*'   => 'integer|exists:users,id',
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

        if ($request->wantsJson()) {
            return response()->json([
                'ok'         => true,
                'task_id'    => $task->id,
                'ticket_key' => $task->ticket_key,
                'title'      => $task->title,
                'status'     => $task->status,
            ]);
        }

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

        return Inertia::render('Tasks/Edit', [
            'task' => [
                'id'                 => $task->id,
                'title'              => $task->title,
                'description'        => $task->description,
                'type'               => $task->type,
                'priority'           => $task->priority,
                'status'             => $task->status,
                'due_date'           => $task->due_date?->format('Y-m-d'),
                'estimated_hours'    => $task->estimated_hours,
                'story_points'       => $task->story_points,
                'severity'           => $task->severity,
                'environment'        => $task->environment,
                'steps_to_reproduce' => $task->steps_to_reproduce,
                'expected_behavior'  => $task->expected_behavior,
                'actual_behavior'    => $task->actual_behavior,
                'column_id'          => $task->column_id,
                'sprint_id'          => $task->sprint_id,
                'assignee_ids'       => $task->assignees->pluck('id'),
                'label_ids'          => $task->labels->pluck('id'),
                'ticket_key'         => $task->ticket_key,
                'show_url'           => route('projects.tasks.show', $task),
                'update_url'         => route('tasks.update', $task),
                'project'            => ['id' => $task->project->id, 'name' => $task->project->name, 'show_url' => route('projects.show', $task->project)],
            ],
            'columns' => $columns->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'members' => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'avatar_url' => $m->avatar_url]),
            'labels'  => $labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color]),
            'sprints' => $sprints->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'status' => $s->status]),
        ]);
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

        $request->validate(['status' => 'required|in:todo,in_progress,qa_testing,in_review,done,cancelled']);

        $updated = $this->taskService->updateTaskStatus($task, $request->status);

        return response()->json(['task' => $updated]);
    }

    public function bugs(Request $request)
    {
        $user = auth()->user();

        $isPrivileged = $user->can('tasks.view-all');

        $query = Task::with(['project', 'assignees', 'creator'])
            ->where('type', 'bug');

        if (!$isPrivileged) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('users.id', $user->id))
                  ->orWhere('is_public', true);
            });
        }

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

        $bugs = $query->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'qa_testing' THEN 1 WHEN 'in_review' THEN 2 WHEN 'todo' THEN 3 WHEN 'done' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->paginate(25);

        // Stats strip — same privilege scope as the main query
        $statsQuery = Task::where('type', 'bug');
        if (!$isPrivileged) {
            $statsQuery->whereHas('project', function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('users.id', $user->id))
                  ->orWhere('is_public', true);
            });
        }
        $bugsByStatus = $statsQuery->selectRaw('status, count(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        // Projects for filter
        $projectsQuery = \App\Models\Project::query();
        if (!$isPrivileged) {
            $projectsQuery->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('users.id', $user->id))
                  ->orWhere('is_public', true);
            });
        }
        $projects = $projectsQuery->orderBy('name')->get();

        return Inertia::render('Bugs/Index', [
            'bugs' => $bugs->through(fn ($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'ticket_key'  => $t->ticket_key,
                'status'      => $t->status,
                'priority'    => $t->priority,
                'severity'    => $t->severity,
                'type_icon'   => $t->type_icon,
                'type_color'  => $t->type_color,
                'created_at'  => $t->created_at->diffForHumans(),
                'assignees'   => $t->assignees->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'avatar_url' => $a->avatar_url]),
                'project'     => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
                'creator'     => $t->creator ? ['name' => $t->creator->name] : null,
                'show_url'    => route('projects.tasks.show', $t),
            ]),
            'projects'     => $projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'bugs_by_status' => $bugsByStatus,
            'filters'      => $request->only(['status', 'severity', 'priority', 'project_id']),
        ]);
    }
}
