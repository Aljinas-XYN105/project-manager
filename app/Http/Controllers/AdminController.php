<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\DailyReport;
use Carbon\Carbon;
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
        $since  = now()->subDays((int)$period);

        // Global stats
        $totalUsers    = User::count();
        $activeUsers   = User::whereHas('timeEntries', fn($q) => $q->where('started_at', '>=', $since))->count();
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $totalTasks    = Task::whereNull('parent_id')->count();
        $doneTasks     = Task::whereNull('parent_id')->where('status', 'done')->count();
        $overdueTasks  = Task::whereNull('parent_id')->whereNotIn('status', ['done','cancelled'])
                             ->whereNotNull('due_date')->where('due_date', '<', today())->count();

        // Total hours logged in period
        $totalHours = TimeEntry::where('started_at', '>=', $since)
                               ->whereNotNull('stopped_at')
                               ->get()
                               ->sum(fn($e) => $e->duration_hours ?? 0);

        // Employee performance table
        $employees = User::with(['assignedTasks', 'timeEntries'])
            ->withCount([
                'assignedTasks as open_tasks'      => fn($q) => $q->whereNotIn('tasks.status', ['done','cancelled']),
                'assignedTasks as completed_tasks' => fn($q) => $q->where('tasks.status', 'done')->where('tasks.updated_at', '>=', $since),
                'assignedTasks as overdue_tasks'   => fn($q) => $q->whereNotIn('tasks.status', ['done','cancelled'])
                                                                    ->whereNotNull('tasks.due_date')->where('tasks.due_date', '<', today()),
            ])
            ->get()
            ->map(function ($user) use ($since) {
                $hours = $user->timeEntries()
                    ->where('started_at', '>=', $since)
                    ->whereNotNull('stopped_at')
                    ->get()
                    ->sum(fn($e) => $e->duration_hours ?? 0);

                $dailyReportsCount = $user->dailyReports()
                    ->where('report_date', '>=', $since->toDateString())
                    ->count();

                return (object)[
                    'user'             => $user,
                    'hours_logged'     => round($hours, 1),
                    'open_tasks'       => $user->open_tasks,
                    'completed_tasks'  => $user->completed_tasks,
                    'overdue_tasks'    => $user->overdue_tasks,
                    'daily_reports'    => $dailyReportsCount,
                    'performance_pct'  => $user->open_tasks + $user->completed_tasks > 0
                        ? round($user->completed_tasks / ($user->open_tasks + $user->completed_tasks) * 100)
                        : 0,
                ];
            })
            ->sortByDesc('hours_logged');

        // Project burn rates
        $projects = Project::with(['tasks', 'timeEntries'])
            ->where('status', 'active')
            ->get()
            ->map(function ($project) use ($since) {
                $totalTasks     = $project->tasks()->whereNull('parent_id')->count();
                $doneTasks      = $project->tasks()->whereNull('parent_id')->where('status', 'done')->count();
                $estimated      = $project->tasks()->whereNull('parent_id')->sum('estimated_hours') ?: 0;
                $logged         = $project->timeEntries()
                    ->where('started_at', '>=', $since)
                    ->whereNotNull('stopped_at')
                    ->get()
                    ->sum(fn($e) => $e->duration_hours ?? 0);

                $progress = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
                $burnRate = $estimated > 0 ? round($logged / $estimated * 100) : null;

                return (object)[
                    'project'        => $project,
                    'total_tasks'    => $totalTasks,
                    'done_tasks'     => $doneTasks,
                    'progress'       => $progress,
                    'estimated_hrs'  => round($estimated, 1),
                    'logged_hrs'     => round($logged, 1),
                    'burn_rate'      => $burnRate,
                    'overdue_tasks'  => $project->tasks()->whereNull('parent_id')
                                           ->whereNotIn('status', ['done','cancelled'])
                                           ->whereNotNull('due_date')->where('due_date', '<', today())->count(),
                ];
            })
            ->sortByDesc('logged_hrs');

        // Daily hours chart (last 14 days)
        $dailyHours = collect(range(13, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            $hours = TimeEntry::whereDate('started_at', $date)
                               ->whereNotNull('stopped_at')
                               ->get()
                               ->sum(fn($e) => $e->duration_hours ?? 0);
            return ['date' => $date, 'label' => now()->subDays($daysAgo)->format('M j'), 'hours' => round($hours, 1)];
        });

        // Recent daily reports
        $recentReports = DailyReport::with('user')
            ->orderByDesc('report_date')
            ->limit(10)
            ->get();

        // Task type breakdown
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
        $since  = now()->subDays((int)$period);

        $timeEntries = $user->timeEntries()
            ->with('task.project')
            ->where('started_at', '>=', $since)
            ->whereNotNull('stopped_at')
            ->orderByDesc('started_at')
            ->get();

        $totalHours = round($timeEntries->sum(fn($e) => $e->duration_hours ?? 0), 1);

        $taskStats = $user->assignedTasks()
            ->with('project')
            ->select('tasks.*', DB::raw('count(*) as cnt'))
            ->get();

        $openTasks      = $user->assignedTasks()->with('project')->whereNotIn('status', ['done','cancelled'])->get();
        $completedTasks = $user->assignedTasks()->with('project')->where('status', 'done')
                              ->where('updated_at', '>=', $since)->get();
        $dailyReports   = $user->dailyReports()->with('reportTasks.task')->where('report_date', '>=', $since->toDateString())->get();

        // Hours per day
        $dailyBreakdown = $timeEntries->groupBy(fn($e) => $e->started_at->toDateString())
            ->map(fn($entries) => round($entries->sum(fn($e) => $e->duration_hours ?? 0), 1))
            ->sortKeys();

        // Hours per project
        $projectHours = $timeEntries->groupBy(fn($e) => $e->project?->name ?? 'Unknown')
            ->map(fn($entries) => round($entries->sum(fn($e) => $e->duration_hours ?? 0), 1))
            ->sortDesc();

        return view('admin.employee', compact(
            'user', 'period', 'totalHours', 'timeEntries',
            'openTasks', 'completedTasks', 'dailyReports',
            'dailyBreakdown', 'projectHours'
        ));
    }
}
