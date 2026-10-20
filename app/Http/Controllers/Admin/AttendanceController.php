<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->filled('from') ? $request->date('from') : now($request->user()->timezone)->startOfWeek();
        $to   = $request->filled('to') ? $request->date('to') : now($request->user()->timezone);

        $records = Attendance::with(['user', 'editor'])
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->orderByDesc('work_date')
            ->orderByDesc('clock_in_at')
            ->paginate(50)
            ->withQueryString();

        $users = User::active()->orderBy('name')->get();

        // Live board: everyone, with today's first clock-in and current state
        $localToday   = Attendance::localToday($request->user());
        $todayRecords = Attendance::whereDate('work_date', $localToday)->get()->groupBy('user_id');
        $onLeave = \App\Models\LeaveRequest::with('type')->where('status', 'approved')->onDate($localToday)->get()->keyBy('user_id');
        $board = $users->map(fn (User $u) => (object) [
            'user'         => $u,
            'leave'        => $onLeave->get($u->id),
            'status'       => $u->effective_availability,
            'clocked_in'   => (bool) $todayRecords->get($u->id)?->firstWhere('clock_out_at', null),
            'first_in'     => $todayRecords->get($u->id)?->min('clock_in_at'),
            'last_out'     => $todayRecords->get($u->id)?->whereNotNull('clock_out_at')->max('clock_out_at'),
            'minutes'      => $todayRecords->get($u->id)?->sum('duration_minutes') ?? 0,
        ])->sortBy(fn ($r) => [$r->status === 'offline' ? 1 : 0, $r->user->name])->values();

        $stats = [
            'online'  => $board->where('status', '!=', 'offline')->count(),
            'present' => $todayRecords->count(),
            'absent'  => $users->filter(fn ($u) => ! $todayRecords->has($u->id) && ! $onLeave->has($u->id))->count(),
            'total'   => $users->count(),
        ];

        return view('admin.attendance.index', compact('records', 'users', 'board', 'stats', 'from', 'to'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Attendance::create($data + ['source' => 'admin', 'edited_by' => $request->user()->id]);

        return back()->with('success', 'Attendance entry added.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $attendance->update($this->validated($request) + ['edited_by' => $request->user()->id]);

        return back()->with('success', 'Attendance entry updated.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return back()->with('success', 'Attendance entry deleted.');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'user_id'   => ['required', 'exists:users,id'],
            'work_date' => ['required', 'date'],
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
            'note'      => ['nullable', 'string', 'max:500'],
        ]);

        // Times are typed in the admin's own timezone and stored as UTC.
        $tz = $request->user()->timezone ?: config('app.timezone');

        return [
            'user_id'      => $v['user_id'],
            'work_date'    => $v['work_date'],
            'clock_in_at'  => Carbon::parse($v['work_date'].' '.$v['clock_in'], $tz)->utc(),
            'clock_out_at' => $v['clock_out'] ? Carbon::parse($v['work_date'].' '.$v['clock_out'], $tz)->utc() : null,
            'note'         => $v['note'] ?? null,
        ];
    }
}
