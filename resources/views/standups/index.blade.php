@extends('layouts.app')
@section('title', 'Standups — ' . $month->format('F Y'))

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="bi bi-calendar3 text-violet-600 dark:text-violet-400"></i>
                </span>
                Daily Standups
            </h1>
            <p class="text-sm text-slate-500 mt-1">Track what the team commits to each day</p>
        </div>
        <a href="{{ route('standups.show', today()->toDateString()) }}"
           class="btn-primary">
            <i class="bi bi-plus-lg text-sm"></i> My Standup Today
        </a>
    </div>

    {{-- Month navigator --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('standups.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"
           class="btn-ghost btn-sm">
            <i class="bi bi-chevron-left"></i>
        </a>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
            {{ $month->format('F Y') }}
        </h2>
        <a href="{{ route('standups.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}"
           class="btn-ghost btn-sm">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    {{-- Calendar grid --}}
    <div class="card overflow-hidden">
        {{-- Day-of-week headers --}}
        <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
            <div class="py-2 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide
                        {{ in_array($dow, ['Sun','Sat']) ? 'bg-slate-50 dark:bg-slate-800/50' : '' }}">
                {{ $dow }}
            </div>
            @endforeach
        </div>

        @php
            $startOfGrid = $month->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
            $endOfGrid   = $month->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
            $today       = \Carbon\Carbon::today();
            $cursor      = $startOfGrid->copy();
        @endphp

        <div class="grid grid-cols-7">
            @while ($cursor <= $endOfGrid)
            @php
                $key       = $cursor->format('Y-m-d');
                $inMonth   = $cursor->month === $month->month;
                $isToday   = $cursor->isSameDay($today);
                $isWeekend = $cursor->isWeekend();
                $entry     = $datesWithStandups[$key] ?? null;
                $isPast    = $cursor->isPast() && !$isToday;
            @endphp
            <div class="min-h-[90px] border-r border-b border-slate-100 dark:border-slate-800 p-2 relative
                        {{ $isWeekend ? 'bg-slate-50/60 dark:bg-slate-800/20' : '' }}
                        {{ !$inMonth ? 'opacity-30' : '' }}">

                {{-- Date number --}}
                <div class="flex items-center justify-between mb-1.5">
                    @if($inMonth && $entry)
                    <a href="{{ route('standups.day', $key) }}"
                       class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold transition-colors
                              {{ $isToday
                                  ? 'bg-violet-600 text-white'
                                  : 'text-slate-700 dark:text-slate-200 hover:bg-violet-100 dark:hover:bg-violet-900/30 hover:text-violet-700' }}">
                        {{ $cursor->day }}
                    </a>
                    @else
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-semibold
                                 {{ $isToday ? 'bg-violet-600 text-white' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $cursor->day }}
                    </span>
                    @endif
                </div>

                {{-- Standup badge --}}
                @if($inMonth && $entry)
                <a href="{{ route('standups.day', $key) }}"
                   class="block w-full px-2 py-1 rounded-lg text-xs font-medium transition-colors
                          bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300
                          hover:bg-violet-200 dark:hover:bg-violet-800/40">
                    <i class="bi bi-people-fill text-[10px] mr-1"></i>
                    {{ $entry->member_count }} {{ Str::plural('member', $entry->member_count) }}
                </a>
                @elseif($inMonth && $isToday)
                <a href="{{ route('standups.show', $key) }}"
                   class="block w-full px-2 py-1 rounded-lg text-xs font-medium transition-colors
                          border border-dashed border-violet-300 dark:border-violet-700 text-violet-500
                          hover:bg-violet-50 dark:hover:bg-violet-900/20">
                    + Add standup
                </a>
                @endif
            </div>
            @php $cursor->addDay() @endphp
            @endwhile
        </div>
    </div>

    {{-- Recent activity --}}
    @if($datesWithStandups->count())
    <div class="mt-6">
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Recent Standup Days</h3>
        <div class="space-y-2">
            @foreach($datesWithStandups->sortKeysDesc()->take(5) as $dateKey => $entry)
            <a href="{{ route('standups.day', $dateKey) }}"
               class="flex items-center justify-between px-4 py-3 card hover:border-violet-300 dark:hover:border-violet-700 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-calendar-check text-violet-600 dark:text-violet-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white text-sm">
                            {{ \Carbon\Carbon::parse($dateKey)->format('l, F j, Y') }}
                        </p>
                        <p class="text-xs text-slate-400">{{ $entry->member_count }} {{ Str::plural('standup', $entry->member_count) }} submitted</p>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-slate-400 text-sm"></i>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
