@extends('layouts.app')
@section('title', 'Team Standups')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                <i class="bi bi-people-fill text-violet-600 dark:text-violet-400"></i>
            </span>
            Team Standups
        </h1>
        <p class="text-sm text-slate-500 mt-1">Daily reports across the team</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="card p-4 mb-6">
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <label class="form-label">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="form-input">
        </div>
        <div class="flex-1 min-w-[180px]">
            <label class="form-label">Team Member</label>
            <select name="user_id" class="form-select">
                <option value="">All Members</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="{{ route('daily-reports.team') }}" class="btn-ghost">Reset</a>
    </div>
</form>

{{-- Stats bar --}}
@php
$totalReports = $reports->total();
$withBlockers = $reports->getCollection()->filter(fn($r) => $r->blockers)->count();
$blockedTasks = $reports->getCollection()->flatMap(fn($r) => $r->reportTasks)->where('is_blocked', true)->count();
$totalHours   = $reports->getCollection()->sum('total_hours');
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $totalReports }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Reports</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalHours, 1) }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Hours Logged</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold {{ $withBlockers > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }}">{{ $withBlockers }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Have Blockers</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold {{ $blockedTasks > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">{{ $blockedTasks }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Blocked Tasks</p>
    </div>
</div>

@if($reports->isEmpty())
<div class="empty-state">
    <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
    <h3>No reports for this date</h3>
    <p>No team members have submitted a standup for the selected date or filters.</p>
</div>
@else
<div class="space-y-4">
    @foreach($reports as $report)
    @php $hasBlockers = $report->blockers || $report->reportTasks->where('is_blocked', true)->count() > 0; @endphp
    <div class="card overflow-hidden {{ $hasBlockers ? 'border-l-4 border-l-amber-400 dark:border-l-amber-600' : '' }}">
        {{-- Report header --}}
        <div class="p-4 flex items-center gap-3 cursor-pointer"
             x-data="{ open: false }" @click="open = !open">
            <img src="{{ $report->user->avatar_url }}" alt="{{ $report->user->name }}"
                 class="w-10 h-10 rounded-full flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-slate-900 dark:text-white text-sm">{{ $report->user->name }}</span>
                    <span class="text-xs text-slate-400">{{ $report->report_date->format('M j') }}</span>
                    @if($hasBlockers)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-semibold">
                        <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blocker
                    </span>
                    @endif
                    <span class="ml-auto text-sm font-bold text-slate-900 dark:text-white">
                        {{ number_format($report->total_hours, 1) }}h
                    </span>
                    <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="open && 'rotate-180'"></i>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ $report->summary }}</p>
            </div>

            {{-- Expanded content --}}
            <div x-show="open" x-cloak @click.stop
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="border-t border-slate-100 dark:border-slate-800 p-4 space-y-4">

                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $report->summary }}</p>

                @if($report->reportTasks->count())
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Tasks</p>
                    <div class="space-y-2">
                        @foreach($report->reportTasks as $rt)
                        @php
                        $statusColors = [
                            'in_progress' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
                            'in_review'   => 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
                            'done'        => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
                            'blocked'     => 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400',
                            'todo'        => 'bg-slate-50 dark:bg-slate-800 text-slate-500',
                        ];
                        @endphp
                        <div class="flex items-start gap-3 text-sm {{ $rt->is_blocked ? 'opacity-80' : '' }}">
                            <span class="flex-shrink-0 font-mono text-xs text-slate-400 w-14 pt-0.5">
                                {{ number_format($rt->hours_spent, 1) }}h
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-slate-800 dark:text-slate-200">{{ $rt->task_description }}</span>
                                    @if($rt->is_blocked)
                                    <span class="inline-flex items-center gap-0.5 px-1 py-px rounded text-[10px] font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                                        <i class="bi bi-exclamation-octagon-fill"></i> blocked
                                    </span>
                                    @endif
                                    @if($rt->status_update)
                                    <span class="px-1.5 py-px rounded text-[10px] font-medium {{ $statusColors[$rt->status_update] ?? '' }}">
                                        {{ str_replace('_', ' ', ucfirst($rt->status_update)) }}
                                    </span>
                                    @endif
                                </div>
                                @if($rt->notes)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $rt->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($report->plan_for_tomorrow || $report->blockers)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @if($report->plan_for_tomorrow)
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Tomorrow</p>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $report->plan_for_tomorrow }}</p>
                    </div>
                    @endif
                    @if($report->blockers)
                    <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <p class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blockers
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $report->blockers }}</p>
                    </div>
                    @endif
                </div>
                @endif

                <div class="flex justify-end">
                    <a href="{{ route('daily-reports.show', $report) }}"
                       class="text-xs text-violet-600 dark:text-violet-400 hover:underline">
                        View full report →
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">{{ $reports->appends(request()->query())->links() }}</div>
@endif
@endsection
