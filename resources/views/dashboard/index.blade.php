@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            Good {{ now()->format('H') < 12 ? 'morning' : (now()->format('H') < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="flex items-center gap-3">
        @auth
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('admin.dashboard') }}" class="btn-ghost text-sm">
            <i class="bi bi-shield-fill-check text-rose-500"></i>
            <span class="hidden sm:inline">Admin Panel</span>
        </a>
        @endif
        @endauth
        <a href="{{ route('projects.create') }}" class="btn-primary">
            <i class="bi bi-plus-lg"></i> <span class="hidden sm:inline">New Project</span>
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Projects</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['total_projects'] }}</p>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    {{ $stats['active_projects'] }} active
                </p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                <i class="bi bi-folder2-open text-violet-600 dark:text-violet-400"></i>
            </div>
        </div>
    </div>
    <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">My Tasks</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['my_tasks'] }}</p>
                <p class="text-xs text-slate-500 mt-1">open tasks</p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                <i class="bi bi-check2-square text-blue-600 dark:text-blue-400"></i>
            </div>
        </div>
    </div>
    <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Overdue</p>
                <p class="text-3xl font-bold {{ $stats['overdue_tasks'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $stats['overdue_tasks'] }}
                </p>
                <p class="text-xs text-slate-500 mt-1">past due date</p>
            </div>
            <div class="w-10 h-10 rounded-2xl {{ $stats['overdue_tasks'] > 0 ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-slate-100 dark:bg-slate-800' }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                <i class="bi bi-exclamation-triangle {{ $stats['overdue_tasks'] > 0 ? 'text-rose-500' : 'text-slate-400' }}"></i>
            </div>
        </div>
    </div>
    <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Done Today</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['completed_today'] }}</p>
                <p class="text-xs text-slate-500 mt-1">tasks completed</p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                <i class="bi bi-check-circle-fill text-emerald-600 dark:text-emerald-400"></i>
            </div>
        </div>
    </div>
</div>

{{-- Timer banner if running --}}
@if($runningTimer)
<div class="mb-6 rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-600 p-4 flex items-center gap-4 text-white shadow-lg shadow-violet-200 dark:shadow-violet-900/20">
    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-stopwatch-fill text-xl animate-pulse"></i>
    </div>
    <div class="flex-1 min-w-0">
        <p class="font-semibold text-sm">Timer running</p>
        <p class="text-xs text-white/70 truncate">{{ $runningTimer->task?->title ?? $runningTimer->description ?? 'Untitled entry' }}</p>
    </div>
    <form method="POST" action="{{ route('time-entries.stop', $runningTimer) }}">
        @csrf @method('PATCH')
        <button type="submit" class="flex-shrink-0 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-medium transition-colors">
            <i class="bi bi-stop-circle"></i> Stop
        </button>
    </form>
</div>
@endif

