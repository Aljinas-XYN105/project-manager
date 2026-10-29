@extends('layouts.app')
@section('title', 'My Tasks')

@section('content')
@php
    $groupStyle = [
        'Overdue'     => ['bi-exclamation-circle-fill', 'text-rose-500'],
        'Today'       => ['bi-sun-fill', 'text-amber-500'],
        'This week'   => ['bi-calendar-week', 'text-violet-500'],
        'Later'       => ['bi-calendar3', 'text-blue-500'],
        'No due date' => ['bi-dash-circle', 'text-slate-400'],
        'Completed'   => ['bi-check-circle-fill', 'text-emerald-500'],
    ];
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">{{ ($isManager && $scope === 'all') ? 'All Tasks' : 'My Tasks' }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            {{ ($isManager && $scope === 'all') ? 'All tasks across every project.' : 'Everything assigned to you, across every project.' }}
        </p>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        @if($isManager)
        <div class="tab-bar">
            <a href="{{ route('my-tasks', ['scope' => 'mine', 'view' => $view]) }}" class="tab-item {{ $scope === 'mine' ? 'active' : '' }}">My Tasks <span class="text-slate-400">{{ $counts['open'] }}</span></a>
            <a href="{{ route('my-tasks', ['scope' => 'all', 'view' => $view]) }}" class="tab-item {{ $scope === 'all' ? 'active' : '' }}">All Tasks <span class="text-slate-400">{{ $counts['all_open'] }}</span></a>
        </div>
        @endif
        <div class="tab-bar">
            <a href="{{ route('my-tasks', ['view' => 'open', 'scope' => $scope] + request()->except('view')) }}" class="tab-item {{ $view === 'open' ? 'active' : '' }}">Open <span class="text-slate-400">{{ $isManager && $scope === 'all' ? $counts['all_open'] : $counts['open'] }}</span></a>
            <a href="{{ route('my-tasks', ['view' => 'done', 'scope' => $scope] + request()->except('view')) }}" class="tab-item {{ $view === 'done' ? 'active' : '' }}">Done <span class="text-slate-400">{{ $isManager && $scope === 'all' ? $counts['all_done'] : $counts['done'] }}</span></a>
        </div>
    </div>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-5">
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="hidden" name="scope" value="{{ $scope }}">
    <select name="project_id" onchange="this.form.submit()" class="form-select text-sm w-auto">
        <option value="">All projects</option>
        @foreach($projects as $p)
        <option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->name }}</option>
        @endforeach
    </select>
    <select name="priority" onchange="this.form.submit()" class="form-select text-sm w-auto">
        <option value="">Any priority</option>
        @foreach(['critical','high','medium','low'] as $pr)
        <option value="{{ $pr }}" @selected(request('priority') === $pr)>{{ ucfirst($pr) }}</option>
        @endforeach
    </select>
    @if($isManager)
    <select name="type" onchange="this.form.submit()" class="form-select text-sm w-auto">
        <option value="">Any type</option>
        @foreach(['bug' => 'Bug', 'feature' => 'Feature', 'task' => 'Task', 'improvement' => 'Improvement', 'chore' => 'Chore'] as $tv => $tl)
        <option value="{{ $tv }}" @selected(request('type') === $tv)>{{ $tl }}</option>
        @endforeach
    </select>
    @endif
    @if(request()->hasAny(['project_id','priority','type']))
    <a href="{{ route('my-tasks', ['view' => $view, 'scope' => $scope]) }}" class="btn-ghost btn-sm">Clear</a>
    @endif
</form>

@php $total = collect($groups)->sum(fn ($g) => $g->count()); @endphp
@if($total === 0)
<div class="card empty-state py-16">
    <i class="bi bi-check2-all text-4xl text-emerald-400"></i>
    <p class="text-sm text-slate-500 mt-3">{{ $view === 'done' ? 'No completed tasks yet.' : 'Nothing on your plate. Nice!' }}</p>
</div>
@endif

<div class="space-y-5">
    @foreach($groups as $label => $tasks)
    @continue($tasks->isEmpty())
    <div class="card overflow-hidden" x-data="{ open: true }">
        <button type="button" @click="open = !open" class="w-full flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 text-left">
            <i class="bi {{ $groupStyle[$label][0] }} {{ $groupStyle[$label][1] }}"></i>
            <span class="font-semibold text-slate-900 dark:text-white">{{ $label }}</span>
            <span class="text-xs text-slate-400">{{ $tasks->count() }}</span>
            <i class="bi bi-chevron-down text-xs text-slate-400 ml-auto transition-transform" :class="open || '-rotate-90'"></i>
        </button>
        <div x-show="open" x-collapse>
            @foreach($tasks as $task)
            <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40"
                 x-data="{ status: '{{ $task->status }}' }">
                <button type="button" title="{{ $task->status === 'done' ? 'Reopen' : 'Mark done' }}"
                        @click="const next = status === 'done' ? 'todo' : 'done';
                                axios.patch('/tasks/{{ $task->id }}/status', { status: next }).then(() => { status = next; window.toast(next === 'done' ? 'Task completed' : 'Task reopened'); })"
                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                        :class="status === 'done' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600 hover:border-emerald-400'">
                    <i class="bi bi-check text-xs" x-show="status === 'done'"></i>
                </button>
                <i class="bi {{ $task->type_icon }} {{ $task->type_color }} text-sm"></i>
                <a href="{{ route('projects.tasks.show', $task) }}" class="flex-1 min-w-0">
                    <p class="text-sm text-slate-900 dark:text-white truncate" :class="status === 'done' && 'line-through text-slate-400'">{{ $task->title }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ $task->ticket_key }} · {{ $task->project->name }}</p>
                </a>
                @if($isManager && $scope === 'all')
                <div class="hidden sm:flex items-center -space-x-1.5 flex-shrink-0">
                    @foreach($task->assignees->take(3) as $a)
                    <img src="{{ $a->avatar_url }}" alt="{{ $a->name }}" title="{{ $a->name }}"
                         class="w-5 h-5 rounded-full border border-white dark:border-slate-900 object-cover">
                    @endforeach
                    @if($task->assignees->count() > 3)
                    <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 border border-white dark:border-slate-900 flex items-center justify-center text-[9px] text-slate-500">+{{ $task->assignees->count() - 3 }}</span>
                    @endif
                </div>
                @endif
                <span class="badge-{{ $task->priority }} text-[10px] hidden sm:inline-flex">{{ ucfirst($task->priority) }}</span>
                @if($task->due_date)
                <span class="text-xs w-20 text-right flex-shrink-0 {{ $task->is_overdue ? 'text-rose-500 font-medium' : 'text-slate-500' }}">
                    {{ $task->due_date->isToday() ? 'Today' : ($task->due_date->isTomorrow() ? 'Tomorrow' : $task->due_date->format('M j')) }}
                </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
