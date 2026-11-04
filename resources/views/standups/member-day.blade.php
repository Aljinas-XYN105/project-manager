@extends('layouts.app')
@section('title', $user->name . ' — Standup')

@section('content')
@php
$columns = [
    'todo'        => ['label' => 'To Do',       'dot' => 'bg-slate-400',  'border' => 'border-slate-300 dark:border-slate-600',  'head' => 'text-slate-600 dark:text-slate-300'],
    'in_progress' => ['label' => 'In Progress',  'dot' => 'bg-blue-500',   'border' => 'border-blue-300 dark:border-blue-700',    'head' => 'text-blue-600 dark:text-blue-400'],
    'in_review'   => ['label' => 'In Review',    'dot' => 'bg-amber-400',  'border' => 'border-amber-300 dark:border-amber-700',  'head' => 'text-amber-600 dark:text-amber-400'],
    'qa_testing'  => ['label' => 'QA Testing',   'dot' => 'bg-violet-500', 'border' => 'border-violet-300 dark:border-violet-700','head' => 'text-violet-600 dark:text-violet-400'],
    'done'        => ['label' => 'Done',         'dot' => 'bg-green-500',  'border' => 'border-green-300 dark:border-green-700',  'head' => 'text-green-600 dark:text-green-400'],
];

$priorityClass = [
    'urgent'   => 'text-rose-500',
    'high'     => 'text-orange-500',
    'medium'   => 'text-amber-500',
    'low'      => 'text-slate-400',
];
@endphp

{{-- Full-width layout for the kanban --}}
<div class="max-w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6 gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('standups.index', ['date' => $day->toDateString()]) }}"
               class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center
                      text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center
                        font-bold text-violet-600 dark:text-violet-400 text-base flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-tight truncate">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $user->job_title ?? $user->department ?? $user->email }}
                    <span class="mx-1.5 text-slate-300">·</span>
                    {{ $day->format('l, F j') }}
                </p>
            </div>
        </div>

        {{-- Date at top right --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('standups.calendar') }}?return_date={{ $day->toDateString() }}"
               class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700
                      bg-white dark:bg-slate-800 text-sm font-medium text-slate-600 dark:text-slate-300
                      hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
                <i class="bi bi-calendar3 text-violet-500 text-sm"></i>
                {{ $day->isToday() ? 'Today' : $day->format('D, M j') }}
            </a>
        </div>
    </div>

    {{-- Hint --}}
    <div class="flex items-center gap-2 text-xs text-slate-400 mb-5">
        <i class="bi bi-info-circle flex-shrink-0"></i>
        Showing all active tasks assigned to {{ $user->name }}.
        <span class="font-medium text-slate-500">{{ $tasks->count() }} {{ Str::plural('task', $tasks->count()) }}</span>
    </div>

    {{-- Kanban board --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-3 items-start">
        @foreach($columns as $status => $col)
        @php $items = $tasksByStatus->get($status, collect()) @endphp
        <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border {{ $col['border'] }} border-opacity-60 p-3 flex flex-col gap-2 min-h-[200px]">

            {{-- Column header --}}
            <div class="flex items-center gap-2 mb-1 px-0.5">
                <span class="w-2 h-2 rounded-full {{ $col['dot'] }} flex-shrink-0"></span>
                <span class="text-xs font-bold {{ $col['head'] }}">{{ $col['label'] }}</span>
                <span class="ml-auto text-xs font-semibold {{ $col['head'] }} opacity-70">{{ $items->count() }}</span>
            </div>

            {{-- Task cards --}}
            @forelse($items as $task)
            <div class="bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm border border-slate-100 dark:border-slate-700
                        hover:shadow-md hover:border-violet-200 dark:hover:border-violet-700 transition-all group">

                {{-- Project + ticket key --}}
                @if($task->project)
                <div class="flex items-center gap-1.5 mb-1.5">
                    <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide truncate max-w-[80%]">
                        {{ $task->project->name }}
                    </span>
                    <span class="text-[10px] font-bold text-violet-500 dark:text-violet-400 flex-shrink-0">
                        · {{ $task->ticket_key }}
                    </span>
                </div>
                @endif

                {{-- Title --}}
                <p class="text-sm text-slate-700 dark:text-slate-200 leading-snug font-medium">{{ $task->title }}</p>

                {{-- Footer: priority + due date --}}
                @if($task->priority || $task->due_date)
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    @if($task->priority)
                    <span class="text-[11px] font-semibold capitalize {{ $priorityClass[$task->priority] ?? 'text-slate-400' }}">
                        {{ ucfirst($task->priority) }}
                    </span>
                    @endif
                    @if($task->due_date)
                    <span class="flex items-center gap-1 text-[11px] {{ $task->due_date->isPast() ? 'text-rose-500' : 'text-slate-400' }}">
                        <i class="bi bi-calendar2 text-[10px]"></i>
                        {{ $task->due_date->format('M j') }}
                    </span>
                    @endif
                </div>
                @endif
            </div>
            @empty
            <div class="flex-1 flex items-center justify-center">
                <span class="text-xs text-slate-300 dark:text-slate-600">Empty</span>
            </div>
            @endforelse
        </div>
        @endforeach
    </div>

</div>
@endsection
