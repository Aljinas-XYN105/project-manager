@extends('layouts.app')
@section('title', 'Time Tracking')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Time Tracking</h1>
        <p class="text-sm text-slate-500 mt-0.5">Week of {{ \Carbon\Carbon::parse($from)->format('M j') }} – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Hours</span>
        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $summary['total_hours'] }}h</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium uppercase tracking-wide">Entries</span>
        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $summary['entries']->count() }}</span>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="flex gap-3 mb-5">
    <input type="date" name="from" value="{{ $from }}" class="form-input w-auto">
    <input type="date" name="to" value="{{ $to }}" class="form-input w-auto">
    <button type="submit" class="btn-primary btn-sm">Filter</button>
</form>

{{-- Entries grouped by date --}}
@php $grouped = $summary['entries']->groupBy(fn($e) => $e->started_at->toDateString()); @endphp
@forelse($grouped as $date => $entries)
<div class="mb-5">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">
            {{ \Carbon\Carbon::parse($date)->format('l, M j') }}
        </h3>
        <span class="text-xs text-slate-500">
            {{ gmdate('H:i:s', $entries->sum('duration_seconds')) }}
        </span>
    </div>
    <div class="card overflow-hidden divide-y divide-slate-50 dark:divide-slate-700">
        @foreach($entries as $entry)
        <div class="flex items-center gap-4 px-4 py-3" x-data="{editing: false}">
            <div class="w-2 h-8 rounded-full bg-violet-200 dark:bg-violet-800 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $entry->task->title }}</p>
                <p class="text-xs text-slate-400 truncate">{{ $entry->project->name }}</p>
            </div>
            @if($entry->description)
            <p class="text-xs text-slate-400 truncate max-w-xs hidden md:block">{{ $entry->description }}</p>
            @endif
            <span class="text-sm font-mono font-medium text-slate-900 dark:text-white flex-shrink-0">
                {{ $entry->duration_formatted }}
            </span>
            <span class="text-xs text-slate-400 flex-shrink-0">
                {{ $entry->started_at->format('g:i A') }} – {{ $entry->stopped_at?->format('g:i A') ?? 'running' }}
            </span>
            @can('delete', $entry)
            <button onclick="if(confirm('Delete this entry?')) { axios.delete('/time-entries/{{ $entry->id }}').then(()=>location.reload()); }"
                    class="text-slate-300 hover:text-red-500 transition-colors flex-shrink-0">
                <i class="bi bi-trash text-sm"></i>
            </button>
            @endcan
        </div>
        @endforeach
    </div>
</div>
@empty
<div class="card p-16 text-center">
    <i class="bi bi-clock text-4xl text-slate-300 dark:text-slate-600"></i>
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-4">No time entries</h3>
    <p class="text-sm text-slate-500 mt-2">Start a timer on any task to track your time</p>
</div>
@endforelse
@endsection
