<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $tz = $request->user()->timezone ?: config('app.timezone');
        $fmt = fn ($m) => sprintf('%dh %02dm', intdiv($m, 60), $m % 60);
        $statuses = User::AVAILABILITY;

        return Inertia::render('Admin/Attendance/Index', [
            'records' => $records->through(fn ($r) => [
                'id'        => $r->id,
                'user_id'   => $r->user_id,
                'user_name' => $r->user?->name ?? '—',
                'work_date' => $r->work_date->toDateString(),
                'clock_in'  => $r->clock_in_at?->setTimezone($tz)->format('H:i'),
                'clock_out' => $r->clock_out_at?->setTimezone($tz)->format('H:i'),
                'duration'  => $fmt($r->duration_minutes ?? 0),
                'source'    => $r->source,
                'note'      => $r->note,
                'edit_url'  => route('admin.attendance.update', $r),
                'destroy_url' => route('admin.attendance.destroy', $r),
            ]),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'board' => $board->map(fn ($row) => [
                'user_id'          => $row->user->id,
                'user_name'        => $row->user->name,
                'avatar_url'       => $row->user->avatar_url,
                'show_url'         => route('users.show', $row->user),
                'availability_note'=> $row->user->availability_note,
                'last_seen_at'     => $row->user->last_seen_at?->diffForHumans(),
                'status'           => $row->status,
                'status_label'     => ($statuses[$row->status] ?? $statuses['offline'])['label'],
                'status_dot'       => ($statuses[$row->status] ?? $statuses['offline'])['dot'],
                'leave_type'       => $row->leave?->type?->name,
                'clocked_in'       => $row->clocked_in,
                'first_in'         => $row->first_in?->setTimezone($tz)->format('H:i'),
                'last_out'         => $row->last_out?->setTimezone($tz)->format('H:i'),
                'minutes'          => $row->minutes,
                'duration'         => $fmt($row->minutes ?? 0),
            ]),
            'stats'          => $stats,
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
            'can_manage'     => $request->user()->hasRole('admin') || $request->user()->can('attendance.manage'),
            'timezone'       => $tz,
            'today'          => now($tz)->toDateString(),
            'store_url'         => route('admin.attendance.store'),
            'clock_out_all_url' => route('admin.attendance.clock-out-all'),
            'summary_url'       => route('admin.attendance.summary'),
            'export_url'        => route('admin.attendance.export'),
            'schedule_url_base' => url('admin/attendance/schedule'),
        ]);
    }

    public function summary(Request $request): \Inertia\Response
    {
        $tz   = $request->user()->timezone ?: config('app.timezone');
        $from = $request->filled('from') ? Carbon::parse($request->input('from'), $tz)->startOfDay()->utc()
                                         : now($tz)->startOfMonth()->utc();
        $to   = $request->filled('to') ? Carbon::parse($request->input('to'), $tz)->endOfDay()->utc()
                                       : now($tz)->endOfDay()->utc();

        $users   = User::active()->orderBy('name')->get();
        $records = Attendance::whereBetween('clock_in_at', [$from, $to])->get()->groupBy('user_id');

        $dayNames = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        $rows = $users->map(function (User $u) use ($from, $to, $records, $tz, $dayNames) {
            $workDays  = $u->work_days ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
            $expectedH = (float) ($u->expected_hours_per_day ?? 8.0);
            $startTime = $u->expected_start_time; // e.g. "09:00:00"

            // Count working days in range
            $period = CarbonPeriod::create($from->copy()->setTimezone($tz)->toDateString(),
                                           $to->copy()->setTimezone($tz)->toDateString());
            $workingDays = 0;
            foreach ($period as $day) {
                if (in_array(strtolower($day->format('D')), array_map(fn ($d) => substr($d, 0, 3), $workDays))) {
                    $workingDays++;
                }
            }

            $sessions       = $records->get($u->id, collect());
            $totalMinutes   = $sessions->sum('duration_minutes');
            $daysPresent    = $sessions->groupBy(fn ($s) => $s->work_date->toDateString())->count();
            $expectedMins   = $workingDays * $expectedH * 60;
            $diffMins       = $totalMinutes - $expectedMins;

            // Late-arrival detection
            $lateDays = 0;
            if ($startTime) {
                $byDay = $sessions->groupBy(fn ($s) => $s->work_date->toDateString());
                foreach ($byDay as $day => $daySessions) {
                    $firstIn = $daySessions->min('clock_in_at');
                    if ($firstIn) {
                        $expectedAt = Carbon::parse($day.' '.$startTime, $tz)->utc()->addMinutes(10); // 10-min grace
                        if ($firstIn->gt($expectedAt)) {
                            $lateDays++;
                        }
                    }
                }
            }

            return [
                'user_id'          => $u->id,
                'user_name'        => $u->name,
                'avatar_url'       => $u->avatar_url,
                'show_url'         => route('users.show', $u),
                'schedule_url'     => route('admin.attendance.schedule', $u),
                'expected_hours'   => $expectedH,
                'work_days'        => $workDays,
                'expected_start'   => $startTime ? substr($startTime, 0, 5) : null,
                'days_present'     => $daysPresent,
                'working_days'     => $workingDays,
                'total_minutes'    => $totalMinutes,
                'total_formatted'  => sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60),
                'expected_minutes' => (int) $expectedMins,
                'expected_formatted' => sprintf('%dh %02dm', intdiv((int)$expectedMins, 60), (int)$expectedMins % 60),
                'diff_minutes'     => $diffMins,
                'diff_formatted'   => ($diffMins >= 0 ? '+' : '-').sprintf('%dh %02dm', intdiv(abs((int)$diffMins), 60), abs((int)$diffMins) % 60),
                'overtime'         => $diffMins > 0,
                'shortfall'        => $diffMins < 0,
                'late_days'        => $lateDays,
            ];
        });

        return Inertia::render('Admin/Attendance/Summary', [
            'rows'    => $rows,
            'from'    => $from->setTimezone($tz)->toDateString(),
            'to'      => $to->setTimezone($tz)->toDateString(),
            'users'   => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'export_url' => route('admin.attendance.export'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $tz   = $request->user()->timezone ?: config('app.timezone');
        $from = $request->filled('from') ? Carbon::parse($request->input('from'), $tz)->startOfDay()->utc()
                                         : now($tz)->startOfMonth()->utc();
        $to   = $request->filled('to') ? Carbon::parse($request->input('to'), $tz)->endOfDay()->utc()
                                       : now($tz)->endOfDay()->utc();

        $records = Attendance::with('user')
            ->whereBetween('clock_in_at', [$from, $to])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->orderBy('work_date')->orderBy('clock_in_at')
            ->get();

        $filename = 'attendance-'.$from->setTimezone($tz)->toDateString().'-to-'.$to->setTimezone($tz)->toDateString().'.csv';

        return response()->streamDownload(function () use ($records, $tz) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Member', 'Clock In', 'Clock Out', 'Duration (min)', 'Duration', 'Source', 'Note']);
            foreach ($records as $r) {
                fputcsv($out, [
                    $r->work_date->toDateString(),
                    $r->user?->name ?? '—',
                    $r->clock_in_at?->setTimezone($tz)->format('H:i'),
                    $r->clock_out_at?->setTimezone($tz)->format('H:i') ?? '',
                    $r->duration_minutes,
                    $r->duration_formatted,
                    $r->source,
                    $r->note ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function updateSchedule(Request $request, User $user)
    {
        $data = $request->validate([
            'expected_hours_per_day' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'expected_start_time'    => ['nullable', 'date_format:H:i'],
            'work_days'              => ['nullable', 'array'],
            'work_days.*'            => ['string', 'in:mon,tue,wed,thu,fri,sat,sun'],
        ]);

        if (isset($data['expected_start_time']) && $data['expected_start_time']) {
            $data['expected_start_time'] = $data['expected_start_time'].':00';
        }

        $user->update($data);

        return back()->with('success', "Work schedule updated for {$user->name}.");
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

    public function clockOutAll(Request $request)
    {
        $open = Attendance::open()->with('user')->get();
        $count = 0;

        foreach ($open as $attendance) {
            $attendance->update(['clock_out_at' => now(), 'clock_out_ip' => $request->ip(), 'note' => trim(($attendance->note ?? '').' (bulk clock-out by admin)')]);
            $attendance->user?->forceFill(['availability' => 'offline'])->saveQuietly();
            $count++;
        }

        return back()->with('success', $count > 0 ? "Clocked out {$count} member(s)." : 'No members were clocked in.');
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
