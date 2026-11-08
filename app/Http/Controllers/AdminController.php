<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        $typeConfig = [
            'task'    => ['icon' => 'bi-check-square-fill', 'color' => 'bg-slate-400',   'label' => 'Task'],
            'bug'     => ['icon' => 'bi-bug-fill',          'color' => 'bg-rose-500',    'label' => 'Bug'],
            'feature' => ['icon' => 'bi-stars',             'color' => 'bg-violet-500',  'label' => 'Feature'],
            'story'   => ['icon' => 'bi-bookmark-fill',     'color' => 'bg-blue-500',    'label' => 'Story'],
            'epic'    => ['icon' => 'bi-lightning-fill',    'color' => 'bg-amber-500',   'label' => 'Epic'],
        ];

        return Inertia::render('Admin/Dashboard', [
            'period'          => $period,
            'total_users'     => $totalUsers,
            'active_users'    => $activeUsers,
            'total_projects'  => $totalProjects,
            'active_projects' => $activeProjects,
            'total_tasks'     => $totalTasks,
            'done_tasks'      => $doneTasks,
            'overdue_tasks'   => $overdueTasks,
            'total_hours'     => number_format($totalHours, 1),
            'daily_hours'     => $dailyHours->values(),
            'task_types'      => collect($typeConfig)->map(fn ($tc, $key) => [
                'key'   => $key,
                'label' => $tc['label'],
                'icon'  => $tc['icon'],
                'color' => $tc['color'],
                'count' => $taskTypes->get($key, 0),
                'pct'   => $taskTypes->sum() > 0 ? round($taskTypes->get($key, 0) / $taskTypes->sum() * 100) : 0,
            ])->values(),
            'employees' => $employees->map(fn ($emp) => [
                'name'             => $emp->user?->name ?? '—',
                'avatar_url'       => $emp->user?->avatar_url ?? asset('images/default-avatar.png'),
                'role'             => $emp->user?->getRoleNames()?->first() ?? 'member',
                'hours_logged'     => $emp->hours_logged,
                'completed_tasks'  => $emp->completed_tasks,
                'open_tasks'       => $emp->open_tasks,
                'overdue_tasks'    => $emp->overdue_tasks,
                'daily_reports'    => $emp->daily_reports,
                'performance_pct'  => $emp->performance_pct,
                'employee_url'     => $emp->user ? route('admin.employee', $emp->user) : null,
            ])->values(),
            'projects' => $projects->map(fn ($p) => [
                'name'          => $p->project?->name ?? '—',
                'color'         => $p->project?->color ?? '#6366f1',
                'show_url'      => $p->project ? route('projects.show', $p->project) : null,
                'total_tasks'   => $p->total_tasks,
                'done_tasks'    => $p->done_tasks,
                'progress'      => $p->progress,
                'estimated_hrs' => $p->estimated_hrs,
                'logged_hrs'    => $p->logged_hrs,
                'burn_rate'     => $p->burn_rate,
                'overdue_tasks' => $p->overdue_tasks,
            ])->values(),
            'recent_reports' => $recentReports->map(fn ($r) => [
                'id'          => $r->id,
                'user_name'   => $r->user?->name ?? '—',
                'avatar_url'  => $r->user?->avatar_url ?? asset('images/default-avatar.png'),
                'report_date' => $r->report_date->format('M j, Y'),
                'total_hours' => $r->total_hours,
                'summary'     => \Illuminate\Support\Str::limit($r->summary, 120),
                'has_blocker' => (bool) $r->blockers,
                'show_url'    => route('daily-reports.show', $r),
            ])->values(),
            'users_url'   => route('users.index'),
            'reports_url' => route('daily-reports.team'),
        ]);
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

        $statusColors = ['todo' => 'bg-slate-300', 'in_progress' => 'bg-blue-500', 'qa_testing' => 'bg-violet-500', 'in_review' => 'bg-amber-500', 'done' => 'bg-emerald-500'];

        return Inertia::render('Admin/Employee', [
            'period'       => $period,
            'total_hours'  => $totalHours,
            'admin_url'    => route('admin.dashboard'),
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar_url' => $user->avatar_url,
                'role'       => $user->getRoleNames()->first() ?? 'member',
                'show_url'   => route('users.show', $user),
            ],
            'kpi' => [
                'completed' => $completedTasks->count(),
                'open'      => $openTasks->count(),
                'reports'   => $dailyReports->count(),
            ],
            'project_hours'    => $projectHours->map(fn ($hrs, $name) => ['name' => $name, 'hours' => $hrs])->values(),
            'daily_breakdown'  => $dailyBreakdown->map(fn ($hrs, $date) => ['date' => $date, 'day' => (int) \Carbon\Carbon::parse($date)->format('j'), 'hours' => $hrs])->values(),
            'open_tasks'       => $openTasks->map(fn ($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'project'     => $t->project?->name ?? '',
                'priority'    => $t->priority ?? 'medium',
                'status'      => $t->status,
                'status_dot'  => $statusColors[$t->status] ?? 'bg-slate-300',
                'due_label'   => $t->due_date ? ($t->due_date->isPast() ? 'Overdue ' . $t->due_date->diffForHumans() : 'Due ' . $t->due_date->format('M j')) : null,
                'is_overdue'  => $t->due_date && $t->due_date->isPast(),
                'show_url'    => route('projects.tasks.show', $t),
            ])->values(),
            'daily_reports'    => $dailyReports->map(fn ($r) => [
                'id'          => $r->id,
                'day'         => $r->report_date->format('j'),
                'month'       => $r->report_date->format('M'),
                'summary'     => \Illuminate\Support\Str::limit($r->summary, 80),
                'task_count'  => $r->reportTasks->count(),
                'total_hours' => $r->total_hours,
                'has_blocker' => (bool) $r->blockers,
                'show_url'    => route('daily-reports.show', $r),
            ])->values(),
        ]);
    }
}
