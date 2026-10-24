<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->hasRole('admin')) {
                abort(403, 'Access denied. Admin only.');
            }
            return $next($request);
        });
    }

    public function dashboard(Request $request)
    {
        $period = $request->get('period', '30');
        $since  = now()->subDays((int) $period);

        // Global stats — all in separate, lean DB queries
        $totalUsers     = User::count();
        $activeUsers    = User::whereHas('timeEntries', fn ($q) => $q->where('started_at', '>=', $since))->count();
        $totalProjects  = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $totalTasks     = Task::whereNull('parent_id')->count();
        $doneTasks      = Task::whereNull('parent_id')->where('status', 'done')->count();
        $overdueTasks   = Task::whereNull('parent_id')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')->where('due_date', '<', today())->count();

        // Single DB sum — no in-memory collection
        $totalHours = round(
            TimeEntry::where('started_at', '>=', $since)->whereNotNull('stopped_at')->sum('duration_seconds') / 3600,
            1
        );

        // Employee performance — constrained eager loads eliminate N+1
        $employees = User::withCount([
                'assignedTasks as open_tasks'      => fn ($q) => $q->whereNotIn('tasks.status', ['done', 'cancelled']),
                'assignedTasks as completed_tasks'  => fn ($q) => $q->where('tasks.status', 'done')->where('tasks.updated_at', '>=', $since),
                'assignedTasks as overdue_tasks'    => fn ($q) => $q->whereNotIn('tasks.status', ['done', 'cancelled'])
                                                                    ->whereNotNull('tasks.due_date')->where('tasks.due_date', '<', today()),
                'dailyReports as daily_reports_count' => fn ($q) => $q->where('report_date', '>=', $since->toDateString()),
            ])
            ->withSum(
                ['timeEntries as time_seconds' => fn ($q) => $q->where('started_at', '>=', $since)->whereNotNull('stopped_at')],
                'duration_seconds'
            )
            ->get()
            ->map(fn ($user) => (object) [
                'user'            => $user,
                'hours_logged'    => round(($user->time_seconds ?? 0) / 3600, 1),
                'open_tasks'      => $user->open_tasks,
                'completed_tasks' => $user->completed_tasks,
                'overdue_tasks'   => $user->overdue_tasks,
                'daily_reports'   => $user->daily_reports_count,
                'performance_pct' => ($user->open_tasks + $user->completed_tasks) > 0
                    ? round($user->completed_tasks / ($user->open_tasks + $user->completed_tasks) * 100)
                    : 0,
            ])
            ->sortByDesc('hours_logged');

        // Project burn rates — aggregate in DB, not in PHP loops
        $projectTaskStats = Task::whereNull('parent_id')
            ->whereIn('project_id', Project::where('status', 'active')->pluck('id'))
            ->select(
                'project_id',
                DB::raw('count(*) as total_tasks'),
                DB::raw("sum(case when status = 'done' then 1 else 0 end) as done_tasks"),
                DB::raw("sum(estimated_hours) as estimated_hours"),
                DB::raw("sum(case when status not in ('done','cancelled') and due_date is not null and due_date < date('now') then 1 else 0 end) as overdue_tasks")
            )
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $projectTimeStats = TimeEntry::where('started_at', '>=', $since)
            ->whereNotNull('stopped_at')
            ->whereIn('project_id', Project::where('status', 'active')->pluck('id'))
            ->select('project_id', DB::raw('sum(duration_seconds) as logged_seconds'))
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $projects = Project::where('status', 'active')->get()
            ->map(function ($project) use ($projectTaskStats, $projectTimeStats) {
                $ts        = $projectTaskStats->get($project->id);
                $logged    = ($projectTimeStats->get($project->id)?->logged_seconds ?? 0) / 3600;
                $totalT    = $ts?->total_tasks ?? 0;
                $doneT     = $ts?->done_tasks ?? 0;
                $estimated = $ts?->estimated_hours ?? 0;

                return (object) [
                    'project'       => $project,
                    'total_tasks'   => $totalT,
                    'done_tasks'    => $doneT,
                    'progress'      => $totalT > 0 ? round($doneT / $totalT * 100) : 0,
                    'estimated_hrs' => round($estimated, 1),
                    'logged_hrs'    => round($logged, 1),
                    'burn_rate'     => $estimated > 0 ? round($logged / $estimated * 100) : null,
                    'overdue_tasks' => $ts?->overdue_tasks ?? 0,
                ];
            })
            ->sortByDesc('logged_hrs');

        // Daily hours chart — single GROUP BY query instead of 14 separate queries
        $cutoff = now()->subDays(13)->startOfDay();
        $rawDaily = TimeEntry::whereNotNull('stopped_at')
            ->where('started_at', '>=', $cutoff)
            ->select(DB::raw("date(started_at) as day"), DB::raw('sum(duration_seconds) as seconds'))
            ->groupBy('day')
            ->pluck('seconds', 'day');

        $dailyHours = collect(range(13, 0))->map(function ($daysAgo) use ($rawDaily) {
            $d = now()->subDays($daysAgo);
            return [
                'date'  => $d->toDateString(),
                'label' => $d->format('M j'),
                'hours' => round(($rawDaily->get($d->toDateString(), 0)) / 3600, 1),
            ];
        });

        $recentReports = DailyReport::with('user')->orderByDesc('report_date')->limit(10)->get();

        $taskTypes = Task::whereNull('parent_id')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        return view('admin.dashboard', compact(
            'totalUsers', 'activeUsers', 'totalProjects', 'activeProjects',
            'totalTasks', 'doneTasks', 'overdueTasks', 'totalHours',
            'employees', 'projects', 'dailyHours', 'recentReports', 'taskTypes', 'period'
        ));
    }

    public function employee(Request $request, User $user)
    {
        $period = $request->get('period', '30');
        $since  = now()->subDays((int) $period);

        $timeEntries = $user->timeEntries()
            ->with('task.project')
            ->where('started_at', '>=', $since)
            ->whereNotNull('stopped_at')
            ->orderByDesc('started_at')
            ->get();

        $totalHours     = round($timeEntries->sum('duration_seconds') / 3600, 1);
        $openTasks      = $user->assignedTasks()->with('project')->whereNotIn('status', ['done', 'cancelled'])->get();
        $completedTasks = $user->assignedTasks()->with('project')->where('status', 'done')->where('updated_at', '>=', $since)->get();
        $dailyReports   = $user->dailyReports()->with('reportTasks.task')->where('report_date', '>=', $since->toDateString())->get();

        $dailyBreakdown = $timeEntries
            ->groupBy(fn ($e) => $e->started_at->toDateString())
            ->map(fn ($entries) => round($entries->sum('duration_seconds') / 3600, 1))
            ->sortKeys();

        $projectHours = $timeEntries
            ->groupBy(fn ($e) => $e->task?->project?->name ?? 'Unknown')
            ->map(fn ($entries) => round($entries->sum('duration_seconds') / 3600, 1))
            ->sortDesc();

        return view('admin.employee', compact(
            'user', 'period', 'totalHours', 'timeEntries',
            'openTasks', 'completedTasks', 'dailyReports',
            'dailyBreakdown', 'projectHours'
        ));
    }
}
