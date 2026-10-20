<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveSettingsController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        return view('admin.leave.index', [
            'types'    => LeaveType::withCount(['requests as used_this_year' => fn ($q) => $q->where('status', 'approved')->whereYear('start_date', now()->year)])->orderBy('name')->get(),
            'holidays' => Holiday::whereYear('date', $year)->orderBy('date')->get(),
            'year'     => $year,
            'summary'  => [
                'pending'        => LeaveRequest::where('status', 'pending')->count(),
                'on_leave_today' => LeaveRequest::where('status', 'approved')->onDate(today()->toDateString())->count(),
                'approved_year'  => (float) LeaveRequest::where('status', 'approved')->whereYear('start_date', now()->year)->sum('days'),
            ],
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
