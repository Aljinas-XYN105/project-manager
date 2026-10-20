<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $month = $request->filled('month') ? \Carbon\Carbon::parse($request->input('month').'-01') : now()->startOfMonth();

        $records = $user->attendances()
            ->whereBetween('work_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderByDesc('clock_in_at')
            ->get();

        $byDay = $records->groupBy(fn ($a) => $a->work_date->toDateString());

        $summary = [
            'days'          => $byDay->count(),
            'total_minutes' => $records->sum('duration_minutes'),
            'avg_minutes'   => $byDay->count() ? intdiv($records->sum('duration_minutes'), $byDay->count()) : 0,
            'first_in_avg'  => $byDay->count()
                ? \Carbon\Carbon::today()->addMinutes((int) round($byDay->map(function ($d) {
                    $first = $d->min('clock_in_at')->inUserTz();
                    return $first->hour * 60 + $first->minute;
                })->avg()))->format('g:i A')
                : '—',
        ];

        return view('attendance.index', [
            'byDay'   => $byDay,
            'month'   => $month,
            'summary' => $summary,
            'open'    => $user->openAttendance(),
        ]);
    }

    public function clockIn(Request $request)
    {
        Attendance::clockIn($request->user(), $request->ip(), 'manual');
        $request->user()->forceFill(['availability' => 'available'])->saveQuietly();

        return $this->respond($request, 'Clocked in at '.now()->inUserTz()->format('g:i A').'.');
    }

    public function clockOut(Request $request)
    {
        $closed = Attendance::clockOut($request->user(), $request->ip());
        $request->user()->forceFill(['availability' => 'away'])->saveQuietly();

        return $this->respond($request, $closed ? 'Clocked out — worked '.$closed->duration_formatted.'.' : 'You were not clocked in.');
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'availability'      => ['required', Rule::in(array_keys(User::AVAILABILITY))],
            'availability_note' => ['nullable', 'string', 'max:120'],
        ]);

        $request->user()->update($data);

        return $this->respond($request, 'Status set to '.User::AVAILABILITY[$data['availability']]['label'].'.');
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            $user = $request->user()->fresh();
            $open = $user->openAttendance();
            return response()->json([
                'message'      => $message,
                'availability' => $user->availability,
                'clocked_in'   => (bool) $open,
                'clock_in_at'  => $open?->clock_in_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', $message);
    }
}
