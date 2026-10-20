<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DailyReportController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $reports = DailyReport::with('reportTasks.task')
            ->where('user_id', $user->id)
            ->orderByDesc('report_date')
            ->paginate(15);

        $todayReport = DailyReport::where('user_id', $user->id)
            ->where('report_date', today())
            ->with('reportTasks.task')
            ->first();

        return view('daily-reports.index', compact('reports', 'todayReport'));
    }

    public function team(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Only admins and managers can view team reports.');
        }

        $date  = $request->input('date', today()->toDateString());
        $userId = $request->input('user_id');

        $query = DailyReport::with(['user', 'reportTasks.task'])
            ->orderByDesc('report_date')
            ->orderBy('user_id');

        if ($date) {
            $query->where('report_date', $date);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $reports = $query->paginate(30);
        $users   = User::active()->orderBy('name')->get();

        return view('daily-reports.team', compact('reports', 'users', 'date', 'userId'));
    }

    public function create()
    {
        $user  = auth()->user();
        $today = today()->toDateString();

        // Redirect if already submitted today
        $existing = DailyReport::where('user_id', $user->id)->where('report_date', $today)->first();
        if ($existing) {
            return redirect()->route('daily-reports.edit', $existing)
                ->with('info', 'You already submitted a report today. You can edit it below.');
        }

        // Tasks assigned to this user that are in progress or not done
        $myTasks = Task::with(['project'])
            ->whereHas('assignees', fn($q) => $q->where('users.id', $user->id))
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $projects = $this->reportableProjects($user);

        return view('daily-reports.create', compact('myTasks', 'today', 'projects'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate(['report_date' => 'required|date'] + $this->rules());
        $this->authorizeNewTasks($validated['tasks'] ?? [], $user);

        DB::transaction(function () use ($validated, $user) {
            $report = DailyReport::updateOrCreate(
                ['user_id' => $user->id, 'report_date' => $validated['report_date']],
                [
                    'summary'           => $validated['summary'],
                    'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? null,
                    'blockers'          => $validated['blockers'] ?? null,
                    'total_hours'       => collect($validated['tasks'] ?? [])->sum('hours'),
                ]
            );

            $this->syncReportTasks($report, $validated['tasks'] ?? [], $user);
        });

        return redirect()->route('daily-reports.index')
            ->with('success', 'Daily report submitted successfully!');
    }

    public function show(DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);
        $dailyReport->load(['user', 'reportTasks.task.project']);
        return view('daily-reports.show', compact('dailyReport'));
    }

    public function edit(DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);

        $user = auth()->user();
        $myTasks = Task::with(['project'])
            ->whereHas('assignees', fn($q) => $q->where('users.id', $user->id))
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $dailyReport->load('reportTasks.task');
        $projects = $this->reportableProjects($user);

        return view('daily-reports.edit', compact('dailyReport', 'myTasks', 'projects'));
    }

    public function update(Request $request, DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);

        $validated = $request->validate($this->rules());
        $this->authorizeNewTasks($validated['tasks'] ?? [], $request->user());

        DB::transaction(function () use ($validated, $dailyReport, $request) {
            $dailyReport->update([
                'summary'           => $validated['summary'],
                'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? null,
                'blockers'          => $validated['blockers'] ?? null,
                'total_hours'       => collect($validated['tasks'] ?? [])->sum('hours'),
            ]);

            $this->syncReportTasks($dailyReport, $validated['tasks'] ?? [], $request->user());
        });

        return redirect()->route('daily-reports.show', $dailyReport)
            ->with('success', 'Report updated.');
    }

    public function destroy(DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);
        $dailyReport->delete();
        return redirect()->route('daily-reports.index')->with('success', 'Report deleted.');
    }

    private function rules(): array
    {
        return [
            'summary'                    => 'required|string|max:5000',
            'plan_for_tomorrow'          => 'nullable|string|max:3000',
            'blockers'                   => 'nullable|string|max:2000',
            'tasks'                      => 'nullable|array',
            'tasks.*.task_id'            => 'nullable|integer|exists:tasks,id',
            'tasks.*.description'        => 'required_with:tasks|string|max:500',
            'tasks.*.hours'              => 'required_with:tasks|numeric|min:0|max:24',
            'tasks.*.notes'              => 'nullable|string|max:500',
            'tasks.*.status_update'      => 'nullable|string|in:todo,in_progress,in_review,done,blocked',
            'tasks.*.is_blocked'         => 'nullable|boolean',
            'tasks.*.new_task'           => 'nullable|array',
            'tasks.*.new_task.project_id'=> 'required_with:tasks.*.new_task|integer|exists:projects,id',
            'tasks.*.new_task.title'     => 'required_with:tasks.*.new_task|string|max:255',
            'tasks.*.new_task.type'      => 'nullable|in:task,bug,feature,story',
            'tasks.*.new_task.priority'  => 'nullable|in:low,medium,high,critical',
        ];
    }

    private function authorizeNewTasks(array $rows, User $user): void
    {
        foreach ($rows as $i => $row) {
            if (! empty($row['new_task']) && ! Gate::forUser($user)->allows('view', Project::find($row['new_task']['project_id']))) {
                throw ValidationException::withMessages([
                    "tasks.{$i}.new_task.project_id" => 'You do not have access to that project.',
                ]);
            }
        }
    }

    private function syncReportTasks(DailyReport $report, array $rows, User $user): void
    {
        $report->reportTasks()->delete();

        foreach ($rows as $row) {
            $taskId = $row['task_id'] ?? null;
            $status = $row['status_update'] ?? null;

            if (! $taskId && ! empty($row['new_task'])) {
                $taskId = $this->createTaskFromReport($row['new_task'], $status, $user)->id;
            } elseif ($taskId && $status && $status !== 'blocked') {
                Task::where('id', $taskId)->update(['status' => $status]);
            }

            $report->reportTasks()->create([
                'task_id'          => $taskId,
                'task_description' => $row['description'],
                'hours_spent'      => $row['hours'],
                'notes'            => $row['notes'] ?? null,
                'status_update'    => $status,
                'is_blocked'       => ! empty($row['is_blocked']),
            ]);
        }
    }

    private function createTaskFromReport(array $data, ?string $status, User $user): Task
    {
        $project = Project::findOrFail($data['project_id']);
        $column  = $project->columns()->where('is_default', true)->first() ?? $project->columns()->orderBy('position')->first();

        return $this->taskService->createTask([
            'project_id'   => $project->id,
            'column_id'    => $column?->id,
            'title'        => $data['title'],
            'type'         => $data['type'] ?? 'task',
            'priority'     => $data['priority'] ?? 'medium',
            'status'       => $status && $status !== 'blocked' ? $status : 'in_progress',
            'assignee_ids' => [$user->id],
        ], $user);
    }

    private function reportableProjects(User $user)
    {
        return Project::query()
            ->where('status', '!=', 'archived')
            ->when(! $user->hasRole('admin'), fn ($q) => $q->where(fn ($q) => $q
                ->where('is_public', true)
                ->orWhere('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function authorizeReport(DailyReport $report): void
    {
        $user = auth()->user();
        if ($report->user_id !== $user->id && !$user->hasAnyRole(['admin', 'manager'])) {
            abort(403);
        }
    }
}
