@extends('layouts.app')
@section('title', 'Leave Report')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">Leave Report</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}
            </p>
        </div>
        <a href="{{ route('reports.leave', array_merge(request()->query(), ['export' => 'pdf'])) }}"
           class="btn-secondary flex items-center gap-2">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('reports.leave') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Employee</label>
                <x-ui.select name="user_id" placeholder="All Employees"
                    :value="request('user_id','')"
                    :options="$users->pluck('name','id')->toArray()" />
            </div>
            <div>
                <label class="form-label">Leave Type</label>
                <x-ui.select name="type_id" placeholder="All Types"
                    :value="request('type_id','')"
                    :options="$leaveTypes->pluck('name','id')->toArray()" />
            </div>
            <div>
                <label class="form-label">Status</label>
                <x-ui.select name="status" placeholder="All Statuses"
                    :value="request('status','')"
                    :options="['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled']" />
            </div>
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('reports.leave') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="stat-card sm:col-span-1">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total Requests</span>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $summary['total'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Approved</span>
            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $summary['approved'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Pending</span>
            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $summary['pending'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Rejected</span>
            <div class="text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $summary['rejected'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Approved Days</span>
            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">{{ number_format($summary['days'], 1) }}</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Employee</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Type</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Dates</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Days</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Reason</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Reviewed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($leaves as $leave)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $leave->user->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $leave->type->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $leave->date_range }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-slate-700 dark:text-slate-300">{{ $leave->days }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 max-w-xs truncate" title="{{ $leave->reason }}">{{ $leave->reason ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $st = \App\Models\LeaveRequest::STATUSES[$leave->status] ?? null @endphp
                            @if($st)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $st['class'] }}">
                                {{ $st['label'] }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $leave->reviewer->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">No leave requests found for the selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaves->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $leaves->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
