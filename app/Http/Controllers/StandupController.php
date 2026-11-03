<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class StandupController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->hasRole(['admin', 'manager'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $members = User::active()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $open = Task::whereHas('assignees', fn ($q) => $q->where('users.id', $u->id))
                    ->whereNull('parent_id')
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->count();

                $inProgress = Task::whereHas('assignees', fn ($q) => $q->where('users.id', $u->id))
                    ->whereNull('parent_id')
                    ->where('status', 'in_progress')
                    ->count();

                return [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'avatar'      => $u->avatar_url,
                    'job_title'   => $u->job_title,
                    'department'  => $u->department,
                    'role'        => $u->roles->first()?->name ?? 'member',
                    'open'        => $open,
                    'in_progress' => $inProgress,
                ];
            });

        return view('standups.index', compact('members'));
    }

    public function show(User $user)
    {
        $tasks = Task::with(['project', 'column', 'assignees', 'labels'])
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))
            ->whereNull('parent_id')
            ->whereNotIn('status', ['cancelled'])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'in_review' THEN 1 WHEN 'todo' THEN 2 WHEN 'done' THEN 3 ELSE 4 END")
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->get();

        // Group by column name (reflects actual Kanban position), falling back to status
        $laneOrder = ['To Do', 'In Progress', 'In Review', 'QA Testing', 'Done'];
        $laneColors = [
            'To Do'       => '#94a3b8',
            'In Progress' => '#3b82f6',
            'In Review'   => '#f59e0b',
            'QA Testing'  => '#8b5cf6',
            'Done'        => '#10b981',
        ];

        $statusToLane = [
            'todo'        => 'To Do',
            'in_progress' => 'In Progress',
            'in_review'   => 'In Review',
            'done'        => 'Done',
        ];

        $lanes = collect($laneOrder)->mapWithKeys(fn ($name) => [$name => collect()]);

        foreach ($tasks as $task) {
            $laneName = $task->column?->name ?? ($statusToLane[$task->status] ?? 'To Do');
            if (! isset($lanes[$laneName])) {
                $lanes[$laneName] = collect();
            }
            $lanes[$laneName]->push($task);
        }

        return view('standups.show', compact('user', 'lanes', 'laneColors', 'laneOrder'));
    }

    // Called via PATCH /tasks/{task}/standup-move
    // Moves task to the named lane/column in its own project, syncs status
    public function moveTask(Request $request, Task $task)
    {
        $request->validate(['lane' => 'required|string|max:100']);

        $lane = $request->input('lane');

        // Find the matching column in this task's project
        $column = Column::where('project_id', $task->project_id)
            ->where('name', $lane)
            ->first();

        $laneToStatus = [
            'To Do'       => 'todo',
            'In Progress' => 'in_progress',
            'In Review'   => 'in_review',
            'QA Testing'  => 'in_review', // closest status
            'Done'        => 'done',
        ];

        $updates = ['status' => $laneToStatus[$lane] ?? $task->status];

        if ($column) {
            $updates['column_id'] = $column->id;
            // Place at end of that column
            $updates['position'] = Task::where('column_id', $column->id)->max('position') + 1;

            if ($column->is_done) {
                $updates['status']       = 'done';
                $updates['completed_at'] = $updates['completed_at'] ?? now();
            }
        }

        $task->update($updates);

        return response()->json(['success' => true, 'task' => $task->fresh(['column', 'project'])]);
    }
}
