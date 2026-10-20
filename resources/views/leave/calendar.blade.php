@extends('layouts.app')
@section('title', 'Team leave calendar')

@section('content')
@php
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $qs = request()->only('department');
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $month->format('F Y') }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Who's off — approved leave, pending requests (striped) and company holidays.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <select name="department" onchange="this.form.submit()" class="form-select text-sm w-auto">
                <option value="">All departments</option>
                @foreach($departments as $d)
                <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('leave.calendar', ['month' => $prev] + $qs) }}" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>
        <a href="{{ route('leave.calendar', $qs) }}" class="btn-secondary btn-sm">This month</a>
        <a href="{{ route('leave.calendar', ['month' => $next] + $qs) }}" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
        <a href="{{ route('leave.index') }}" class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Request leave</a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
        <div class="px-2 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $d }}</div>
        @endforeach
    </div>
    <div class="grid grid-cols-7">
        @for($day = $start->copy(); $day->lte($end); $day->addDay())
        @php
            $date = $day->toDateString();
            $inMonth = $day->month === $month->month;
            $holiday = $holidays->get($date);
            $off = $leaves->filter(fn ($l) => $l->start_date->toDateString() <= $date && $l->end_date->toDateString() >= $date);
            $weekend = $day->isWeekend();
        @endphp
        <div class="min-h-[108px] border-b border-r border-slate-100 dark:border-slate-800 p-1.5
                    {{ ! $inMonth ? 'bg-slate-50/60 dark:bg-slate-900/40' : ($weekend ? 'bg-slate-50/40 dark:bg-slate-800/20' : '') }}
                    {{ $holiday ? '!bg-rose-50/60 dark:!bg-rose-900/10' : '' }}">
            <div class="flex items-center justify-between mb-1">
                <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-medium
                             {{ $day->isToday() ? 'bg-violet-600 text-white' : ($inMonth ? 'text-slate-700 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600') }}">{{ $day->day }}</span>
                @if($holiday)<span class="text-[10px] text-rose-600 font-medium truncate ml-1" title="{{ $holiday->name }}">{{ $holiday->name }}</span>@endif
            </div>
            @unless($weekend || $holiday)
            <div class="space-y-0.5">
                @foreach($off->take(3) as $l)
                <div class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] truncate
                            bg-{{ $l->type->color }}-100 text-{{ $l->type->color }}-600 dark:bg-{{ $l->type->color }}-900/30 dark:text-{{ $l->type->color }}-400
                            {{ $l->status === 'pending' ? 'opacity-70 border border-dashed border-current' : '' }}"
                     title="{{ $l->user->name }} — {{ $l->type->name }}{{ $l->status === 'pending' ? ' (pending)' : '' }}{{ $l->is_half_day ? ', half day' : '' }}">
                    <span class="truncate">{{ Str::before($l->user->name, ' ') }}{{ $l->is_half_day ? ' ½' : '' }}</span>
                </div>
                @endforeach
                @if($off->count() > 3)
                <p class="text-[10px] text-slate-400 pl-1" title="{{ $off->skip(3)->pluck('user.name')->implode(', ') }}">+{{ $off->count() - 3 }} more</p>
                @endif
            </div>
            @endunless
        </div>
        @endfor
    </div>
</div>

@php $types = $leaves->pluck('type')->unique('id'); @endphp
@if($types->isNotEmpty())
<div class="flex flex-wrap gap-3 mt-4 text-xs text-slate-500">
    @foreach($types as $t)
    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-{{ $t->color }}-500"></span>{{ $t->name }}</span>
    @endforeach
    <span class="flex items-center gap-1.5"><span class="w-3 h-2.5 rounded border border-dashed border-slate-400"></span>Pending</span>
</div>
@endif
@endsection
