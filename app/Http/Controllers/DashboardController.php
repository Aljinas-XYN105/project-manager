<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        $runningTimer = $this->timeTrackingService->getRunningEntry($user);

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

        return view('dashboard.index', compact(
            'stats', 'upcomingTasks', 'recentActivity',
            'runningTimer', 'unreadCount', 'myProjects',
            'announcements', 'offToday', 'nextHoliday', 'celebrations'
        ));
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
