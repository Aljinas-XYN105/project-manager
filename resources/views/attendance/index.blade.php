@extends('layouts.app')
@section('title', 'My Attendance')

@section('content')
@php
    $fmt = fn ($m) => sprintf('%dh %02dm', intdiv($m, 60), $m % 60);
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">My Attendance</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Your sign-in and sign-out times. Times shown in {{ auth()->user()->timezone }}.</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" class="flex items-center gap-1">
            <a href="?month={{ $month->copy()->subMonth()->format('Y-m') }}" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" class="form-input py-1.5 text-sm w-40">
            <a href="?month={{ $month->copy()->addMonth()->format('Y-m') }}" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
        </form>
        <form method="POST" action="{{ route($open ? 'attendance.clock-out' : 'attendance.clock-in') }}">
            @csrf
            <button class="{{ $open ? 'btn-secondary' : 'btn-primary' }}">
                <i class="bi {{ $open ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right' }}"></i>
                {{ $open ? 'Clock out' : 'Clock in' }}
            </button>
        </form>
    </div>
</div>

@if($open)
<div class="card p-4 mb-6 flex items-center gap-3 border-l-4 border-l-emerald-400">
    <span class="relative flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
    </span>
    <p class="text-sm text-slate-700 dark:text-slate-300">
        You're clocked in since <strong>{{ $open->clock_in_at->inUserTz()->format('g:i A') }}</strong>
        ({{ $open->duration_formatted }} so far).
    </p>
</div>
@endif

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Days present', $summary['days'], 'bi-calendar-check', 'violet'],
        ['Total time', $fmt($summary['total_minutes']), 'bi-hourglass-split', 'emerald'],
        ['Average per day', $fmt($summary['avg_minutes']), 'bi-graph-up', 'blue'],
        ['Avg. first sign-in', $summary['first_in_avg'], 'bi-sunrise', 'amber'],
    ] as [$label, $value, $icon, $color])
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 flex items-center justify-center flex-shrink-0">
            <i class="bi {{ $icon }} text-{{ $color }}-600 dark:text-{{ $color }}-400"></i>
        </div>
        <div>
            <p class="text-xl font-bold text-slate-900 dark:text-white leading-none">{{ $value }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $label }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
        <h2 class="font-semibold text-slate-900 dark:text-white">{{ $month->format('F Y') }}</h2>
    </div>
    @forelse($byDay as $date => $sessions)
    @php $day = \Carbon\Carbon::parse($date); @endphp
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5 border-b border-slate-50 dark:border-slate-800 last:border-b-0">
        <div class="w-32 flex-shrink-0">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $day->format('D, M j') }}</p>
            <p class="text-xs text-slate-400">{{ $sessions->count() }} {{ Str::plural('session', $sessions->count()) }}</p>
        </div>
        <div class="flex-1 flex flex-wrap gap-2">
            @foreach($sessions->sortBy('clock_in_at') as $s)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300">
                <i class="bi bi-box-arrow-in-right text-emerald-500"></i>{{ $s->clock_in_at->inUserTz()->format('g:i A') }}
                <i class="bi bi-arrow-right text-slate-300"></i>
                @if($s->clock_out_at)
                <i class="bi bi-box-arrow-right text-rose-400"></i>{{ $s->clock_out_at->inUserTz()->format('g:i A') }}
                @else
                <span class="text-emerald-600 font-medium">now</span>
                @endif
                @if($s->source !== 'login')
                <span class="text-[10px] text-slate-400">({{ $s->source === 'admin' ? 'added by admin' : 'manual' }})</span>
                @endif
            </span>
            @endforeach
        </div>
        <span class="font-mono text-sm font-semibold text-slate-900 dark:text-white tabular-nums">{{ $fmt($sessions->sum('duration_minutes')) }}</span>
    </div>
    @empty
    <div class="empty-state py-14">
        <i class="bi bi-calendar-x text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-sm text-slate-500 mt-3">No attendance recorded in {{ $month->format('F Y') }}.</p>
        <p class="text-xs text-slate-400 mt-1">Signing in records your start time automatically; signing out records your end time.</p>
    </div>
    @endforelse
</div>
@endsection
