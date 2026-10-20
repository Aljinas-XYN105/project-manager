@extends('layouts.app')
@section('title', $user->name . ' — Employee Profile')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-violet-600">Admin</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>{{ $user->name }}</span>
        </div>
        <div class="flex items-center gap-3">
            <img src="{{ $user->avatar_url }}" class="w-12 h-12 rounded-2xl ring-2 ring-slate-200 dark:ring-slate-700">
            <div>
                <h1 class="page-title leading-tight">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500 capitalize">{{ $user->getRoleNames()->first() ?? 'member' }} · {{ $user->email }}</p>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET">
            <select name="period" onchange="this.form.submit()" class="form-select text-sm">
                <option value="7"  {{ $period=='7'  ? 'selected':'' }}>Last 7 days</option>
                <option value="30" {{ $period=='30' ? 'selected':'' }}>Last 30 days</option>
                <option value="90" {{ $period=='90' ? 'selected':'' }}>Last 90 days</option>
            </select>
        </form>
        <a href="{{ route('admin.dashboard') }}" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- KPI strip --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $totalHours }}h</p>
        <p class="text-xs text-slate-500 mt-0.5">Hours logged</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $completedTasks->count() }}</p>
        <p class="text-xs text-slate-500 mt-0.5">Tasks completed</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ $openTasks->count() }}</p>
        <p class="text-xs text-slate-500 mt-0.5">Open tasks</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $dailyReports->count() }}</p>
        <p class="text-xs text-slate-500 mt-0.5">Daily reports</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">

    {{-- Hours per project --}}
    <div class="card p-5">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Hours by Project</h2>
        @if($projectHours->count())
        @php $maxPH = $projectHours->max() ?: 1; @endphp
        <div class="space-y-3">
            @foreach($projectHours as $proj => $hrs)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ $proj }}</span>
                    <span class="text-xs font-semibold text-slate-900 dark:text-white ml-2 flex-shrink-0">{{ $hrs }}h</span>
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full">
                    <div class="h-2 bg-violet-500 rounded-full" style="width: {{ round($hrs/$maxPH*100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-slate-400 text-center py-6">No time logged this period.</p>
        @endif
    </div>

    {{-- Daily hours breakdown --}}
    <div class="card p-5">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Daily Hours</h2>
        @if($dailyBreakdown->count())
        @php $maxDH = $dailyBreakdown->max() ?: 1; @endphp
        <div class="flex items-end gap-1.5 h-28">
            @foreach($dailyBreakdown as $date => $hrs)
            <div class="flex-1 flex flex-col items-center gap-1 group min-w-0">
                <div class="w-full rounded-t transition-colors group-hover:opacity-80 bg-violet-500"
                     style="height: {{ max(4, round($hrs/$maxDH*100)) }}px"
                     title="{{ $date }}: {{ $hrs }}h"></div>
                <span class="text-[9px] text-slate-400 truncate">{{ \Carbon\Carbon::parse($date)->format('j') }}</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-slate-400 text-center py-6">No data yet.</p>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Open Tasks --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Open Tasks ({{ $openTasks->count() }})</h2>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-80 overflow-y-auto">
            @forelse($openTasks as $task)
            <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                @php
                $sc = ['todo'=>'bg-slate-300','in_progress'=>'bg-blue-500','in_review'=>'bg-amber-500','done'=>'bg-emerald-500'];
                @endphp
                <div class="w-2 h-2 rounded-full {{ $sc[$task->status] ?? 'bg-slate-300' }} flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('projects.tasks.show', $task) }}"
                       class="text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600 truncate block">{{ $task->title }}</a>
                    <p class="text-xs text-slate-400">{{ $task->project->name ?? '' }}
                        @if($task->due_date && $task->due_date < now())
                        · <span class="text-rose-500">Overdue {{ $task->due_date->diffForHumans() }}</span>
                        @elseif($task->due_date)
                        · Due {{ $task->due_date->format('M j') }}
                        @endif
                    </p>
                </div>
                <span class="badge badge-{{ $task->priority ?? 'medium' }} flex-shrink-0">{{ ucfirst($task->priority ?? 'medium') }}</span>
            </div>
            @empty
            <div class="py-10 text-center text-slate-400 text-sm">
                <i class="bi bi-check2-all text-2xl block mb-2"></i>
                All caught up!
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Daily Reports --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Recent Standups ({{ $dailyReports->count() }})</h2>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-80 overflow-y-auto">
            @forelse($dailyReports as $report)
            <a href="{{ route('daily-reports.show', $report) }}"
               class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors block">
                <div class="flex-shrink-0 text-center w-10 mt-0.5">
                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $report->report_date->format('j') }}</p>
                    <p class="text-[10px] text-slate-400">{{ $report->report_date->format('M') }}</p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ Str::limit($report->summary, 80) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $report->reportTasks->count() }} tasks · {{ $report->total_hours }}h logged
                        @if($report->blockers)
                        · <span class="text-rose-500">Has blockers</span>
                        @endif
                    </p>
                </div>
            </a>
            @empty
            <div class="py-10 text-center text-slate-400 text-sm">
                <i class="bi bi-journal-x text-2xl block mb-2"></i>
                No reports this period.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
