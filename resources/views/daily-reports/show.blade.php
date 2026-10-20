@extends('layouts.app')
@section('title', 'Report — '.$dailyReport->report_date->format('M j, Y'))

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="bi bi-journal-text text-amber-600 dark:text-amber-400"></i>
                </span>
                Daily Report
            </h1>
            <p class="text-sm text-slate-500 mt-1">{{ $dailyReport->report_date->format('l, F j, Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $dailyReport)
            <a href="{{ route('daily-reports.edit', $dailyReport) }}" class="btn-secondary btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('daily-reports.index') }}" class="btn-ghost">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Author strip --}}
    <div class="card p-4 mb-5 flex items-center gap-3">
        <img src="{{ $dailyReport->user->avatar_url }}" alt="{{ $dailyReport->user->name }}"
             class="w-10 h-10 rounded-full ring-2 ring-white dark:ring-slate-800 shadow-sm">
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-900 dark:text-white text-sm">{{ $dailyReport->user->name }}</p>
            <p class="text-xs text-slate-400">{{ $dailyReport->report_date->format('l, F j, Y') }} · Submitted {{ $dailyReport->created_at->diffForHumans() }}</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($dailyReport->total_hours, 1) }}<span class="text-sm font-medium text-slate-400">h</span></p>
            <p class="text-xs text-slate-400">total logged</p>
        </div>
    </div>

    <div class="space-y-5">

        {{-- Summary --}}
        <div class="card p-5">
            <h2 class="section-heading">Summary</h2>
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $dailyReport->summary }}</p>
        </div>

        {{-- Task Breakdown --}}
        @if($dailyReport->reportTasks->count())
        <div class="card p-5">
            <h2 class="section-heading">Task Breakdown</h2>
            <div class="space-y-3">
                @foreach($dailyReport->reportTasks as $rt)
                @php
                    $statusColors = [
                        'in_progress' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                        'in_review'   => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                        'done'        => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                        'blocked'     => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
                        'todo'        => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
                    ];
                    $statusLabels = [
                        'in_progress' => '🔵 In Progress',
                        'in_review'   => '🟡 In Review',
                        'done'        => '✅ Done',
                        'blocked'     => '🔴 Blocked',
                        'todo'        => '⚪ To Do',
                    ];
                @endphp
                <div class="flex items-start gap-4 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60
                            {{ $rt->is_blocked ? 'border-l-4 border-l-rose-400 dark:border-l-rose-600' : '' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            @if($rt->task)
                            <a href="{{ route('projects.tasks.show', $rt->task) }}"
                               class="font-mono text-xs text-violet-600 dark:text-violet-400 hover:underline">
                                {{ $rt->task->ticket_key ?? '#'.$rt->task->id }}
                            </a>
                            @endif
                            @if($rt->is_blocked)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-semibold">
                                <i class="bi bi-exclamation-octagon-fill text-[10px]"></i> Blocked
                            </span>
                            @endif
                            @if($rt->status_update)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-xs font-medium {{ $statusColors[$rt->status_update] ?? '' }}">
                                {{ $statusLabels[$rt->status_update] ?? $rt->status_update }}
                            </span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $rt->task_description }}</p>
                        @if($rt->notes)
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $rt->notes }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <span class="text-lg font-bold text-slate-900 dark:text-white">{{ number_format($rt->hours_spent, 1) }}</span>
                        <span class="text-xs text-slate-400">h</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Plan & Blockers --}}
        @if($dailyReport->plan_for_tomorrow || $dailyReport->blockers)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @if($dailyReport->plan_for_tomorrow)
            <div class="card p-5">
                <h2 class="section-heading flex items-center gap-1.5">
                    <i class="bi bi-calendar-event text-violet-500"></i> Plan for Tomorrow
                </h2>
                <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line">{{ $dailyReport->plan_for_tomorrow }}</p>
            </div>
            @endif
            @if($dailyReport->blockers)
            <div class="card p-5 border-amber-200 dark:border-amber-800">
                <h2 class="section-heading flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                    <i class="bi bi-exclamation-triangle-fill"></i> Blockers
                </h2>
                <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line">{{ $dailyReport->blockers }}</p>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>
@endsection
