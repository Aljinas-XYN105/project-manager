<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ReportService
{
    public function generateTimeReport(array $filters): array
    {
        $query = TimeEntry::with(['user', 'task', 'project'])
            ->whereNotNull('stopped_at');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['from'])) {
            $query->where('started_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('started_at', '<=', $filters['to'] . ' 23:59:59');
        }

        $entries = $query->orderByDesc('started_at')->get();

        return [
            'entries'           => $entries,
            'total_seconds'     => $entries->sum('duration_seconds'),
            'total_hours'       => round($entries->sum('duration_seconds') / 3600, 2),
            'billable_seconds'  => $entries->where('is_billable', true)->sum('duration_seconds'),
            'by_project'        => $entries->groupBy('project_id'),
            'by_user'           => $entries->groupBy('user_id'),
            'filters'           => $filters,
        ];
    }

    public function generateProjectReport(array $filters): array
    {
        $query = Project::with(['owner', 'members', 'tasks' => fn($q) => $q->whereNull('parent_id')]);

        if (!empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $projects = $query->get();

        return [
            'projects'          => $projects,
            'total_projects'    => $projects->count(),
            'completed'         => $projects->where('status', 'completed')->count(),
            'active'            => $projects->where('status', 'active')->count(),
            'overdue'           => $projects->filter->is_overdue->count(),
            'avg_progress'      => $projects->avg('progress_percentage'),
            'filters'           => $filters,
        ];
    }

    public function generateTeamReport(array $filters): array
    {
        $users = User::with([
            'assignedTasks' => fn($q) => $q->whereNull('parent_id'),
            'timeEntries' => fn($q) => !empty($filters['from'])
                ? $q->where('started_at', '>=', $filters['from'])
                : $q,
        ])->active()->get();

        return [
            'users'   => $users->map(fn($u) => [
                'user'             => $u,
                'total_tasks'      => $u->assignedTasks->count(),
                'done_tasks'       => $u->assignedTasks->where('status', 'done')->count(),
                'overdue_tasks'    => $u->assignedTasks->filter->is_overdue->count(),
                'tracked_seconds'  => $u->timeEntries->sum('duration_seconds'),
                'tracked_hours'    => round($u->timeEntries->sum('duration_seconds') / 3600, 2),
            ]),
            'filters' => $filters,
        ];
    }

    public function exportToPdf(string $view, array $data): string
    {
        $pdf  = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');
        $path = storage_path('app/exports/report_' . now()->format('Ymd_His') . '.pdf');

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);
        return $path;
    }
}
