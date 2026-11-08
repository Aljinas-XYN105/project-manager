<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use App\Models\Conversation;
use App\Models\Expense;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'roles'      => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'info'    => fn () => $request->session()->get('info'),
            ],
            'appName' => config('app.name'),
            'navBadges' => fn () => $user ? $this->navBadges($user) : [],
            'unreadChatCount' => fn () => $user ? $this->unreadChatCount($user) : 0,
            'presence' => fn () => $user ? $this->presenceData($user) : null,
        ];
    }

    private function navBadges($user): array
    {
        return [
            'leave' => LeaveRequest::where('status', 'pending')
                ->where('user_id', '!=', $user->id)
                ->when(! ($user->hasRole('admin') || $user->can('leave.approve')),
                    fn ($q) => $q->whereIn('user_id', $user->directReports()->pluck('id')))
                ->count(),
            'expenses' => Expense::where('status', 'pending')
                ->where('user_id', '!=', $user->id)
                ->when(! $user->can('expenses.review'),
                    fn ($q) => $q->whereIn('user_id', $user->directReports()->pluck('id')))
                ->count(),
        ];
    }

    private function unreadChatCount($user): int
    {
        return Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->get()
            ->sum(fn ($c) => $c->unreadCountFor($user));
    }

    private function presenceData($user): array
    {
        $openShift = $user->openAttendance();
        $workedToday = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', Attendance::localToday($user))
            ->whereNotNull('clock_out_at')
            ->get()
            ->sum('duration_minutes');

        return [
            'statuses'      => \App\Models\User::AVAILABILITY,
            'availability'  => $user->availability ?: 'available',
            'clockedIn'     => (bool) $openShift,
            'clockInAt'     => $openShift?->clock_in_at?->toIso8601String(),
            'workedMinutes' => $workedToday,
        ];
    }
}