{{-- Daily report reminder --}}
@php $hasReport = auth()->user()->todayReport(); @endphp
@if(!$hasReport)
<div class="mb-6 rounded-2xl border-2 border-dashed border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/10 p-4 flex items-center gap-4">
    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-journal-plus text-amber-600 dark:text-amber-400 text-xl"></i>
    </div>
    <div class="flex-1">
        <p class="font-semibold text-sm text-amber-800 dark:text-amber-200">Daily standup not submitted</p>
        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Submit today's progress report to keep your team in sync.</p>
    </div>
    <a href="{{ route('daily-reports.create') }}" class="flex-shrink-0 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition-colors">
        Submit now
    </a>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- My Projects --}}
    <div class="xl:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">My Projects</h2>
            <a href="{{ route('projects.index') }}" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all →</a>
        </div>
        <div class="space-y-2">
            @forelse($myProjects as $project)
            <div class="card p-4 hover:shadow-card-md transition-shadow group">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center"
                         style="background-color: {{ $project->color }}20;">
                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $project->color }};"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('projects.show', $project) }}"
                               class="font-semibold text-slate-900 dark:text-white hover:text-violet-600 text-sm truncate">
                                {{ $project->name }}
                            </a>
                            <span class="badge badge-{{ $project->status === 'active' ? 'success' : ($project->status === 'on_hold' ? 'warning' : 'neutral') }} text-[10px]">
                                {{ ucwords(str_replace('_',' ',$project->status)) }}
                            </span>
                        </div>
                        @php
                        $total = $project->tasks->whereNull('parent_id')->count();
                        $done  = $project->tasks->whereNull('parent_id')->where('status','done')->count();
                        $pct   = $total > 0 ? round($done/$total*100) : 0;
                        @endphp
                        <div class="flex items-center gap-3 mt-2">
                            <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                                <div class="h-1.5 rounded-full transition-all" style="background-color: {{ $project->color }}; width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-slate-400 flex-shrink-0">{{ $done }}/{{ $total }}</span>
                        </div>
                        <div class="flex items-center gap-4 mt-1.5 text-xs text-slate-400">
                            @if($project->due_date)
                            <span class="{{ $project->due_date < now() ? 'text-rose-500' : '' }}">
                                <i class="bi bi-calendar3 mr-0.5"></i>{{ $project->due_date->format('M j') }}
                            </span>
                            @endif
                            <span><i class="bi bi-people mr-0.5"></i>{{ $project->members->count() + 1 }} members</span>
                        </div>
                    </div>
                    <a href="{{ route('projects.show', $project) }}"
                       class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity btn-icon btn-ghost">
                        <i class="bi bi-arrow-right text-sm"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-folder-plus text-3xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                <p class="font-medium text-slate-600 dark:text-slate-400">No projects yet</p>
                <p class="text-sm text-slate-400 mt-1">Create your first project to get started</p>
                <a href="{{ route('projects.create') }}" class="btn-primary mt-4">
                    <i class="bi bi-plus-lg"></i> New Project
                </a>
            </div>
            @endforelse
        </div>

        {{-- Upcoming Tasks --}}
        @if($upcomingTasks->count())
        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Upcoming Deadlines</h2>
            </div>
            <div class="card overflow-hidden">
                <div class="divide-y divide-slate-50 dark:divide-slate-800">
                    @foreach($upcomingTasks->take(5) as $task)
                    <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        @php
                        $sc2 = ['todo'=>'bg-slate-300','in_progress'=>'bg-blue-500','in_review'=>'bg-amber-500'];
                        $isOverdue = $task->due_date && $task->due_date < now();
                        @endphp
                        <div class="w-2 h-2 rounded-full {{ $sc2[$task->status] ?? 'bg-slate-300' }} flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('projects.tasks.show', $task) }}"
                               class="text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600 truncate block">{{ $task->title }}</a>
                            <p class="text-xs text-slate-400">{{ $task->project->name ?? '' }}</p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-medium {{ $isOverdue ? 'text-rose-500' : 'text-slate-500' }}">
                            {{ $task->due_date->format('M j') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Right column --}}
    <div class="space-y-6">
        {{-- Announcements --}}
        @if($announcements->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Announcements</h2>
                <a href="{{ route('announcements.index') }}" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all →</a>
            </div>
            <div class="card overflow-hidden divide-y divide-slate-50 dark:divide-slate-800">
                @foreach($announcements as $a)
                <a href="{{ route('announcements.index') }}#announcement-{{ $a->id }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <i class="bi {{ $a->style['icon'] }} text-{{ $a->style['color'] }}-500 mt-0.5"></i>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">@if($a->is_pinned)<i class="bi bi-pin-angle-fill text-violet-500"></i> @endif{{ $a->title }}</p>
                        <p class="text-[11px] text-slate-500 line-clamp-2">{{ Str::limit($a->body, 110) }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $a->author->name }} · {{ $a->created_at->diffForHumans() }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- People today --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Team</h2>
                <a href="{{ route('leave.calendar') }}" class="text-xs text-violet-600 hover:text-violet-700 font-medium">Leave calendar →</a>
            </div>
            <div class="card p-4 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-2">Off today</p>
                    @forelse($offToday as $leave)
                    <div class="flex items-center gap-2.5 py-1">
                        <img src="{{ $leave->user->avatar_url }}" class="w-6 h-6 rounded-full" alt="">
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">{{ $leave->user->name }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-{{ $leave->type->color }}-100 text-{{ $leave->type->color }}-600 dark:bg-{{ $leave->type->color }}-900/30 dark:text-{{ $leave->type->color }}-400">{{ $leave->type->name }}{{ $leave->is_half_day ? ' ½' : '' }}</span>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400">Everyone's in today.</p>
                    @endforelse
                </div>
                @if($celebrations->isNotEmpty())
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-2">Coming up</p>
                    @foreach($celebrations->take(5) as $c)
                    <div class="flex items-center gap-2.5 py-1">
                        <i class="bi {{ $c->kind === 'birthday' ? 'bi-cake2 text-rose-500' : 'bi-award text-amber-500' }}"></i>
                        <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate"><strong class="font-medium">{{ $c->user->name }}</strong> · {{ $c->label }}</span>
                        <span class="text-[10px] text-slate-400">{{ $c->date->isToday() ? 'Today' : ($c->date->isTomorrow() ? 'Tomorrow' : $c->date->format('M j')) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @if($nextHoliday)
                <div class="flex items-center gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <i class="bi bi-calendar-heart text-violet-500"></i>
                    <span class="text-xs text-slate-700 dark:text-slate-300 flex-1">Next holiday: <strong class="font-medium">{{ $nextHoliday->name }}</strong></span>
                    <span class="text-[10px] text-slate-400">{{ $nextHoliday->date->format('D, M j') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Activity Feed --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent Activity</h2>
            </div>
            <div class="card overflow-hidden">
                <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-[420px] overflow-y-auto">
                    @forelse($recentActivity as $activity)
                    <div class="flex items-start gap-3 px-4 py-3">
                        <img src="{{ $activity->causer?->avatar_url ?? ''}}" alt=""
                             class="w-7 h-7 rounded-full flex-shrink-0 mt-0.5">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-snug">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $activity->causer?->name ?? 'System' }}</span>
                                {{ $activity->description }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-slate-400 text-sm">
                        <i class="bi bi-activity text-2xl block mb-2 text-slate-300"></i>
                        No recent activity
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('projects.create') }}"
                   class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                        <i class="bi bi-folder-plus text-violet-600 dark:text-violet-400"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">New Project</span>
                </a>
                <a href="{{ route('daily-reports.create') }}"
                   class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                        <i class="bi bi-journal-plus text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Daily Report</span>
                </a>
                <a href="{{ route('bugs.index') }}"
                   class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                        <i class="bi bi-bug-fill text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Bug Tracker</span>
                </a>
                <a href="{{ route('time-entries.index') }}"
                   class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                        <i class="bi bi-stopwatch text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Time Tracker</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
