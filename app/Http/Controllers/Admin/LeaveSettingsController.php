<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LeaveSettingsController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int) $request->input('year', now()->year);
        $types = LeaveType::withCount(['requests as used_this_year' => fn ($q) => $q->where('status', 'approved')->whereYear('start_date', now()->year)])->orderBy('name')->get();

        return Inertia::render('Admin/Leave/Index', [
            'types' => $types->map(fn ($t) => [
                'id' => $t->id, 'name' => $t->name, 'color' => $t->color,
                'days_per_year' => $t->days_per_year, 'is_paid' => $t->is_paid,
                'is_active' => $t->is_active, 'is_unlimited' => $t->is_unlimited ?? ($t->days_per_year == 0),
                'used_this_year' => $t->used_this_year,
                'update_url' => route('admin.leave.types.update', $t),
                'destroy_url' => route('admin.leave.types.destroy', $t),
            ]),
            'holidays' => Holiday::whereYear('date', $year)->orderBy('date')->get()->map(fn ($h) => [
                'id' => $h->id, 'name' => $h->name,
                'date' => $h->date->toDateString(),
                'date_formatted' => $h->date->format('D M j'),
                'is_past' => $h->date->isPast() && ! $h->date->isToday(),
                'destroy_url' => route('admin.holidays.destroy', $h),
            ]),
            'year'    => $year,
            'summary' => [
                'pending'        => LeaveRequest::where('status', 'pending')->count(),
                'on_leave_today' => LeaveRequest::where('status', 'approved')->onDate(today()->toDateString())->count(),
                'approved_year'  => (float) LeaveRequest::where('status', 'approved')->whereYear('start_date', now()->year)->sum('days'),
            ],
            'leave_type_colors'  => LeaveType::COLORS,
            'store_type_url'     => route('admin.leave.types.store'),
            'store_holiday_url'  => route('admin.holidays.store'),
            'approvals_url'      => route('leave.approvals'),
            'calendar_url'       => route('leave.calendar'),
            'prev_year_url'      => route('admin.leave.index', ['year' => $year - 1]),
            'next_year_url'      => route('admin.leave.index', ['year' => $year + 1]),
        ]);
    }

    public function storeType(Request $request)
    {
        LeaveType::create($this->validatedType($request));

        return back()->with('success', 'Leave type added.');
    }

    public function updateType(Request $request, LeaveType $type)
    {
        $type->update($this->validatedType($request, $type));

        return back()->with('success', "{$type->name} updated.");
    }

    public function destroyType(LeaveType $type)
    {
        if ($type->requests()->exists()) {
            $type->update(['is_active' => false]);
            return back()->with('success', "{$type->name} has existing requests, so it was deactivated instead of deleted.");
        }

        $type->delete();

        return back()->with('success', 'Leave type deleted.');
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date', 'unique:holidays,date'],
        ], ['date.unique' => 'There is already a holiday on that date.']);

        Holiday::create($data);

        return back()->with('success', "Holiday “{$data['name']}” added.");
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }

    private function validatedType(Request $request, ?LeaveType $type = null): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:60', Rule::unique('leave_types', 'name')->ignore($type?->id)],
            'color'         => ['required', Rule::in(LeaveType::COLORS)],
            'days_per_year' => ['required', 'numeric', 'min:0', 'max:365'],
        ]);

        return $data + [
            'is_paid'   => $request->boolean('is_paid'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
