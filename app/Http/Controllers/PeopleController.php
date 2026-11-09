<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $people = User::active()
            ->with('manager:id,name')
            ->withCount('directReports')
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->input('department')))
            ->when($request->filled('q'), fn ($q) => $q->search($request->input('q')))
            ->orderBy('name')
            ->get();

        $onLeaveToday = LeaveRequest::where('status', 'approved')->onDate(today()->toDateString())->pluck('user_id')->all();

        $statuses = User::AVAILABILITY;

        return Inertia::render('People/Index', [
            'people'       => $people->map(fn ($u) => [
                'id'               => $u->id,
                'name'             => $u->name,
                'email'            => $u->email,
                'phone'            => $u->phone,
                'job_title'        => $u->job_title,
                'department'       => $u->department,
                'avatar_url'       => $u->avatar_url,
                'direct_reports_count' => $u->direct_reports_count,
                'manager'          => $u->manager ? ['id' => $u->manager->id, 'name' => $u->manager->name] : null,
                'is_on_leave'      => in_array($u->id, $onLeaveToday),
                'dot_class'        => ($statuses[$u->effective_availability] ?? $statuses['offline'])['dot'],
                'show_url'         => route('users.show', $u),
            ]),
            'departments'  => User::active()->whereNotNull('department')->select('department')->distinct()->orderBy('department')->pluck('department'),
            'on_leave_today' => $onLeaveToday,
            'filters'      => ['department' => $request->input('department'), 'q' => $request->input('q')],
            'org_chart_url'=> route('people.org-chart'),
        ]);
    }

    public function orgChart()
    {
        $people = User::active()->get(['id', 'name', 'job_title', 'department', 'manager_id', 'avatar', 'email']);
        $byManager = $people->toBase()->groupBy(fn ($u) => $u->manager_id ?? 0);
        $ids = $people->pluck('id')->all();

        // Roots: nobody above them, or their manager is inactive/removed.
        $roots = $people->filter(fn ($u) => ! $u->manager_id || ! in_array($u->manager_id, $ids))->sortBy('name')->values();

        $serializeNode = null;
        $serializeNode = function ($u) use ($byManager, &$serializeNode) {
            return [
                'id'          => $u->id,
                'name'        => $u->name,
                'job_title'   => $u->job_title,
                'department'  => $u->department,
                'avatar_url'  => $u->avatar ?? null,
                'email'       => $u->email,
                'show_url'    => route('users.show', $u),
                'children'    => ($byManager->get($u->id) ?? collect())->sortBy('name')->map($serializeNode)->values(),
            ];
        };

        return Inertia::render('People/OrgChart', [
            'tree'       => $roots->map($serializeNode)->values(),
            'index_url'  => route('people.index'),
        ]);
    }
}
