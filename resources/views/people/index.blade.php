@extends('layouts.app')
@section('title', 'People')

@section('content')
@php $statuses = \App\Models\User::AVAILABILITY; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">People</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $people->count() }} colleagues{{ request('department') ? ' in '.request('department') : '' }}</p>
    </div>
    <div class="tab-bar">
        <a href="{{ route('people.index') }}" class="tab-item active"><i class="bi bi-grid-3x3-gap"></i> Directory</a>
        <a href="{{ route('people.org-chart') }}" class="tab-item"><i class="bi bi-diagram-3"></i> Org chart</a>
    </div>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-5">
    <div class="relative flex-1 min-w-[12rem] max-w-sm">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
        <input type="search" name="q" value="{{ request('q') }}" class="form-input pl-9 text-sm" placeholder="Name or email…">
    </div>
    <select name="department" onchange="this.form.submit()" class="form-select text-sm w-auto">
        <option value="">All departments</option>
        @foreach($departments as $d)
        <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
        @endforeach
    </select>
    <button class="btn-secondary btn-sm">Search</button>
</form>

@if($people->isEmpty())
<div class="card empty-state py-16">
    <i class="bi bi-people text-4xl text-slate-300 dark:text-slate-600"></i>
    <p class="text-sm text-slate-500 mt-3">No one matches.</p>
</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
    @foreach($people as $person)
    @php
        $onLeave = in_array($person->id, $onLeaveToday);
        $st = $statuses[$person->effective_availability] ?? $statuses['offline'];
    @endphp
    <a href="{{ route('users.show', $person) }}" class="card-hover p-5 flex flex-col">
        <div class="flex items-start gap-3">
            <div class="relative flex-shrink-0">
                <img src="{{ $person->avatar_url }}" class="w-12 h-12 rounded-full" alt="">
                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full ring-2 ring-white dark:ring-slate-900 {{ $st['dot'] }}" title="{{ $st['label'] }}"></span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $person->name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $person->job_title ?: 'No title' }}</p>
                @if($person->department)
                <span class="inline-block mt-1.5 text-[11px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">{{ $person->department }}</span>
                @endif
            </div>
            @if($onLeave)
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium flex-shrink-0">On leave</span>
            @endif
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1 text-xs text-slate-500">
            <p class="truncate"><i class="bi bi-envelope mr-1.5"></i>{{ $person->email }}</p>
            @if($person->phone)<p class="truncate"><i class="bi bi-telephone mr-1.5"></i>{{ $person->phone }}</p>@endif
            @if($person->manager)<p class="truncate"><i class="bi bi-person-up mr-1.5"></i>Reports to {{ $person->manager->name }}</p>@endif
            @if($person->direct_reports_count)<p><i class="bi bi-diagram-2 mr-1.5"></i>{{ $person->direct_reports_count }} direct {{ Str::plural('report', $person->direct_reports_count) }}</p>@endif
        </div>
    </a>
    @endforeach
</div>
@endsection
