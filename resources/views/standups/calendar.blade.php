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
        <p class="text-sm text-slate-500 mt-0.5">Select a date to view that day's standups.</p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('standups.calendar') }}?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}&return_date={{ $selectedStr }}"
           class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>

        <a href="{{ route('standups.calendar') }}?return_date={{ $selectedStr }}"
           class="btn-secondary btn-sm">Today</a>

        @if($canGoNext)
        <a href="{{ route('standups.calendar') }}?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}&return_date={{ $selectedStr }}"
           class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
        @else
        <span class="btn-ghost btn-sm opacity-30 cursor-not-allowed"><i class="bi bi-chevron-right"></i></span>
        @endif
    </div>
</div>

{{-- Full-width calendar grid --}}
<div class="card overflow-hidden">

    {{-- Day-of-week headers --}}
    <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayLabel)
        <div class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
            {{ $dayLabel }}
        </div>
        @endforeach
    </div>

    {{-- Calendar cells --}}
    <div class="grid grid-cols-7">
        @php $current = $startWeek->copy(); @endphp

        @while($current->lte($lastDay->copy()->endOfWeek(\Carbon\Carbon::SATURDAY)))
        @php
            $dateStr        = $current->toDateString();
            $isToday        = $current->isSameDay($today);
            $isSelected     = $dateStr === $selectedStr;
            $isFuture       = $current->gt($today);
            $isCurrentMonth = $current->month === $firstDay->month;
            $isWeekend      = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
        @endphp

        <div class="min-h-[100px] border-b border-r border-slate-100 dark:border-slate-700/50
                    {{ !$isCurrentMonth ? 'bg-slate-50/40 dark:bg-slate-800/20' : '' }}
                    {{ $isFuture || !$isCurrentMonth ? '' : 'hover:bg-violet-50/40 dark:hover:bg-violet-900/10' }}
                    relative group">

            @if(!$isFuture && $isCurrentMonth)
            <a href="{{ route('standups.index') }}?date={{ $dateStr }}"
               class="absolute inset-0 z-10" aria-label="{{ $current->format('F j, Y') }}"></a>
            @endif

            <div class="p-2.5 relative z-20 pointer-events-none">
                {{-- Day number --}}
                <div class="flex items-center justify-center w-8 h-8 rounded-full mb-1 mx-auto
                            {{ $isToday ? 'bg-violet-600 text-white font-bold text-sm'
                             : ($isSelected && $isCurrentMonth && !$isToday ? 'ring-2 ring-violet-400 ring-offset-1 font-semibold text-sm text-violet-700 dark:text-violet-300'
                             : ($isFuture || !$isCurrentMonth ? 'text-slate-300 dark:text-slate-600 text-sm'
                             : ($isWeekend ? 'text-slate-400 dark:text-slate-500 text-sm font-medium'
                             : 'text-slate-700 dark:text-slate-200 text-sm font-medium'))) }}">
                    {{ $current->day }}
                </div>

                {{-- Today label --}}
                @if($isToday)
                <p class="text-center text-[10px] font-semibold text-violet-600 dark:text-violet-400">Today</p>
                @endif
            </div>
        </div>

        @php $current->addDay(); @endphp
        @endwhile
    </div>
</div>

@endsection
