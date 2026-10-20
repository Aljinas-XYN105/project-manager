<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveReviewed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $year  = (int) $request->input('year', now()->year);
        $types = LeaveType::active()->orderBy('name')->get();
        $usage = $user->leaveUsage($year);

        $balances = $types->map(fn (LeaveType $t) => (object) [
            'type'      => $t,
            'approved'  => $usage[$t->id]['approved'] ?? 0,
            'pending'   => $usage[$t->id]['pending'] ?? 0,
            'remaining' => $t->is_unlimited ? null : max(0, $t->days_per_year - ($usage[$t->id]['approved'] ?? 0) - ($usage[$t->id]['pending'] ?? 0)),
        ]);

        $requests = $user->leaveRequests()->with(['type', 'reviewer'])->latest('start_date')->paginate(15);

        return view('leave.index', [
            'year'          => $year,
            'balances'      => $balances,
            'types'         => $types,
            'requests'      => $requests,
            'holidays'      => Holiday::upcoming()->limit(6)->get(),
            'pendingReview' => $this->reviewableQuery($user)->where('status', 'pending')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day'   => ['nullable', 'boolean'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        $type  = LeaveType::active()->findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end   = Carbon::parse($data['end_date'])->startOfDay();
        $half  = $request->boolean('is_half_day') && $start->equalTo($end);
        $days  = LeaveRequest::countWorkingDays($start, $end, $half);

        if ($days <= 0) {
            throw ValidationException::withMessages(['start_date' => 'The selected dates are all weekends or company holidays.']);
        }
        if ($start->diffInDays($end) > 90) {
            throw ValidationException::withMessages(['end_date' => 'A single request can cover at most 90 days.']);
        }

        $clash = $user->leaveRequests()->active()->overlapping($start->toDateString(), $end->toDateString())->with('type')->first();
        if ($clash) {
            throw ValidationException::withMessages(['start_date' => "This overlaps your {$clash->status} {$clash->type->name} ({$clash->date_range})."]);
        }

        if (! $type->is_unlimited) {
            $used = collect($user->leaveUsage($start->year)->get($type->id, ['approved' => 0, 'pending' => 0]))->sum();
            $left = $type->days_per_year - $used;
            if ($days > $left) {
                throw ValidationException::withMessages(['leave_type_id' => "Only {$left} day(s) of {$type->name} left in {$start->year} (requested {$days})."]);
            }
        }

        $leave = $user->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date'    => $start,
            'end_date'      => $end,
            'is_half_day'   => $half,
            'days'          => $days,
            'reason'        => $data['reason'] ?? null,
            'status'        => 'pending',
        ])->load(['user', 'type']);

        Notification::send($this->approversFor($user), new LeaveRequested($leave));

        return redirect()->route('leave.index')->with('success', "Leave request for {$days} day(s) sent for approval.");
    }

    public function cancel(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->user_id === $request->user()->id, 403);

        if (! in_array($leave->status, ['pending', 'approved']) || ($leave->status === 'approved' && $leave->start_date->isPast() && ! $leave->start_date->isToday())) {
            return back()->with('error', 'Only pending requests, or approved leave that has not started, can be cancelled.');
        }

        $leave->update(['status' => 'cancelled']);

        return back()->with('success', 'Leave request cancelled.');
    }

    public function approvals(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status', 'pending');

        $requests = $this->reviewableQuery($user)
            ->with(['user', 'type', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy($status === 'pending' ? 'start_date' : 'updated_at', $status === 'pending' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        // Who else from the same people is off during each pending request, to spot coverage gaps.
        $overlaps = [];
        foreach ($requests as $r) {
            if ($r->status === 'pending') {
                $overlaps[$r->id] = LeaveRequest::with('user:id,name')
                    ->where('status', 'approved')->where('user_id', '!=', $r->user_id)
                    ->overlapping($r->start_date->toDateString(), $r->end_date->toDateString())
                    ->get()->pluck('user.name')->unique()->values();
            }
        }

        return view('leave.approvals', compact('requests', 'status', 'overlaps'));
    }

    public function review(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->canBeReviewedBy($request->user()), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,rejected'],
        ], ['note.required_if' => 'Please give a reason when declining.']);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'This request was already '.$leave->status.'.');
        }

        $leave->update([
            'status'      => $data['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['note'] ?? null,
        ]);

        $leave->user->notify(new LeaveReviewed($leave->load(['type', 'reviewer'])));

        return back()->with('success', "{$leave->user->name}'s leave ".($data['decision'] === 'approved' ? 'approved' : 'declined').'.');
    }

    public function calendar(Request $request)
    {
        $month = $request->filled('month') ? Carbon::parse($request->input('month').'-01') : now()->startOfMonth();
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end   = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $leaves = LeaveRequest::with(['user:id,name,avatar,department', 'type'])
            ->whereIn('status', ['approved', 'pending'])
            ->overlapping($start->toDateString(), $end->toDateString())
            ->when($request->filled('department'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('department', $request->input('department'))))
            ->get();

        $holidays = Holiday::whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())->get()
            ->keyBy(fn ($h) => $h->date->toDateString());

        return view('leave.calendar', [
            'month'       => $month,
            'start'       => $start,
            'end'         => $end,
            'leaves'      => $leaves,
            'holidays'    => $holidays,
            'departments' => User::active()->whereNotNull('department')->select('department')->distinct()->orderBy('department')->pluck('department'),
        ]);
    }

    /** Requests this user may decide on: everyone's for admins/approvers, otherwise their direct reports'. */
    private function reviewableQuery(User $user)
    {
        $query = LeaveRequest::query()->where('user_id', '!=', $user->id);

        if ($user->hasRole('admin') || $user->can('leave.approve')) {
            return $query;
        }

        return $query->whereIn('user_id', $user->directReports()->pluck('id'));
    }

    private function approversFor(User $employee)
    {
        if ($employee->manager && $employee->manager->is_active) {
            return collect([$employee->manager]);
        }

        return User::active()->where('id', '!=', $employee->id)->get()
            ->filter(fn (User $u) => $u->hasRole('admin') || $u->can('leave.approve'));
    }
}
