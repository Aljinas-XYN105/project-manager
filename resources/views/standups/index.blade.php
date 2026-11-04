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
                $standup      = $standups[$member->id] ?? null;
                $todoCount    = $standup ? $standup->items->where('status','todo')->count() : 0;
                $wipCount     = $standup ? $standup->items->whereIn('status',['in_progress','in_review'])->count() : 0;
                $doneCount    = $standup ? $standup->items->where('status','done')->count() : 0;
                $totalItems   = $standup ? $standup->items->count() : 0;
                $progressPct  = $totalItems > 0 ? (int)(($doneCount / $totalItems) * 100) : 0;
                $hasBlockers  = $standup && $standup->blockers;
                $colorIdx     = $member->id % count($avatarBgs);
                $avatarBg     = $avatarBgs[$colorIdx];
                $avatarText   = $avatarTexts[$colorIdx];
                $initials     = strtoupper(substr($member->name, 0, 2));
                $subtitle     = $member->job_title ?? $member->department ?? $member->email;
            @endphp

            <a href="{{ route('standups.member-day', [$date->toDateString(), $member->id]) }}"
               class="group text-left bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700
                      shadow-sm hover:shadow-md hover:border-violet-300 dark:hover:border-violet-600
                      transition-all duration-200 p-5 flex flex-col gap-3 w-full">

                {{-- Top row: avatar + chevron --}}
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold text-base flex-shrink-0"
                         style="background: {{ $avatarBg }}; color: {{ $avatarText }}">
                        {{ $initials }}
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        @if($standup)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                         bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                <i class="bi bi-check-circle-fill text-[10px]"></i> Submitted
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                         bg-slate-100 dark:bg-slate-700 text-slate-400">
                                <i class="bi bi-clock text-[10px]"></i> Pending
                            </span>
                        @endif
                        @if($hasBlockers)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                     bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                            <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blocked
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Name + role badges --}}
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $member->name }}</span>
                        @foreach($member->roles as $role)
                        @if($role->name !== 'member')
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide
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

                {{-- Progress bar + status dots --}}
                <div class="pt-1 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                    @if($standup && $totalItems > 0)
                        {{-- Thin segmented progress bar --}}
                        <div class="flex gap-0.5 h-1.5 rounded-full overflow-hidden">
                            @if($doneCount)
                            <div class="bg-green-400 dark:bg-green-500 rounded-full"
                                 style="flex: {{ $doneCount }}"></div>
                            @endif
                            @if($wipCount)
                            <div class="bg-blue-400 dark:bg-blue-500 rounded-full"
                                 style="flex: {{ $wipCount }}"></div>
                            @endif
                            @if($todoCount)
                            <div class="bg-slate-200 dark:bg-slate-600 rounded-full"
                                 style="flex: {{ $todoCount }}"></div>
                            @endif
                        </div>
                        {{-- Status dot counts --}}
                        <div class="flex items-center gap-3">
                            @if($todoCount)
                            <span class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                <span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-500 flex-shrink-0"></span>
                                {{ $todoCount }}
                            </span>
                            @endif
                            @if($wipCount)
                            <span class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400">
                                <span class="w-2 h-2 rounded-full bg-blue-400 dark:bg-blue-500 flex-shrink-0"></span>
                                {{ $wipCount }}
                            </span>
                            @endif
                            @if($doneCount)
                            <span class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <span class="w-2 h-2 rounded-full bg-green-400 dark:bg-green-500 flex-shrink-0"></span>
                                {{ $doneCount }}
                            </span>
                            @endif
                            <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 group-hover:text-violet-500 transition-colors text-sm ml-auto"></i>
                        </div>
                    @else
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-300 dark:text-slate-600 italic">{{ $standup ? 'No items' : 'Not submitted' }}</span>
                            <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 group-hover:text-violet-500 transition-colors text-sm"></i>
                        </div>
                    @endif
                </div>

            </a>
            @empty
            <div class="col-span-4 text-center py-16 text-slate-400 text-sm">No team members found.</div>
            @endforelse
        </div>

    </div>
</div>
@endsection
