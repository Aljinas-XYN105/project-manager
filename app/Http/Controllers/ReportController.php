<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReportSnapshot;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        return view('reports.index', compact('stats', 'snapshots'));
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

        return view('reports.time-report', compact('from', 'to', 'entries', 'summary', 'byProject', 'projects', 'users'));
    }

    public function projectReport(Request $request)
    {
        $query = Project::withCount('tasks');

        if ($request->filled('status'))  $query->where('status',  $request->status);
        if ($request->filled('team_id')) $query->where('team_id', $request->team_id);

        $projects = $query->orderBy('name')->paginate(20);
        $teams    = Team::orderBy('name')->get();

        return view('reports.project-report', compact('projects', 'teams'));
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

        return view('reports.team-report', compact('members', 'teams', 'from', 'to'));
    }
}
