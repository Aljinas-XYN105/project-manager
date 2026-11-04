@extends('layouts.app')
@section('title', 'Standups — ' . $date->format('D, M j'))

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="bi bi-mic text-violet-600 dark:text-violet-400"></i>
                </span>
                Daily Standups
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">What the team is working on today</p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Date picker --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open=!open" @click.outside="open=false"
                        class="btn-ghost btn-sm flex items-center gap-2 font-semibold">
                    <i class="bi bi-calendar3 text-violet-500 text-sm"></i>
                    {{ $date->isToday() ? 'Today' : $date->format('D, M j') }}
                    @if(!$date->isToday())
                    <span class="text-xs text-slate-400 font-normal">{{ $date->format('Y') }}</span>
                    @endif
                    <i class="bi bi-chevron-down text-xs text-slate-400"></i>
                </button>

                <div x-show="open" x-transition
                     class="absolute right-0 top-full mt-2 z-30 bg-white dark:bg-slate-800
                            border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl p-4 w-72">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Jump to date</p>
                    <input type="date"
                           value="{{ $date->format('Y-m-d') }}"
                           max="{{ today()->format('Y-m-d') }}"
                           @change="window.location.href='/standups?date='+$event.target.value; open=false"
                           class="form-input w-full text-sm" />
                    @if(!$date->isToday())
                    <a href="{{ route('standups.index') }}"
                       class="mt-2 block text-center text-xs text-violet-600 dark:text-violet-400 hover:underline">
                        Back to today
                    </a>
                    @endif
                </div>
            </div>

            <a href="{{ route('standups.show', today()->toDateString()) }}" class="btn-primary">
                <i class="bi bi-pencil-square text-sm"></i> My Standup
            </a>
        </div>
    </div>

    {{-- Date banner (when not today) --}}
    @if(!$date->isToday())
    <div class="mb-4 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800
                text-amber-700 dark:text-amber-300 text-sm flex items-center gap-2">
        <i class="bi bi-clock-history flex-shrink-0"></i>
        Viewing standups for <strong>{{ $date->format('l, F j, Y') }}</strong> — past standups are read-only
    </div>
    @endif

    {{-- Stats bar --}}
    @php
        $submittedCount = $standups->count();
        $totalCount     = $users->count();
        $pendingCount   = $totalCount - $submittedCount;
    @endphp
    <div class="flex items-center gap-4 mb-4 text-sm">
        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $totalCount }} members</span>
        <span class="flex items-center gap-1.5 text-green-600 dark:text-green-400 font-medium">
            <i class="bi bi-check-circle-fill text-xs"></i>
            {{ $submittedCount }} submitted
        </span>
        @if($pendingCount > 0 && $date->isToday())
        <span class="flex items-center gap-1.5 text-slate-400 font-medium">
            <i class="bi bi-clock text-xs"></i>
            {{ $pendingCount }} pending
        </span>
        @endif
    </div>

    {{-- Member list --}}
    <div class="card divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden">
        @forelse($users as $member)
        @php
            $standup     = $standups[$member->id] ?? null;
            $doneCount   = $standup ? $standup->items->where('status','done')->count() : 0;
            $wip         = $standup ? $standup->items->whereIn('status',['in_progress','in_review'])->count() : 0;
            $todoCount   = $standup ? $standup->items->where('status','todo')->count() : 0;
            $totalItems  = $standup ? $standup->items->count() : 0;
            $hasBlockers = $standup && $standup->blockers;
        @endphp

        @if($standup)
        <a href="{{ route('standups.member-day', [$date->toDateString(), $member->id]) }}"
           class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
        @else
        <div class="flex items-center gap-4 px-5 py-4 opacity-60">
        @endif

            {{-- Avatar --}}
            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-sm
                        {{ $standup ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400' : 'bg-slate-100 dark:bg-slate-700 text-slate-400' }}">
                {{ strtoupper(substr($member->name, 0, 2)) }}
            </div>

            {{-- Name & role --}}
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $member->name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ $member->job_title ?? $member->department ?? $member->email }}</p>
            </div>

            {{-- Status --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                @if($standup)
                    {{-- Status pills --}}
                    <div class="flex items-center gap-1.5">
                        @if($doneCount)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ $doneCount }}
                        </span>
                        @endif
                        @if($wip)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>{{ $wip }}
                        </span>
                        @endif
                        @if($todoCount)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>{{ $todoCount }}
                        </span>
                        @endif
                    </div>

                    @if($hasBlockers)
                    <span class="text-xs text-rose-500 flex items-center gap-1">
                        <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blocked
                    </span>
                    @endif

                    <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 group-hover:text-violet-500 transition-colors text-sm ml-1"></i>
                @else
                    <span class="text-xs text-slate-400 italic">Not submitted</span>
                @endif
            </div>

        @if($standup)
        </a>
        @else
        </div>
        @endif
        @empty
        <div class="px-5 py-10 text-center text-slate-400 text-sm">No team members found.</div>
        @endforelse
    </div>

</div>
@endsection
