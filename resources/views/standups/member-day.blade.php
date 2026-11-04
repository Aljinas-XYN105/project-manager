@extends('layouts.app')
@section('title', $user->name . '\'s Standup — ' . $day->format('M j, Y'))

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('standups.index', ['date' => $day->toDateString()]) }}"
                   class="hover:text-violet-600 transition-colors flex items-center gap-1">
                    <i class="bi bi-arrow-left text-xs"></i> Back
                </a>
                <i class="bi bi-dot"></i>
                <span>{{ $day->isToday() ? 'Today' : $day->format('l, F j, Y') }}</span>
            </div>
            <h1 class="page-title flex items-center gap-3">
                <span class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center font-bold text-violet-600 dark:text-violet-400 text-sm flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </span>
                {{ $user->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $user->job_title ?? $user->department ?? $user->email }}
            </p>
        </div>
    </div>

    @if(!$standup || $standup->items->isEmpty())
    <div class="card text-center py-14">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
            <i class="bi bi-calendar-x text-xl text-slate-400"></i>
        </div>
        <h3 class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No standup submitted</h3>
        <p class="text-sm text-slate-400">{{ $user->name }} hasn't submitted a standup for this day.</p>
    </div>
    @else

    {{-- Blockers banner --}}
    @if($standup->blockers)
    <div class="mb-5 px-4 py-3 rounded-xl border border-rose-200 dark:border-rose-800/40
                bg-rose-50 dark:bg-rose-900/10 flex items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
        <div>
            <p class="text-sm font-semibold text-rose-700 dark:text-rose-400 mb-0.5">Blocker</p>
            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ $standup->blockers }}</p>
        </div>
    </div>
    @endif

    {{-- Kanban board --}}
    @php
        $columns = [
            'todo'        => ['label' => 'Todo',        'icon' => 'bi-circle',               'color' => 'text-slate-500',                    'bg' => 'bg-slate-100 dark:bg-slate-800',         'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400',  'pill' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'],
            'in_progress' => ['label' => 'In Progress', 'icon' => 'bi-arrow-clockwise',      'color' => 'text-blue-600 dark:text-blue-400',  'bg' => 'bg-blue-50 dark:bg-blue-900/20',         'border' => 'border-blue-200 dark:border-blue-800/40','dot' => 'bg-blue-500',   'pill' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'],
            'in_review'   => ['label' => 'In Review',   'icon' => 'bi-eye',                  'color' => 'text-amber-600 dark:text-amber-400','bg' => 'bg-amber-50 dark:bg-amber-900/20',       'border' => 'border-amber-200 dark:border-amber-800/40','dot'=> 'bg-amber-500', 'pill' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'],
            'done'        => ['label' => 'Done',        'icon' => 'bi-check-circle-fill',    'color' => 'text-green-600 dark:text-green-400','bg' => 'bg-green-50 dark:bg-green-900/20',       'border' => 'border-green-200 dark:border-green-800/40','dot'=> 'bg-green-500', 'pill' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($columns as $status => $col)
        @php $items = $standup->items->where('status', $status)->values() @endphp
        <div class="rounded-2xl {{ $col['border'] }} border {{ $col['bg'] }} p-3 flex flex-col gap-2 min-h-[120px]">

            {{-- Column header --}}
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                    <i class="bi {{ $col['icon'] }} text-sm {{ $col['color'] }}"></i>
                    <span class="text-xs font-bold uppercase tracking-wide {{ $col['color'] }}">{{ $col['label'] }}</span>
                </div>
                @if($items->count())
                <span class="text-xs font-bold {{ $col['color'] }} opacity-70">{{ $items->count() }}</span>
                @endif
            </div>

            {{-- Cards --}}
            @forelse($items as $item)
            <div class="bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm border border-slate-100 dark:border-slate-700">
                <p class="text-sm text-slate-700 dark:text-slate-200 leading-snug">{{ $item->description }}</p>
            </div>
            @empty
            <div class="flex-1 flex items-center justify-center">
                <span class="text-xs text-slate-300 dark:text-slate-600">Empty</span>
            </div>
            @endforelse
        </div>
        @endforeach
    </div>

    {{-- Summary footer --}}
    <div class="flex items-center gap-3 flex-wrap mt-4">
        @foreach($columns as $status => $col)
        @php $cnt = $standup->items->where('status', $status)->count() @endphp
        @if($cnt)
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $col['pill'] }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $col['dot'] }}"></span>
            {{ $cnt }} {{ $col['label'] }}
        </span>
        @endif
        @endforeach
        <span class="text-xs text-slate-400 ml-auto">{{ $standup->items->count() }} {{ Str::plural('item', $standup->items->count()) }} total</span>
    </div>

    @endif

</div>
@endsection
