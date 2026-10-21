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

{{-- Active Timers Widget --}}
@if($activeTimers->isNotEmpty())
@php
$activeTimersJson = $activeTimers->map(function($e) {
    return [
        'id'             => $e->id,
        'status'         => $e->status,
        'task_id'        => $e->task_id,
        'task_title'     => $e->task?->title ?? $e->description ?? 'Untitled',
        'project_name'   => $e->task?->project?->name ?? '',
        'project_color'  => $e->task?->project?->color ?? '#6366f1',
        'started_at'     => $e->started_at?->toIso8601String(),
        'paused_seconds' => $e->paused_seconds ?? 0,
        'elapsed_seconds'=> $e->elapsed_seconds,
        'pause_url'      => route('time-entries.pause', $e),
        'resume_url'     => route('time-entries.resume', $e),
        'stop_url'       => route('time-entries.stop', $e),
        'task_url'       => $e->task_id ? route('projects.tasks.show', $e->task_id) : null,
    ];
});
@endphp
<div x-data="dashTimers(window.__dashTimersData)" class="mb-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" x-show="runningCount > 0"></div>
            <div class="w-2 h-2 rounded-full bg-amber-400" x-show="runningCount === 0"></div>
            <span class="text-sm font-semibold text-slate-800 dark:text-white">
                Active Timers
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300"
                  x-text="entries.length + ' active'"></span>
        </div>
        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span x-show="runningCount > 0">
                <i class="bi bi-play-circle-fill text-emerald-500 mr-1"></i>
                <span x-text="runningCount + ' running'"></span>
            </span>
            <span x-show="pausedCount > 0">
                <i class="bi bi-pause-circle-fill text-amber-400 mr-1"></i>
                <span x-text="pausedCount + ' paused'"></span>
            </span>
        </div>
    </div>

    {{-- Timer cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <template x-for="entry in entries" :key="entry.id">
            <div class="rounded-2xl border p-4 flex flex-col gap-3 transition-all"
                 :class="entry.status === 'running'
                     ? 'bg-gradient-to-br from-violet-50 to-indigo-50 dark:from-violet-900/20 dark:to-indigo-900/20 border-violet-200 dark:border-violet-800 shadow-sm'
                     : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700'">

                {{-- Top row: project dot + task name + elapsed --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-0.5"
                              :style="'background:' + entry.project_color"></span>
                        <div class="min-w-0">
                            <a :href="entry.task_url ?? '#'"
                               class="text-sm font-semibold text-slate-800 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 truncate block leading-tight"
                               x-text="entry.task_title"></a>
                            <p class="text-xs text-slate-400 dark:text-slate-500 truncate mt-0.5" x-text="entry.project_name"></p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <span class="font-mono text-base font-bold tabular-nums"
                              :class="entry.status === 'running' ? 'text-violet-700 dark:text-violet-300' : 'text-slate-500 dark:text-slate-400'"
                              x-text="entry.elapsed_formatted"></span>
                    </div>
                </div>

                {{-- Status badge --}}
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="entry.status === 'running'
                              ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300'
                              : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'">
                        <i :class="entry.status === 'running' ? 'bi bi-play-fill' : 'bi bi-pause-fill'"></i>
                        <span x-text="entry.status === 'running' ? 'Running' : 'Paused'"></span>
                    </span>

                    {{-- Action buttons --}}
                    <div class="flex items-center gap-1.5">
                        {{-- Pause --}}
                        <button x-show="entry.status === 'running'"
                                @click="pauseTimer(entry)"
                                :disabled="entry.loading"
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-amber-50 dark:hover:bg-amber-900/30 hover:text-amber-600 dark:hover:text-amber-400 border border-slate-200 dark:border-slate-600 transition-colors disabled:opacity-50"
                                title="Pause">
                            <i class="bi bi-pause-fill text-sm"></i>
                        </button>
                        {{-- Resume --}}
                        <button x-show="entry.status === 'paused'"
                                @click="resumeTimer(entry)"
                                :disabled="entry.loading"
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 hover:text-emerald-600 dark:hover:text-emerald-400 border border-slate-200 dark:border-slate-600 transition-colors disabled:opacity-50"
                                title="Resume">
                            <i class="bi bi-play-fill text-sm"></i>
                        </button>
                        {{-- Stop --}}
                        <button @click="stopTimer(entry)"
                                :disabled="entry.loading"
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 border border-slate-200 dark:border-slate-600 transition-colors disabled:opacity-50"
                                title="Stop">
                            <i class="bi bi-stop-fill text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@push('scripts')
<script>
window.__dashTimersData = @json($activeTimersJson);

function dashTimers(initial) {
    function _map(e) {
        return { ...e, elapsed_formatted: formatSeconds(e.elapsed_seconds), loading: false };
    }
    const mapped = initial.map(_map);
    return {
        entries: mapped,
        runningCount: mapped.filter(e => e.status === 'running').length,
        pausedCount:  mapped.filter(e => e.status === 'paused').length,

        _sync() {
            this.runningCount = this.entries.filter(e => e.status === 'running').length;
            this.pausedCount  = this.entries.filter(e => e.status === 'paused').length;
        },

        init() {
            this.tick();
            setInterval(() => this.tick(), 1000);
        },

        tick() {
            this.entries = this.entries.map(e => {
                if (e.status !== 'running') return e;
                const now   = Math.floor(Date.now() / 1000);
                const start = Math.floor(new Date(e.started_at).getTime() / 1000);
                const elapsed = Math.max(0, now - start - (e.paused_seconds || 0));
                return { ...e, elapsed_seconds: elapsed, elapsed_formatted: formatSeconds(elapsed) };
            });
            this._sync();
        },

        async pauseTimer(entry) {
            entry.loading = true;
            try {
                const r = await fetch(entry.pause_url, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                });
                const d = await r.json();
                const idx = this.entries.findIndex(e => e.id === entry.id);
                if (idx >= 0) {
                    this.entries[idx] = {
                        ...this.entries[idx],
                        status: d.entry.status,
                        paused_seconds: d.entry.paused_seconds,
                        elapsed_seconds: d.entry.elapsed_seconds,
                        elapsed_formatted: formatSeconds(d.entry.elapsed_seconds),
                        loading: false,
                    };
                    this._sync();
                }
            } catch (e) { entry.loading = false; }
        },

        async resumeTimer(entry) {
            entry.loading = true;
            try {
                const r = await fetch(entry.resume_url, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                });
                const d = await r.json();
                const idx = this.entries.findIndex(e => e.id === entry.id);
                if (idx >= 0) {
                    this.entries[idx] = {
                        ...this.entries[idx],
                        status: d.entry.status,
                        paused_seconds: d.entry.paused_seconds,
                        started_at: d.entry.started_at,
                        elapsed_seconds: d.entry.elapsed_seconds,
                        elapsed_formatted: formatSeconds(d.entry.elapsed_seconds),
                        loading: false,
                    };
                    this._sync();
                }
            } catch (e) { entry.loading = false; }
        },

        async stopTimer(entry) {
            if (!confirm('Stop this timer?')) return;
            entry.loading = true;
            try {
                await fetch(entry.stop_url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                this.entries = this.entries.filter(e => e.id !== entry.id);
                this._sync();
            } catch (e) { entry.loading = false; }
        },
    };
}

function formatSeconds(s) {
    s = Math.max(0, Math.floor(s));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
}
</script>
@endpush
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
