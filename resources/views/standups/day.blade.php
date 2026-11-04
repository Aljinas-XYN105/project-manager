@extends('layouts.app')
@section('title', 'Standups — ' . $day->format('l, F j, Y'))

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('standups.index') }}" class="hover:text-violet-600 transition-colors">Standups</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span>{{ $day->format('F j, Y') }}</span>
            </div>
            <h1 class="page-title">{{ $day->format('l') }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $day->format('F j, Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('standups.index', ['month' => $day->format('Y-m')]) }}"
               class="btn-ghost btn-sm">
                <i class="bi bi-calendar3 text-sm"></i> Calendar
            </a>
            @if($day->isToday())
            <a href="{{ route('standups.show', $day->toDateString()) }}" class="btn-primary">
                <i class="bi bi-pencil-square text-sm"></i> My Standup
            </a>
            @endif
        </div>
    </div>

    {{-- Day nav --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('standups.day', $day->copy()->subDay()->toDateString()) }}"
           class="btn-ghost btn-sm">
            <i class="bi bi-chevron-left"></i> Previous day
        </a>
        @if(!$day->isToday())
        <a href="{{ route('standups.day', today()->toDateString()) }}"
           class="text-xs text-slate-400 hover:text-violet-600 transition-colors">
            Jump to today
        </a>
        @endif
        <a href="{{ route('standups.day', $day->copy()->addDay()->toDateString()) }}"
           class="btn-ghost btn-sm">
            Next day <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    @if($standups->isEmpty())
    {{-- Empty state --}}
    <div class="card text-center py-16">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-calendar-x text-2xl text-slate-400"></i>
        </div>
        <h3 class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No standups for this day</h3>
        <p class="text-sm text-slate-400">
            @if($day->isToday())
                Be the first to submit your standup today.
            @else
                No team members submitted standups on this date.
            @endif
        </p>
        @if($day->isToday())
        <a href="{{ route('standups.show', $day->toDateString()) }}" class="btn-primary mt-4 inline-flex">
            <i class="bi bi-plus-lg text-sm"></i> Add My Standup
        </a>
        @endif
    </div>
    @else
    {{-- Member cards --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach($standups as $standup)
        @php
            $todoCount       = $standup->items->where('status', 'todo')->count();
            $inProgressCount = $standup->items->where('status', 'in_progress')->count();
            $inReviewCount   = $standup->items->where('status', 'in_review')->count();
            $doneCount       = $standup->items->where('status', 'done')->count();
            $totalCount      = $standup->items->count();
        @endphp
        <a href="{{ route('standups.member-day', [$day->toDateString(), $standup->user_id]) }}"
           class="card hover:border-violet-300 dark:hover:border-violet-700 transition-all group">
            {{-- Member info --}}
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0 font-bold text-violet-600 dark:text-violet-400 text-sm">
                    {{ strtoupper(substr($standup->user->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $standup->user->name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ $standup->user->email }}</p>
                </div>
                <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 ml-auto group-hover:text-violet-500 transition-colors flex-shrink-0"></i>
            </div>

            {{-- Status pill counts --}}
            <div class="flex flex-wrap gap-1.5 mb-3">
                @if($inProgressCount)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    {{ $inProgressCount }} in progress
                </span>
                @endif
                @if($inReviewCount)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    {{ $inReviewCount }} in review
                </span>
                @endif
                @if($doneCount)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    {{ $doneCount }} done
                </span>
                @endif
                @if($todoCount)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    {{ $todoCount }} todo
                </span>
                @endif
            </div>

            {{-- Preview items --}}
            @if($standup->items->count())
            <ul class="space-y-1">
                @foreach($standup->items->take(3) as $item)
                <li class="text-xs text-slate-500 dark:text-slate-400 flex items-start gap-1.5 leading-snug">
                    <i class="bi bi-dash flex-shrink-0 mt-0.5 text-slate-300"></i>
                    <span class="truncate">{{ $item->description }}</span>
                </li>
                @endforeach
                @if($standup->items->count() > 3)
                <li class="text-xs text-slate-400 pl-4">+{{ $standup->items->count() - 3 }} more</li>
                @endif
            </ul>
            @endif

            {{-- Blockers badge --}}
            @if($standup->blockers)
            <div class="mt-3 flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400">
                <i class="bi bi-exclamation-triangle-fill text-[10px]"></i>
                Has blockers
            </div>
            @endif
        </a>
        @endforeach
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">
        {{ $standups->count() }} {{ Str::plural('member', $standups->count()) }} submitted standups
    </p>
    @endif

</div>
@endsection
