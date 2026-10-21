@extends('layouts.app')
@section('title', 'Daily Reports')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">My Daily Reports</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your daily work progress</p>
    </div>
    <div class="flex items-center gap-2">
        @if(auth()->user()->hasAnyRole(['admin','manager']))
        <a href="{{ route('daily-reports.team') }}" class="btn-ghost btn-sm">
            <i class="bi bi-people-fill"></i> Team Reports
        </a>
        @endif
        <a href="{{ route('daily-reports.create') }}" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Submit Today's Report
        </a>
    </div>
</div>


{{-- Today's report banner --}}
@if($todayReport)
<div class="card p-4 mb-6 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                <i class="bi bi-check-circle-fill text-green-600 text-base"></i>
            </div>
            <div>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">Today's report submitted!</p>
                <p class="text-xs text-gray-500">{{ $todayReport->total_hours }}h logged · {{ $todayReport->reportTasks->count() }} task(s)</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('daily-reports.show', $todayReport) }}" class="btn-ghost btn-sm">View</a>
            <a href="{{ route('daily-reports.edit', $todayReport) }}" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        </div>
    </div>
</div>
@else
<div class="card p-4 mb-6 border-l-4 border-orange-400 bg-orange-50 dark:bg-orange-900/20">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center">
                <i class="bi bi-journal-text text-orange-600 text-base"></i>
            </div>
            <div>
                <p class="font-semibold text-sm text-gray-900 dark:text-white">You haven't submitted today's report yet</p>
                <p class="text-xs text-gray-500">{{ today()->format('l, F j, Y') }}</p>
            </div>
        </div>
        <a href="{{ route('daily-reports.create') }}" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Submit Now
        </a>
    </div>
</div>
@endif

{{-- Reports list --}}
@if($reports->isEmpty())
<div class="card p-14 text-center">
    <i class="bi bi-journal-text text-5xl text-slate-200 dark:text-slate-700 block mb-4"></i>
    <h3 class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-1">No reports submitted yet</h3>
    <p class="text-sm text-slate-400 mb-4">Submit your first daily standup to start tracking your progress.</p>
    <a href="{{ route('daily-reports.create') }}" class="btn-primary btn-sm mx-auto">
        <i class="bi bi-plus-lg"></i> Submit Today's Report
    </a>
</div>
@else
<div class="space-y-3">
    @foreach($reports as $report)
    <div class="card p-5 hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ $report->report_date->format('l, F j, Y') }}
                    </span>
                    @if($report->report_date->isToday())
                    <span class="badge badge-success text-[10px]">Today</span>
                    @elseif($report->report_date->isYesterday())
                    <span class="badge badge-secondary text-[10px]">Yesterday</span>
                    @endif
                    <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 dark:text-slate-400 px-2 py-0.5 rounded-full">
                        {{ $report->total_hours }}h logged
                    </span>
                    <span class="text-xs text-slate-400">{{ $report->reportTasks->count() }} task(s)</span>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-2">{{ $report->summary }}</p>
                @if($report->blockers)
                <div class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 mb-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span class="truncate">{{ Str::limit($report->blockers, 100) }}</span>
                </div>
                @endif
                @if($report->reportTasks->count())
                <div class="flex flex-wrap gap-1.5">
                    @foreach($report->reportTasks->take(5) as $rt)
                    <span class="text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded-full">
                        {{ Str::limit($rt->task_description, 30) }} · {{ $rt->hours_spent }}h
                    </span>
                    @endforeach
                    @if($report->reportTasks->count() > 5)
                    <span class="text-[11px] text-slate-400">+{{ $report->reportTasks->count()-5 }} more</span>
                    @endif
                </div>
                @endif
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <a href="{{ route('daily-reports.show', $report) }}" class="btn-ghost btn-sm">View</a>
                <a href="{{ route('daily-reports.edit', $report) }}" class="btn-ghost btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                <form method="POST" action="{{ route('daily-reports.destroy', $report) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost btn-sm text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                            onclick="return confirm('Delete this report?')" title="Delete">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="mt-6">{{ $reports->links() }}</div>
@endif
@endsection
