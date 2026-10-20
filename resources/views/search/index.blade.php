@extends('layouts.app')
@section('title', $term ? 'Search: '.$term : 'Search')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="page-header">
        <div>
            <h1 class="page-title">Search</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Projects, tasks (including ticket keys like MOBILE-12) and people.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('search') }}" class="card p-3 mb-6 flex gap-2">
        <div class="relative flex-1">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="search" name="q" value="{{ $term }}" autofocus minlength="2" class="form-input pl-10" placeholder="Search…">
        </div>
        <button class="btn-primary">Search</button>
    </form>

    @if(mb_strlen($term) < 2)
    <div class="empty-state py-16">
        <i class="bi bi-search text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-sm text-slate-500 mt-3">Type at least 2 characters.</p>
    </div>
    @elseif($projects->isEmpty() && $tasks->isEmpty() && $people->isEmpty())
    <div class="empty-state py-16">
        <i class="bi bi-emoji-neutral text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-sm text-slate-500 mt-3">Nothing matches “{{ $term }}”.</p>
    </div>
    @else
    <div class="space-y-6">
        @if($tasks->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 font-semibold text-slate-900 dark:text-white">Tasks <span class="text-slate-400 font-normal">({{ $tasks->count() }})</span></div>
            @foreach($tasks as $task)
            <a href="{{ route('projects.tasks.show', $task) }}" class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <i class="bi {{ $task->type_icon }} {{ $task->type_color }}"></i>
                <span class="font-mono text-xs text-slate-400 w-20 flex-shrink-0">{{ $task->ticket_key }}</span>
                <span class="flex-1 min-w-0 truncate text-sm {{ in_array($task->status, ['done','cancelled']) ? 'line-through text-slate-400' : 'text-slate-900 dark:text-white' }}">{{ $task->title }}</span>
                <span class="text-xs text-slate-400 hidden sm:inline">{{ $task->project->name }}</span>
                <span class="badge-{{ $task->status }} text-[10px]">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
            </a>
            @endforeach
        </div>
        @endif

        @if($projects->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 font-semibold text-slate-900 dark:text-white">Projects <span class="text-slate-400 font-normal">({{ $projects->count() }})</span></div>
            @foreach($projects as $project)
            <a href="{{ route('projects.show', $project) }}" class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $project->color }}"></span>
                <span class="flex-1 text-sm font-medium text-slate-900 dark:text-white">{{ $project->name }}</span>
                <span class="badge-{{ $project->status }} text-[10px]">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
            </a>
            @endforeach
        </div>
        @endif

        @if($people->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 font-semibold text-slate-900 dark:text-white">People <span class="text-slate-400 font-normal">({{ $people->count() }})</span></div>
            @foreach($people as $person)
            <a href="{{ route('users.show', $person) }}" class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <img src="{{ $person->avatar_url }}" class="w-8 h-8 rounded-full" alt="">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $person->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ collect([$person->job_title, $person->department, $person->email])->filter()->implode(' · ') }}</p>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
