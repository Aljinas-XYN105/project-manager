<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyTasksController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $isManager = $user->hasRole(['admin', 'manager']);
        $view = $request->input('view', 'open');
        $scope = $request->input('scope', 'mine'); // 'mine' or 'all' (managers only)

        $assigneeId = $isManager && $request->filled('assignee_id') ? $request->integer('assignee_id') : null;

        if ($isManager && ($scope === 'all' || $assigneeId)) {
            $baseQuery = Task::with(['project', 'assignees', 'labels'])->whereNull('parent_id');
        } else {
            $baseQuery = $user->assignedTasks()->with(['project', 'labels']);
        }

        $tasks = $baseQuery
            ->when($view === 'open', fn ($q) => $q->whereNotIn('tasks.status', ['done', 'cancelled']))
            ->when($view === 'done', fn ($q) => $q->where('tasks.status', 'done'))
            ->when($request->filled('project_id'), fn ($q) => $q->where('tasks.project_id', $request->integer('project_id')))
            ->when($request->filled('priority'), fn ($q) => $q->where('tasks.priority', $request->input('priority')))
            ->when($request->filled('type'), fn ($q) => $q->where('tasks.type', $request->input('type')))
            ->when($assigneeId, fn ($q) => $q->whereHas('assignees', fn ($aq) => $aq->where('users.id', $assigneeId)))
            ->orderByRaw('tasks.due_date is null')
            ->orderBy('tasks.due_date')
            ->orderByRaw("case tasks.priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 else 3 end")
            ->get();

        if ($view === 'done') {
            $groups = ['Completed' => $tasks->sortByDesc('completed_at')->take(100)];
        } else {
            $today = today();
            $groups = [
                'Overdue'     => $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->lt($today)),
                'Today'       => $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->isSameDay($today)),
                'This week'   => $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->gt($today) && $t->due_date->lte($today->copy()->endOfWeek())),
                'Later'       => $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->gt($today->copy()->endOfWeek())),
                'No due date' => $tasks->filter(fn (Task $t) => ! $t->due_date),
            ];
        }

        $counts = [
            'open' => $user->assignedTasks()->whereNotIn('tasks.status', ['done', 'cancelled'])->count(),
            'done' => $user->assignedTasks()->where('tasks.status', 'done')->count(),
        ];

        if ($isManager) {
            $counts['all_open'] = Task::whereNull('parent_id')->whereNotIn('status', ['done', 'cancelled'])->count();
            $counts['all_done'] = Task::whereNull('parent_id')->where('status', 'done')->count();
        }

        $projects = $isManager && ($scope === 'all' || $assigneeId)
            ? \App\Models\Project::orderBy('name')->get()
            : $user->assignedTasks()->with('project')->get()->pluck('project')->filter()->unique('id')->sortBy('name');

        $members = $isManager ? User::active()->orderBy('name')->get() : collect();

        $serializeTask = fn (Task $t) => [
            'id'          => $t->id,
            'title'       => $t->title,
            'ticket_key'  => $t->ticket_key,
            'status'      => $t->status,
            'priority'    => $t->priority,
            'type'        => $t->type,
            'type_icon'   => $t->type_icon,
            'type_color'  => $t->type_color,
            'due_date'    => $t->due_date?->format('M j'),
            'due_date_raw'=> $t->due_date?->toDateString(),
            'is_overdue'  => $t->is_overdue,
            'project'     => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
            'assignees'   => $t->assignees ? $t->assignees->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'avatar_url' => $a->avatar_url]) : [],
            'show_url'    => route('projects.tasks.show', $t),
            'status_url'  => route('tasks.status', $t),
        ];

        $serializedGroups = collect($groups)->map(fn ($groupTasks) => $groupTasks->values()->map($serializeTask))->toArray();

        return Inertia::render('MyTasks/Index', [
            'groups'     => $serializedGroups,
            'view'       => $view,
            'scope'      => $scope,
            'counts'     => $counts,
            'isManager'  => $isManager,
            'assigneeId' => $assigneeId,
            'projects'   => $projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'members'    => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]),
            'filters'    => $request->only(['project_id', 'priority', 'type', 'assignee_id']),
        ]);
    }
}
