@extends('layouts.app')
@section('title', 'Standup Calendar — ' . $firstDay->format('F Y'))

@section('content')
@php
$lastDay    = $firstDay->copy()->endOfMonth();
$startWeek  = $firstDay->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
$today      = \Carbon\Carbon::today();
$selectedStr = $returnDate ?? $today->toDateString();

$prevMonth = $firstDay->copy()->subMonth();
$nextMonth = $firstDay->copy()->addMonth();
$canGoNext = $nextMonth->lte($today->copy()->startOfMonth());

$avatarBgs   = ['#ede9fe','#dbeafe','#d1fae5','#fef3c7','#fee2e2','#e0e7ff','#ccfbf1','#fce7f3','#ddd6fe','#bfdbfe'];
$avatarTexts = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#be185d','#6d28d9','#1d4ed8'];
@endphp

{{-- Header --}}
<div class="page-header">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('standups.index') }}" class="hover:text-violet-600 transition-colors flex items-center gap-1">
                <i class="bi bi-arrow-left text-[10px]"></i> Standups
            </a>
        </div>
        <h1 class="page-title">{{ $firstDay->format('F Y') }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Click a date to view that day's standups.</p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('standups.calendar') }}?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}&return_date={{ $selectedStr }}"
           class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>

        <a href="{{ route('standups.calendar') }}?return_date={{ $selectedStr }}"
           class="btn-secondary btn-sm">This month</a>

        @if($canGoNext)
        <a href="{{ route('standups.calendar') }}?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}&return_date={{ $selectedStr }}"
           class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
        @else
        <span class="btn-ghost btn-sm opacity-30 cursor-not-allowed"><i class="bi bi-chevron-right"></i></span>
        @endif
    </div>
</div>

{{-- Calendar --}}
<div class="card overflow-hidden">

    {{-- Day headers --}}
    <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayLabel)
        <div class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
            {{ $dayLabel }}
        </div>
        @endforeach
    </div>

    {{-- Calendar grid --}}
    <div class="grid grid-cols-7 divide-x divide-y divide-slate-100 dark:divide-slate-700/50">
        @php $current = $startWeek->copy(); @endphp

        @while($current->lte($lastDay->copy()->endOfWeek(\Carbon\Carbon::SATURDAY)))
        @php
            $dateStr        = $current->toDateString();
            $isToday        = $current->isSameDay($today);
            $isSelected     = $dateStr === $selectedStr;
            $isFuture       = $current->gt($today);
            $isCurrentMonth = $current->month === $firstDay->month;
            $isWeekend      = in_array($current->dayOfWeek, [0, 6]);
            $dayStandups    = $standupsByDate[$dateStr] ?? collect();
        @endphp

        <div class="relative min-h-[130px] flex flex-col
                    {{ $isToday ? 'bg-violet-50/60 dark:bg-violet-900/10' : '' }}
                    {{ !$isCurrentMonth ? 'bg-slate-50/70 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900' }}
                    {{ !$isFuture && $isCurrentMonth ? 'hover:bg-violet-50/40 dark:hover:bg-violet-900/5 cursor-pointer' : '' }}
                    group transition-colors">

            {{-- Clickable overlay --}}
            @if(!$isFuture && $isCurrentMonth)
            <a href="{{ route('standups.index') }}?date={{ $dateStr }}"
               class="absolute inset-0 z-10" aria-label="{{ $current->format('F j, Y') }}"></a>
            @endif

            {{-- Cell content --}}
            <div class="p-2.5 flex flex-col gap-1.5 flex-1 relative z-20 pointer-events-none">

                {{-- Day number — top left --}}
                <div class="flex items-start justify-between">
                    <div class="flex-shrink-0">
                        @if($isToday)
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full
                                     bg-violet-600 text-white text-xs font-bold leading-none">
                            {{ $current->day }}
                        </span>
                        @elseif($isSelected && $isCurrentMonth && !$isToday)
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full
                                     ring-2 ring-violet-400 ring-offset-1 text-violet-700 dark:text-violet-300 text-xs font-semibold leading-none">
                            {{ $current->day }}
                        </span>
                        @else
                        <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-medium leading-none
                                     {{ !$isCurrentMonth || $isFuture ? 'text-slate-300 dark:text-slate-600'
                                      : ($isWeekend ? 'text-slate-400 dark:text-slate-500'
                                      : 'text-slate-700 dark:text-slate-300') }}">
                            {{ $current->day }}
                        </span>
                        @endif
                    </div>

                    {{-- Submission count badge --}}
                    @if($dayStandups->count() > 0 && $isCurrentMonth)
                    <span class="text-[10px] font-semibold text-violet-500 dark:text-violet-400 leading-none mt-1 flex-shrink-0">
                        {{ $dayStandups->count() }}
                    </span>
                    @endif
                </div>

                {{-- Standup chips --}}
                @if($isCurrentMonth && $dayStandups->count() > 0)
                <div class="flex flex-col gap-0.5 mt-0.5">
                    @foreach($dayStandups->take(3) as $standup)
                    @php
                        $chipUser  = $standup->user;
                        $chipIdx   = ($chipUser->id ?? 0) % count($avatarBgs);
                        $chipBg    = $avatarBgs[$chipIdx];
                        $chipColor = $avatarTexts[$chipIdx];
                    @endphp
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium truncate leading-tight pointer-events-auto"
                          style="background: {{ $chipBg }}20; color: {{ $chipColor }}">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: {{ $chipColor }}"></span>
                        {{ $chipUser->name ?? 'Unknown' }}
                    </span>
                    @endforeach
                    @if($dayStandups->count() > 3)
                    <span class="text-[10px] text-slate-400 pl-1">+{{ $dayStandups->count() - 3 }} more</span>
                    @endif
                </div>
                @endif

            </div>
        </div>

        @php $current->addDay(); @endphp
        @endwhile
    </div>
</div>

{{-- Legend --}}
<div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
    <span class="flex items-center gap-1.5">
        <span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>
        Today
    </span>
    <span class="flex items-center gap-1.5">
        <span class="w-2.5 h-2.5 rounded bg-violet-100 dark:bg-violet-900/40"></span>
        Standup submitted
    </span>
</div>

@endsection
