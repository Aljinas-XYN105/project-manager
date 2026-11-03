@extends('layouts.app')
@section('title', 'Standups')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Standups</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Click a member to review their tasks during standup.</p>
    </div>
    <div class="text-sm text-slate-400 flex items-center gap-2">
        <i class="bi bi-calendar3"></i>
        {{ now()->format('l, F j') }}
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($members as $member)
    <a href="{{ route('standups.show', $member['id']) }}"
       class="card p-4 flex items-start gap-4 hover:border-violet-300 dark:hover:border-violet-700
              hover:shadow-md hover:-translate-y-px transition-all duration-150 group cursor-pointer">

        <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}"
             class="w-12 h-12 rounded-full object-cover flex-shrink-0 ring-2 ring-white dark:ring-slate-800">

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $member['name'] }}</p>
                @if($member['role'] !== 'member')
                <span class="text-[10px] px-1.5 py-px rounded-full font-semibold
                             {{ $member['role'] === 'admin' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400' }}">
                    {{ ucfirst($member['role']) }}
                </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 truncate mt-0.5">{{ $member['job_title'] ?? $member['department'] ?? '—' }}</p>

            <div class="flex items-center gap-3 mt-3">
                <div class="text-center">
                    <p class="text-lg font-bold text-slate-800 dark:text-white leading-none">{{ $member['open'] }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Open</p>
                </div>
                <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>
                <div class="text-center">
                    <p class="text-lg font-bold {{ $member['in_progress'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-800 dark:text-white' }} leading-none">
                        {{ $member['in_progress'] }}
                    </p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Active</p>
                </div>
            </div>
        </div>

        <i class="bi bi-chevron-right text-slate-300 group-hover:text-violet-500 transition-colors mt-1 flex-shrink-0"></i>
    </a>
    @endforeach
</div>
@endsection
