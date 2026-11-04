@extends('layouts.app')
@section('title', $user->name . '\'s Standup — ' . $day->format('F j, Y'))

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('standups.index') }}" class="hover:text-violet-600 transition-colors">Standups</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <a href="{{ route('standups.day', $day->toDateString()) }}" class="hover:text-violet-600 transition-colors">{{ $day->format('M j, Y') }}</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span>{{ $user->name }}</span>
            </div>
            <h1 class="page-title flex items-center gap-3">
                <span class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center font-bold text-violet-600 dark:text-violet-400 text-sm flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </span>
                {{ $user->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $day->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('standups.day', $day->toDateString()) }}" class="btn-ghost btn-sm">
            <i class="bi bi-arrow-left text-sm"></i> Back
        </a>
    </div>

    @if(!$standup)
    <div class="card text-center py-12">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
            <i class="bi bi-calendar-x text-xl text-slate-400"></i>
        </div>
        <h3 class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No standup submitted</h3>
        <p class="text-sm text-slate-400">{{ $user->name }} didn't submit a standup on this date.</p>
    </div>
    @else

    {{-- Items by status --}}
    @php
        $groups = [
            'in_progress' => ['label' => 'In Progress', 'dot' => 'bg-blue-500',   'pill' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'],
            'in_review'   => ['label' => 'In Review',   'dot' => 'bg-amber-500',  'pill' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'],
            'done'        => ['label' => 'Done',        'dot' => 'bg-green-500',  'pill' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'],
            'todo'        => ['label' => 'Todo',        'dot' => 'bg-slate-400',  'pill' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'],
        ];
    @endphp

    <div class="card">
        <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
            <i class="bi bi-list-check text-violet-500"></i>
            Tasks &amp; Work Items
            <span class="ml-auto text-xs font-normal text-slate-400">{{ $standup->items->count() }} {{ Str::plural('item', $standup->items->count()) }}</span>
        </h2>

        @if($standup->items->isEmpty())
        <p class="text-sm text-slate-400 py-4 text-center">No items in this standup.</p>
        @else
        <div class="space-y-5">
            @foreach($groups as $status => $cfg)
            @php $groupItems = $standup->items->where('status', $status) @endphp
            @if($groupItems->count())
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }}"></span>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $cfg['label'] }}</span>
                    <span class="text-xs text-slate-400 ml-1">{{ $groupItems->count() }}</span>
                </div>
                <ul class="space-y-2 pl-4">
                    @foreach($groupItems as $item)
                    <li class="flex items-start gap-2">
                        <i class="bi bi-dash flex-shrink-0 mt-0.5 text-slate-300 dark:text-slate-600"></i>
                        <span class="text-sm text-slate-700 dark:text-slate-300 leading-snug">{{ $item->description }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>

    {{-- Blockers --}}
    @if($standup->blockers)
    <div class="card border-rose-200 dark:border-rose-800/40 bg-rose-50/30 dark:bg-rose-900/10">
        <h2 class="font-semibold text-rose-700 dark:text-rose-400 flex items-center gap-2 mb-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Blockers
        </h2>
        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ $standup->blockers }}</p>
    </div>
    @endif

    {{-- Summary bar --}}
    @php
        $counts = $standup->items->groupBy('status')->map->count();
    @endphp
    <div class="flex items-center gap-3 flex-wrap mt-2">
        @foreach($groups as $status => $cfg)
        @if(($counts[$status] ?? 0) > 0)
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $cfg['pill'] }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }}"></span>
            {{ $counts[$status] }} {{ $cfg['label'] }}
        </span>
        @endif
        @endforeach
    </div>

    @endif

</div>
@endsection
