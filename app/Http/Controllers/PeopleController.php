<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;

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

        return view('people.index', [
            'people'       => $people,
            'departments'  => User::active()->whereNotNull('department')->select('department')->distinct()->orderBy('department')->pluck('department'),
            'onLeaveToday' => $onLeaveToday,
        ]);
    }

    public function orgChart()
    {
        $people = User::active()->get(['id', 'name', 'job_title', 'department', 'manager_id', 'avatar', 'email']);
        $byManager = $people->toBase()->groupBy(fn ($u) => $u->manager_id ?? 0);
        $ids = $people->pluck('id')->all();

        // Roots: nobody above them, or their manager is inactive/removed.
        $roots = $people->filter(fn ($u) => ! $u->manager_id || ! in_array($u->manager_id, $ids))->sortBy('name')->values();

        return view('people.org-chart', compact('roots', 'byManager'));
    }
}
