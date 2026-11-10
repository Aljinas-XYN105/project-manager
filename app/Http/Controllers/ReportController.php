<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Project;
use App\Models\ReportSnapshot;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index()
    {
        $stats = [
            'total_hours'    => round(TimeEntry::whereNotNull('stopped_at')
                ->whereMonth('started_at', now()->month)->sum('duration_seconds') / 3600, 1),
            'tasks_completed'=> \App\Models\Task::where('status', 'done')
                ->whereMonth('updated_at', now()->month)->count(),
            'active_projects'=> Project::where('status', 'active')->count(),
            'team_members'   => User::where('is_active', true)->count(),
        ];

        $snapshots = ReportSnapshot::where('user_id', auth()->id())
            ->latest()->limit(10)->get();

        return Inertia::render('Reports/Index', [
            'stats'     => $stats,
            'snapshots' => $snapshots->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name ?? 'Report',
                'created_at' => $s->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function timeReport(Request $request)
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->format('Y-m-d')));
        $to   = Carbon::parse($request->input('to',   now()->format('Y-m-d')));

        $query = TimeEntry::with(['user', 'task', 'project'])
            ->whereNotNull('stopped_at')
            ->whereBetween('started_at', [$from->startOfDay(), $to->copy()->endOfDay()]);

        if ($request->filled('project_id')) $query->where('project_id', $request->project_id);
        if ($request->filled('user_id'))    $query->where('user_id',    $request->user_id);

        if ($request->filled('export')) {
            $entries = $query->orderByDesc('started_at')->get();
            $data    = ['entries' => $entries, 'from' => $from, 'to' => $to];
            $path    = $this->reportService->exportToPdf('reports.time-report', $data);
            return response()->download($path)->deleteFileAfterSend();
        }

        // Aggregate in DB — avoids loading all rows into memory
        $summary = [
            'total_hours'    => round((clone $query)->sum('duration_seconds') / 3600, 1),
            'billable_hours' => round((clone $query)->where('is_billable', true)->sum('duration_seconds') / 3600, 1),
            'entry_count'    => (clone $query)->count(),
        ];

        $entries = $query->orderByDesc('started_at')->paginate(25);

        $byProject = TimeEntry::query()
            ->join('projects', 'time_entries.project_id', '=', 'projects.id')
            ->selectRaw('projects.id, projects.name as project_name, projects.color, sum(duration_seconds)/3600 as hours')
            ->whereNotNull('stopped_at')
            ->whereBetween('started_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->when($request->filled('project_id'), fn($q) => $q->where('time_entries.project_id', $request->project_id))
            ->when($request->filled('user_id'), fn($q) => $q->where('time_entries.user_id', $request->user_id))
            ->groupBy('projects.id', 'projects.name', 'projects.color')
            ->orderByDesc('hours')
            ->get();

        $projects = Project::orderBy('name')->get();
        $users    = User::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Reports/TimeReport', [
            'from'     => $from->format('Y-m-d'),
            'to'       => $to->format('Y-m-d'),
            'summary'  => $summary,
            'by_project' => $byProject->map(fn ($r) => [
                'name'  => $r->project_name,
                'color' => $r->color ?? '#6366f1',
                'hours' => round($r->hours, 1),
                'pct'   => $summary['total_hours'] > 0 ? round($r->hours / $summary['total_hours'] * 100) : 0,
            ])->values(),
            'entries'  => $entries->through(fn ($e) => [
                'id'               => $e->id,
                'date'             => $e->started_at->format('M j'),
                'user_name'        => $e->user?->name ?? '—',
                'user_avatar'      => $e->user?->avatar_url ?? '',
                'project'          => $e->project?->name ?? '—',
                'task'             => $e->task?->title ?? 'Manual entry',
                'duration'         => $e->duration_formatted ?? '',
                'is_billable'      => $e->is_billable,
            ]),
            'projects' => $projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            'users'    => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'filters'  => $request->only('from', 'to', 'project_id', 'user_id'),
            'export_url' => route('reports.time'),
        ]);
    }

    public function projectReport(Request $request)
    {
        $query = Project::withCount('tasks');

        if ($request->filled('status'))  $query->where('status',  $request->status);
        if ($request->filled('team_id')) $query->where('team_id', $request->team_id);

        $projects = $query->orderBy('name')->paginate(20);
        $teams    = Team::orderBy('name')->get();

        $statusClasses = [
            'planning'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
            'active'    => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
            'on_hold'   => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
            'completed' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
            'archived'  => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-500',
        ];

        return Inertia::render('Reports/ProjectReport', [
            'projects' => $projects->through(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'color'       => $p->color ?? '#6366f1',
                'initial'     => strtoupper(substr($p->name, 0, 1)),
                'status'      => $p->status,
                'status_label'=> ucfirst(str_replace('_', ' ', $p->status)),
                'status_class'=> $statusClasses[$p->status] ?? $statusClasses['planning'],
                'team'        => $p->team?->name ?? 'No team',
                'progress'    => $p->progress_percentage ?? 0,
                'tasks_count' => $p->tasks_count,
                'hours_logged'=> 0,
                'due_date'    => $p->due_date?->format('M j, Y'),
                'show_url'    => route('projects.show', $p),
            ]),
            'teams'   => $teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'filters' => $request->only('status', 'team_id'),
        ]);
    }

    public function teamReport(Request $request)
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->format('Y-m-d')));
        $to   = Carbon::parse($request->input('to',   now()->format('Y-m-d')));

        $members = User::where('is_active', true)
            ->withCount([
                'assignedTasks as assigned_count',
                'assignedTasks as completed_count' => fn($q) => $q->where('status', 'done'),
                'assignedTasks as overdue_count'   => fn($q) => $q->overdue(),
            ])
            ->with(['timeEntries' => fn($q) => $q->whereBetween('started_at', [$from, $to])->whereNotNull('stopped_at')])
            ->when($request->filled('team_id'), fn($q) => $q->whereHas('teams', fn($tq) => $tq->where('teams.id', $request->team_id)))
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->hours_logged = round($user->timeEntries->sum('duration_seconds') / 3600, 1);
                return $user;
            });

        $teams = Team::orderBy('name')->get();

        return Inertia::render('Reports/TeamReport', [
            'from'    => $from->format('Y-m-d'),
            'to'      => $to->format('Y-m-d'),
            'members' => $members->map(fn ($m) => [
                'id'              => $m->id,
                'name'            => $m->name,
                'avatar_url'      => $m->avatar_url,
                'job_title'       => $m->job_title ?? $m->email,
                'assigned_count'  => $m->assigned_count,
                'completed_count' => $m->completed_count,
                'overdue_count'   => $m->overdue_count,
                'hours_logged'    => $m->hours_logged,
                'completion_rate' => $m->assigned_count > 0 ? round($m->completed_count / $m->assigned_count * 100) : 0,
                'show_url'        => route('users.show', $m),
            ])->values(),
            'teams'   => $teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'filters' => $request->only('from', 'to', 'team_id'),
        ]);
    }

    public function leaveReport(Request $request)
    {
        $from = Carbon::parse($request->input('from', now()->startOfYear()->format('Y-m-d')));
        $to   = Carbon::parse($request->input('to',   now()->format('Y-m-d')));

        $query = LeaveRequest::with(['user', 'type', 'reviewer'])
            ->whereBetween('start_date', [$from, $to]);

        if ($request->filled('user_id'))    $query->where('user_id', $request->user_id);
        if ($request->filled('status'))     $query->where('status',  $request->status);
        if ($request->filled('type_id'))    $query->where('leave_type_id', $request->type_id);

        $summary = [
            'total'    => (clone $query)->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'pending'  => (clone $query)->where('status', 'pending')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'days'     => (clone $query)->where('status', 'approved')->sum('days'),
        ];

        if ($request->filled('export')) {
            $leaves = (clone $query)->orderByDesc('start_date')->get();
            $data   = compact('leaves', 'summary', 'from', 'to');
            $path   = $this->reportService->exportToPdf('reports.pdf.leave', $data);
            return response()->download($path, 'leave-report-'.$from->format('Y-m-d').'.pdf')->deleteFileAfterSend();
        }

        $leaves    = $query->orderByDesc('start_date')->paginate(25)->withQueryString();
        $users     = User::where('is_active', true)->orderBy('name')->get();
        $leaveTypes = LeaveType::orderBy('name')->get();

        return Inertia::render('Reports/LeaveReport', [
            'from'    => $from->format('Y-m-d'),
            'to'      => $to->format('Y-m-d'),
            'summary' => $summary,
            'leaves'  => $leaves->through(fn ($l) => [
                'id'          => $l->id,
                'user_name'   => $l->user?->name ?? '—',
                'avatar_url'  => $l->user?->avatar_url ?? '',
                'type'        => $l->type?->name ?? '—',
                'type_color'  => $l->type?->color ?? 'slate',
                'days'        => $l->days,
                'date_range'  => $l->date_range,
                'status'      => $l->status,
                'status_label'=> ucfirst($l->status),
                'reviewer'    => $l->reviewer?->name,
                'reviewed_at' => $l->reviewed_at?->diffForHumans(),
            ]),
            'users'       => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'leave_types' => $leaveTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'filters'     => $request->only('from', 'to', 'user_id', 'status', 'type_id'),
            'export_url'  => route('reports.leave'),
        ]);
    }

    public function expenseReport(Request $request)
    {
        $from = Carbon::parse($request->input('from', now()->startOfYear()->format('Y-m-d')));
        $to   = Carbon::parse($request->input('to',   now()->format('Y-m-d')));

        $query = Expense::with(['user', 'reviewer'])
            ->whereBetween('expense_date', [$from, $to]);

        if ($request->filled('user_id'))  $query->where('user_id',  $request->user_id);
        if ($request->filled('status'))   $query->where('status',   $request->status);
        if ($request->filled('category')) $query->where('category', $request->category);

        $summary = [
            'total_count' => (clone $query)->count(),
            'approved'    => (clone $query)->where('status', 'approved')->count(),
            'pending'     => (clone $query)->where('status', 'pending')->count(),
            'rejected'    => (clone $query)->where('status', 'rejected')->count(),
            'total_amount'=> (clone $query)->where('status', 'approved')->sum('amount'),
            'by_category' => (clone $query)->where('status', 'approved')
                ->selectRaw('category, sum(amount) as total')
                ->groupBy('category')->orderByDesc('total')->get(),
        ];

        if ($request->filled('export')) {
            $expenses = (clone $query)->orderByDesc('expense_date')->get();
            $data     = compact('expenses', 'summary', 'from', 'to');
            $path     = $this->reportService->exportToPdf('reports.pdf.expense', $data);
            return response()->download($path, 'expense-report-'.$from->format('Y-m-d').'.pdf')->deleteFileAfterSend();
        }

        $expenses   = $query->orderByDesc('expense_date')->paginate(25)->withQueryString();
        $users      = User::where('is_active', true)->orderBy('name')->get();
        $categories = Expense::CATEGORIES;

        return Inertia::render('Reports/ExpenseReport', [
            'from'    => $from->format('Y-m-d'),
            'to'      => $to->format('Y-m-d'),
            'summary' => array_merge($summary, [
                'by_category' => $summary['by_category']->map(fn ($r) => [
                    'category' => $r->category,
                    'total'    => number_format($r->total, 2),
                ])->values(),
            ]),
            'expenses' => $expenses->through(fn ($e) => [
                'id'          => $e->id,
                'user_name'   => $e->user?->name ?? '—',
                'avatar_url'  => $e->user?->avatar_url ?? '',
                'category'    => $e->category,
                'amount'      => number_format($e->amount, 2),
                'expense_date'=> $e->expense_date->format('M j, Y'),
                'description' => $e->description,
                'status'      => $e->status,
                'status_label'=> ucfirst($e->status),
                'reviewer'    => $e->reviewer?->name,
            ]),
            'users'      => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'categories' => is_array($categories) ? array_keys($categories) : (is_object($categories) ? array_keys($categories->toArray()) : []),
            'filters'    => $request->only('from', 'to', 'user_id', 'status', 'category'),
            'export_url' => route('reports.expenses'),
        ]);
    }
}
