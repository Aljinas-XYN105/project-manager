@extends('layouts.app')
@section('title', 'Standups — ' . $date->format('D, M j'))

@section('content')

{{-- Avatar color palette (inline styles, Tailwind-JIT safe) --}}
@php
$avatarBgs   = ['#ede9fe','#dbeafe','#d1fae5','#fef3c7','#fee2e2','#e0e7ff','#ccfbf1','#fce7f3','#ddd6fe','#bfdbfe'];
$avatarTexts = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#be185d','#6d28d9','#1d4ed8'];
@endphp

<div class="relative">

        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                        <i class="bi bi-mic text-violet-600 dark:text-violet-400"></i>
                    </span>
                    Standups
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">Click a member to review their tasks during standup.</p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Date chip --}}
                <a href="{{ route('standups.calendar') }}?return_date={{ $date->toDateString() }}"
                   class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700
                          bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200
                          hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
                    <i class="bi bi-calendar3 text-violet-500 text-sm"></i>
                    @if($date->isToday())
                        Today, {{ $date->format('F j') }}
                    @else
                        {{ $date->format('l, F j') }}
                    @endif
                </a>

                @if(!$date->isToday())
                <a href="{{ route('standups.index') }}" class="btn-ghost btn-sm text-violet-600 dark:text-violet-400">
                    Today
                </a>
                @endif

                <a href="{{ route('standups.show', today()->toDateString()) }}" class="btn-primary">
                    <i class="bi bi-pencil-square text-sm"></i> My Standup
                </a>
            </div>
        </div>

        {{-- Past-date notice --}}
        @if(!$date->isToday())
        <div class="mb-5 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800
                    text-amber-700 dark:text-amber-300 text-sm flex items-center gap-2">
            <i class="bi bi-clock-history flex-shrink-0"></i>
            Viewing <strong>{{ $date->format('l, F j, Y') }}</strong> — past standups are read-only
        </div>
        @endif

        {{-- Stats row --}}
        @php
            $submittedCount = $standups->count();
            $totalCount     = $users->count();
            $pendingCount   = $totalCount - $submittedCount;
        @endphp
        <div class="flex items-center gap-5 mb-6 text-sm">
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

        {{-- Card grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($users as $member)
            @php
                $standup       = $standups[$member->id] ?? null;
                $todoCount     = $standup ? $standup->items->where('status','todo')->count() : 0;
                $inProgCount   = $standup ? $standup->items->where('status','in_progress')->count() : 0;
                $reviewCount   = $standup ? $standup->items->where('status','in_review')->count() : 0;
                $doneCount     = $standup ? $standup->items->where('status','done')->count() : 0;
                $hasBlockers   = $standup && $standup->blockers;
                $cardShadow    = $hasBlockers
                    ? '0 3px 12px 0 rgba(239,68,68,0.28), 0 1px 4px 0 rgba(239,68,68,0.12)'
                    : ($standup ? '0 3px 12px 0 rgba(34,197,94,0.22), 0 1px 4px 0 rgba(34,197,94,0.08)' : '0 3px 12px 0 rgba(251,146,60,0.18), 0 1px 4px 0 rgba(251,146,60,0.06)');
                $cardBorder    = $hasBlockers
                    ? 'border-rose-200 dark:border-rose-800/50'
                    : ($standup ? 'border-green-200 dark:border-green-800/40' : 'border-amber-200 dark:border-amber-700/40');
                $dividerColor  = $hasBlockers
                    ? 'border-rose-100 dark:border-rose-900/30'
                    : ($standup ? 'border-green-100 dark:border-green-900/20' : 'border-amber-100 dark:border-amber-900/20');
                $colorIdx      = $member->id % count($avatarBgs);
                $avatarBg      = $avatarBgs[$colorIdx];
                $avatarText    = $avatarTexts[$colorIdx];
                $initials      = strtoupper(substr($member->name, 0, 2));
                $subtitle      = $member->job_title ?? $member->department ?? $member->email;
            @endphp

            <a href="{{ route('standups.member-day', [$date->toDateString(), $member->id]) }}"
               class="group flex flex-col bg-white dark:bg-slate-800 rounded-xl border {{ $cardBorder }}
                      hover:border-violet-300 dark:hover:border-violet-600 overflow-hidden
                      transition-all duration-200 w-full"
               style="box-shadow: {{ $cardShadow }}">

                {{-- Top section: avatar + name + status badge --}}
                <div class="flex items-center gap-3 px-4 pt-4 pb-3.5">
                    {{-- Avatar --}}
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                         style="background: {{ $avatarBg }}; color: {{ $avatarText }}">
                        {{ $initials }}
                    </div>

                    {{-- Name + role + designation --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-slate-900 dark:text-white text-sm leading-tight truncate">{{ $member->name }}</span>
                            @foreach($member->roles as $role)
                            @if($role->name !== 'member')
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide flex-shrink-0
                                         {{ $role->name === 'admin' ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300'
                                          : ($role->name === 'manager' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                          : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300') }}">
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </span>
                            @endif
                            @endforeach
                        </div>
                        @if($subtitle)
                        <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $subtitle }}</p>
                        @endif
                    </div>

                    {{-- Status dot + chevron --}}
                    <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                        @if($standup)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                     bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800/50">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                            Submitted
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                     bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                            Pending
                        </span>
                        @endif
                        <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 group-hover:text-violet-500 transition-colors text-xs"></i>
                    </div>
                </div>

                {{-- Counts bar — always present, full-width, keeps all cards the same height --}}
                <div class="grid grid-cols-4 border-t {{ $dividerColor }}">
                    {{-- To Do --}}
                    <div class="flex flex-col items-center gap-1 py-2.5
                                border-r {{ $dividerColor }}">
                        <i class="bi bi-circle-fill text-[11px] {{ $todoCount > 0 ? 'text-slate-400' : 'text-slate-200 dark:text-slate-700' }}"></i>
                        <span class="text-[11px] font-bold tabular-nums leading-none
                                     {{ $todoCount > 0 ? 'text-slate-600 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600' }}">
                            {{ $todoCount }}
                        </span>
                    </div>
                    {{-- In Progress --}}
                    <div class="flex flex-col items-center gap-1 py-2.5
                                border-r {{ $dividerColor }}">
                        <i class="bi bi-arrow-repeat text-[11px] {{ $inProgCount > 0 ? 'text-blue-500' : 'text-slate-200 dark:text-slate-700' }}"></i>
                        <span class="text-[11px] font-bold tabular-nums leading-none
                                     {{ $inProgCount > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-300 dark:text-slate-600' }}">
                            {{ $inProgCount }}
                        </span>
                    </div>
                    {{-- In Review --}}
                    <div class="flex flex-col items-center gap-1 py-2.5
                                border-r {{ $dividerColor }}">
                        <i class="bi bi-eye-fill text-[11px] {{ $reviewCount > 0 ? 'text-amber-500' : 'text-slate-200 dark:text-slate-700' }}"></i>
                        <span class="text-[11px] font-bold tabular-nums leading-none
                                     {{ $reviewCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-300 dark:text-slate-600' }}">
                            {{ $reviewCount }}
                        </span>
                    </div>
                    {{-- Done --}}
                    <div class="flex flex-col items-center gap-1 py-2.5">
                        <i class="bi bi-check-circle-fill text-[11px] {{ $doneCount > 0 ? 'text-green-500' : 'text-slate-200 dark:text-slate-700' }}"></i>
                        <span class="text-[11px] font-bold tabular-nums leading-none
                                     {{ $doneCount > 0 ? 'text-green-600 dark:text-green-400' : 'text-slate-300 dark:text-slate-600' }}">
                            {{ $doneCount }}
                        </span>
                    </div>
                </div>

            </a>
            @empty
            <div class="col-span-4 text-center py-16 text-slate-400 text-sm">No team members found.</div>
            @endforelse
        </div>

    </div>
</div>
@endsection
