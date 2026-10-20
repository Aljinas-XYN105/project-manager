@extends('layouts.app')
@section('title', $project->name.' — Calendar')

@section('content')
@php
    $firstDay = \Carbon\Carbon::create($year, $month, 1);
    $lastDay  = $firstDay->copy()->endOfMonth();
    $startWeek = $firstDay->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
    $tasksByDate = $tasks->groupBy(fn($t) => $t->due_date->toDateString());
@endphp

<div class="page-header">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-slate-600">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i><span>Calendar</span>
        </div>
        <h1 class="page-title">{{ $firstDay->format('F Y') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('projects.tasks.calendar', $project) }}?year={{ $firstDay->copy()->subMonth()->year }}&month={{ $firstDay->copy()->subMonth()->month }}"
           class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>
        <a href="{{ route('projects.tasks.calendar', $project) }}" class="btn-secondary btn-sm">Today</a>
        <a href="{{ route('projects.tasks.calendar', $project) }}?year={{ $firstDay->copy()->addMonth()->year }}&month={{ $firstDay->copy()->addMonth()->month }}"
           class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
        <a href="{{ route('projects.tasks.kanban', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-kanban"></i> Board</a>
        <a href="{{ route('projects.tasks.index', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-list-task"></i> List</a>
        <a href="{{ route('projects.tasks.backlog', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-inbox"></i> Backlog</a>
    </div>
</div>

<div class="card overflow-hidden">
    {{-- Day headers --}}
    <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
        <div class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ $day }}</div>
        @endforeach
    </div>

    {{-- Calendar grid --}}
    <div class="grid grid-cols-7">
        @php $current = $startWeek->copy(); @endphp
        @while($current->lte($lastDay->copy()->endOfWeek(\Carbon\Carbon::SATURDAY)))
        @php
            $dateStr = $current->toDateString();
            $isToday = $current->isToday();
            $isCurrentMonth = $current->month === $month;
            $dayTasks = $tasksByDate[$dateStr] ?? collect();
        @endphp
        <div class="min-h-[110px] border-b border-r border-slate-100 dark:border-slate-700/50 p-2
                    {{ !$isCurrentMonth ? 'bg-slate-50/50 dark:bg-slate-800/20' : '' }}
                    last:border-r-0 col-span-1">
            <div class="flex items-center justify-center w-7 h-7 rounded-full mb-1
                        {{ $isToday ? 'bg-violet-600 text-white font-bold' : 'text-sm font-medium '.($isCurrentMonth ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600') }}">
                {{ $current->day }}
            </div>
            <div class="space-y-1">
                @foreach($dayTasks->take(3) as $task)
                <a href="{{ route('projects.tasks.show', $task) }}"
                   class="block text-[11px] px-1.5 py-0.5 rounded text-white truncate hover:opacity-90 transition-opacity"
                   style="background-color: {{ $task->project->color ?? '#3B82F6' }}"
                   title="{{ $task->title }}">
                    {{ $task->title }}
                </a>
                @endforeach
                @if($dayTasks->count() > 3)
                <span class="text-[11px] text-slate-400 pl-1.5">+{{ $dayTasks->count() - 3 }} more</span>
                @endif
            </div>
        </div>
        @php $current->addDay(); @endphp
        @endwhile
    </div>
</div>
@endsection
