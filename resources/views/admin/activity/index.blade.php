@extends('layouts.app')
@section('title', 'Activity Log')

@section('content')
@include('admin._nav')

<div class="page-header">
    <div>
        <h1 class="page-title">Activity Log</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Every sign-in, sign-out and change made by every user. Times shown in {{ auth()->user()->timezone }}.</p>
    </div>
    <a href="{{ route('admin.activity.export', request()->query()) }}" class="btn-secondary">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Events today', $stats['today'], 'bi-activity', 'violet'],
        ['Sign-ins today', $stats['logins_today'], 'bi-box-arrow-in-right', 'emerald'],
        ['Failed sign-ins today', $stats['failed_today'], 'bi-shield-exclamation', 'rose'],
        ['Active users today', $stats['active_users'], 'bi-people', 'blue'],
    ] as [$label, $value, $icon, $color])
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 flex items-center justify-center flex-shrink-0">
            <i class="bi {{ $icon }} text-{{ $color }}-600 dark:text-{{ $color }}-400"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ $value }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $label }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="card p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
    <div class="lg:col-span-2">
        <label class="form-label">Search</label>
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="e.g. deleted, logged in, project name…">
    </div>
    <div>
        <label class="form-label">User</label>
        <select name="user_id" class="form-select">
            <option value="">All users</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Type</label>
        <select name="category" class="form-select">
            <option value="">All types</option>
            @foreach($categories as $key => $label)
            <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="form-input">
    </div>
    <div>
        <label class="form-label">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="form-input">
    </div>
    <div class="sm:col-span-2 lg:col-span-6 flex justify-end gap-2">
        @if(request()->hasAny(['q','user_id','category','from','to']))
        <a href="{{ route('admin.activity.index') }}" class="btn-ghost">Clear</a>
        @endif
        <button class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
    </div>
</form>

{{-- Log --}}
<div class="card overflow-hidden">
    @forelse($logs as $log)
    @php
        $p = $log->properties ?? [];
        $style = match(true) {
            $log->description === 'failed login attempt' => ['bi-shield-exclamation', 'rose'],
            $log->description === 'logged in'            => ['bi-box-arrow-in-right', 'emerald'],
            $log->description === 'logged out'           => ['bi-box-arrow-right', 'slate'],
            str_starts_with($log->description, 'deleted') || str_starts_with($log->description, 'removed') => ['bi-trash3', 'rose'],
            str_starts_with($log->description, 'created') || str_starts_with($log->description, 'added')   => ['bi-plus-circle', 'violet'],
            default => ['bi-pencil-square', 'blue'],
        };
        $date = $log->created_at->inUserTz();
    @endphp
    @if($loop->first || $logs[$loop->index - 1]->created_at->inUserTz()->toDateString() !== $date->toDateString())
    <div class="px-5 py-2 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-500 uppercase tracking-wide">
        {{ $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('l, M j, Y')) }}
    </div>
    @endif
    <div x-data="{ open: false }" class="border-b border-slate-50 dark:border-slate-800 last:border-b-0">
        <button type="button" @click="open = !open"
                class="w-full flex items-center gap-3 px-5 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
            <div class="w-8 h-8 rounded-lg bg-{{ $style[1] }}-100 dark:bg-{{ $style[1] }}-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi {{ $style[0] }} text-sm text-{{ $style[1] }}-600 dark:text-{{ $style[1] }}-400"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-slate-700 dark:text-slate-300 truncate">
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $log->causer?->name ?? ($p['email'] ?? 'Unknown user') }}</span>
                    {{ $log->description }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-2 flex-wrap">
                    <span>{{ $date->format('g:i:s A') }}</span>
                    @if(!empty($p['ip']))<span class="font-mono">{{ $p['ip'] }}</span>@endif
                    <span class="px-1.5 py-px rounded bg-slate-100 dark:bg-slate-800 text-[10px]">{{ $categories[$log->log_name] ?? $log->log_name }}</span>
                    @if(($p['status'] ?? 200) >= 400)<span class="text-rose-500">failed ({{ $p['status'] }})</span>@endif
                </p>
            </div>
            <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-collapse x-cloak class="px-5 pb-4 pl-16">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
                @if($log->causer)<div><dt class="inline text-slate-400">Email:</dt> <dd class="inline text-slate-700 dark:text-slate-300">{{ $log->causer->email }}</dd></div>@endif
                @foreach(['method' => 'Method', 'url' => 'URL', 'route' => 'Route', 'user_agent' => 'Browser'] as $k => $l)
                @if(!empty($p[$k]))<div class="truncate"><dt class="inline text-slate-400">{{ $l }}:</dt> <dd class="inline text-slate-700 dark:text-slate-300 font-mono">{{ $p[$k] }}</dd></div>@endif
                @endforeach
                <div><dt class="inline text-slate-400">Exact time (UTC):</dt> <dd class="inline text-slate-700 dark:text-slate-300 font-mono">{{ $log->created_at->toDateTimeString() }}</dd></div>
            </dl>
            @if(!empty($p['input']))
            <pre class="mt-3 text-[11px] bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg p-3 overflow-x-auto text-slate-600 dark:text-slate-400">{{ json_encode($p['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    </div>
    @empty
    <div class="empty-state py-16">
        <i class="bi bi-activity text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-sm text-slate-500 mt-3">No activity matches these filters.</p>
    </div>
    @endforelse
</div>

@if($logs->hasPages())
<div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
