<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly TimeTrackingService $timeTrackingService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $stats = Cache::remember("dashboard_stats_{$user->id}", 300, function () use ($user) {
            $myProjectIds = $user->projects()->pluck('projects.id')
                ->merge($user->ownedProjects()->pluck('id'))
                ->unique();

            return [
                'total_projects'  => $myProjectIds->count(),
                'active_projects' => Project::whereIn('id', $myProjectIds)->where('status', 'active')->count(),
                'total_tasks'     => Task::whereIn('project_id', $myProjectIds)->whereNull('parent_id')->count(),
                'my_tasks'        => $user->assignedTasks()->whereNotIn('status', ['done', 'cancelled'])->count(),
                'overdue_tasks'   => $user->assignedTasks()->overdue()->count(),
                'completed_today' => $user->assignedTasks()
                    ->where('status', 'done')
                    ->whereDate('completed_at', today())
                    ->count(),
            ];
        });

        $upcomingTasks = $user->assignedTasks()
            ->with(['project', 'labels'])
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $recentActivity = $this->activityLogService->getRecentLogs(15);

        $activeTimers = $this->timeTrackingService->getActiveEntries($user);
        $runningTimer = $activeTimers->firstWhere('status', 'running');

        $unreadCount = $this->notificationService->getUnreadCount($user);

        $myProjects = $user->projects()
            ->with(['owner', 'tasks'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        $announcements = \App\Models\Announcement::visible()->with('author')->limit(3)->get();

        $offToday = \App\Models\LeaveRequest::with(['user', 'type'])
            ->where('status', 'approved')->onDate(today()->toDateString())->get();

        $nextHoliday = \App\Models\Holiday::upcoming()->first();

        $celebrations = $this->upcomingCelebrations(14);

        $hasReport = (bool) $user->todayReport();

        return Inertia::render('Dashboard', [
            'stats'          => $stats,
            'upcomingTasks'  => $upcomingTasks->map(fn ($t) => [
                'id'        => $t->id,
                'title'     => $t->title,
                'status'    => $t->status,
                'due_date'  => $t->due_date?->toDateString(),
                'project'   => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
            ]),
            'recentActivity' => $recentActivity->map(fn ($a) => [
                'id'              => $a->id,
                'description'     => $a->description,
                'created_at_human'=> $a->created_at->diffForHumans(),
                'causer'          => $a->causer ? ['name' => $a->causer->name, 'avatar_url' => $a->causer->avatar_url] : null,
            ]),
            'activeTimers'   => $activeTimers->map(fn ($e) => [
                'id'              => $e->id,
                'status'          => $e->status,
                'task_title'      => $e->task?->title ?? $e->description ?? 'Untitled',
                'project_name'    => $e->task?->project?->name ?? '',
                'project_color'   => $e->task?->project?->color ?? '#6366f1',
                'started_at'      => $e->started_at?->toIso8601String(),
                'paused_seconds'  => $e->paused_seconds ?? 0,
                'elapsed_seconds' => $e->elapsed_seconds,
                'elapsed_formatted' => gmdate('H:i:s', max(0, $e->elapsed_seconds)),
                'pause_url'       => route('time-entries.pause', $e),
                'resume_url'      => route('time-entries.resume', $e),
                'stop_url'        => route('time-entries.stop', $e),
                'task_url'        => $e->task_id ? route('projects.tasks.show', $e->task_id) : null,
            ]),
            'myProjects'     => $myProjects->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'color'         => $p->color,
                'status'        => $p->status,
                'due_date'      => $p->due_date?->toDateString(),
                'total_tasks'   => $p->tasks->whereNull('parent_id')->count(),
                'done_tasks'    => $p->tasks->whereNull('parent_id')->where('status', 'done')->count(),
                'progress_pct'  => ($p->tasks->whereNull('parent_id')->count() > 0)
                    ? round($p->tasks->whereNull('parent_id')->where('status', 'done')->count() / $p->tasks->whereNull('parent_id')->count() * 100)
                    : 0,
                'members_count' => $p->members->count() + 1,
            ]),
            'announcements'  => $announcements->map(fn ($a) => [
                'id'            => $a->id,
                'title'         => $a->title,
                'body_excerpt'  => \Illuminate\Support\Str::limit(strip_tags($a->body), 110),
                'is_pinned'     => $a->is_pinned,
                'style'         => $a->style ?? [],
                'created_at_human' => $a->created_at->diffForHumans(),
                'author'        => $a->author ? ['name' => $a->author->name] : null,
            ]),
            'offToday'       => $offToday->map(fn ($l) => [
                'id'          => $l->id,
                'is_half_day' => $l->is_half_day,
                'user'        => $l->user ? ['name' => $l->user->name, 'avatar_url' => $l->user->avatar_url] : null,
                'type'        => $l->type ? ['name' => $l->type->name] : null,
            ]),
            'nextHoliday'    => $nextHoliday ? [
                'name'           => $nextHoliday->name,
                'date_formatted' => $nextHoliday->date->format('D, M j'),
            ] : null,
            'celebrations'   => $celebrations->map(fn ($c) => [
                'kind'       => $c->kind,
                'label'      => $c->label,
                'date_label' => $c->date->isToday() ? 'Today' : ($c->date->isTomorrow() ? 'Tomorrow' : $c->date->format('M j')),
                'user'       => $c->user ? ['name' => $c->user->name, 'avatar_url' => $c->user->avatar_url] : null,
            ]),
            'hasReport'      => $hasReport,
        ]);
    }

    /** Birthdays and work anniversaries (1+ years) in the next N days. */
    private function upcomingCelebrations(int $days)
    {
        $today = today();

        $next = function (?\Carbon\Carbon $date) use ($today) {
            if (! $date) {
                return null;
            }
            $occurs = $date->copy()->setYear($today->year);
            if ($occurs->lt($today)) {
                $occurs->addYear();
            }
            return $occurs;
        };

        return \App\Models\User::active()
            ->where(fn ($q) => $q->whereNotNull('date_of_birth')->orWhereNotNull('joined_on'))
            ->get()
            ->flatMap(function ($u) use ($next, $today, $days) {
                $items = [];
                if (($d = $next($u->date_of_birth)) && $d->diffInDays($today, true) <= $days) {
                    $items[] = (object) ['user' => $u, 'date' => $d, 'kind' => 'birthday', 'label' => 'Birthday'];
                }
                if ($u->joined_on && ($d = $next($u->joined_on)) && $d->diffInDays($today, true) <= $days && ($years = $d->year - $u->joined_on->year) >= 1) {
                    $items[] = (object) ['user' => $u, 'date' => $d, 'kind' => 'anniversary', 'label' => $years.' '.\Illuminate\Support\Str::plural('year', $years).' at the company'];
                }
                return $items;
            })
            ->sortBy('date')
            ->values();
    }
}
