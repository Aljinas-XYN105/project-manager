@extends('layouts.app')
@section('title', $project->name.' — Backlog')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-slate-600">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i><span>Backlog</span>
        </div>
        <h1 class="page-title">Backlog</h1>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('projects.tasks.kanban', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-kanban"></i> Board</a>
        <a href="{{ route('projects.tasks.index', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-list-task"></i> List</a>
        <a href="{{ route('projects.tasks.calendar', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-calendar3"></i> Calendar</a>
        <a href="{{ route('projects.tasks.create', $project) }}" class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Task</a>
    </div>
</div>

@php
    $grouped = $tasks->groupBy('priority');
    $priorityOrder = ['critical', 'high', 'medium', 'low'];
    $priorityLabels = ['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
@endphp

<div x-data="{
    selected: [],
    columns: @js($columns->pluck('name','id')),
    toggleAll(checked) {
        const ids = {{ $tasks->pluck('id')->toJson() }};
        this.selected = checked ? ids : [];
    },
    toggle(id, checked) {
        if (checked) this.selected.push(id);
        else this.selected = this.selected.filter(i => i !== id);
    },
    async moveToColumn(columnId) {
        if (!this.selected.length || !columnId) return;
        await axios.post('/tasks/bulk-update', { task_ids: this.selected, action: 'status', value: 'in_progress' });
        // Also move to column via individual moves
        for (const id of this.selected) {
            await axios.patch('/tasks/'+id+'/move', { column_id: parseInt(columnId), position: 999 });
        }
        window.toast('Tasks moved to '+this.columns[columnId], 'success');
        window.location.reload();
    }
}" class="space-y-4">

{{-- Bulk Action Bar --}}
<div x-show="selected.length > 0" x-cloak x-transition
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 dark:bg-slate-700 text-white rounded-2xl shadow-2xl px-5 py-3 flex items-center gap-4">
    <span class="text-sm font-medium" x-text="selected.length + ' selected'"></span>
    <div class="h-4 w-px bg-slate-600"></div>
    <div class="flex items-center gap-2">
        <select @change="if($event.target.value) { moveToColumn($event.target.value); $event.target.value=''; }"
                class="bg-slate-800 dark:bg-slate-600 text-white text-xs rounded-lg px-2.5 py-1.5 border border-slate-600 focus:ring-1 focus:ring-violet-500">
            <option value="">Move to column...</option>
            @foreach($columns as $col)
            <option value="{{ $col->id }}">{{ $col->name }}</option>
            @endforeach
        </select>
    </div>
    <button @click="selected = []" class="ml-2 text-slate-400 hover:text-white"><i class="bi bi-x-lg text-sm"></i></button>
</div>

@if($tasks->count())
    @foreach($priorityOrder as $priority)
    @if(isset($grouped[$priority]) && $grouped[$priority]->count())
    <div class="card overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <span class="badge-{{ $priority }}">{{ $priorityLabels[$priority] }}</span>
            <span class="text-sm text-slate-500">{{ $grouped[$priority]->count() }} tasks</span>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700/50">
            @foreach($grouped[$priority] as $task)
            <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
                <input type="checkbox" class="rounded border-slate-300 flex-shrink-0"
                       :checked="selected.includes({{ $task->id }})"
                       @change="toggle({{ $task->id }}, $event.target.checked)">
                <a href="{{ route('projects.tasks.show', $task) }}"
                   class="flex-1 font-medium text-sm text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 truncate">
                    {{ $task->title }}
                </a>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="badge-{{ $task->status }} text-[10px]">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    @if($task->story_points)
                    <span class="text-[10px] text-violet-500 font-semibold bg-violet-50 dark:bg-violet-900/20 px-1.5 py-0.5 rounded-full">{{ $task->story_points }}pts</span>
                    @endif
                    @if($task->due_date)
                    <span class="text-[10px] {{ $task->is_overdue ? 'text-red-500 font-medium' : 'text-slate-400' }}">
                        <i class="bi bi-calendar3 mr-0.5"></i>{{ $task->due_date->format('M j') }}
                    </span>
                    @endif
                    <div class="flex items-center">
                        @foreach($task->assignees->take(3) as $a)
                        <img src="{{ $a->avatar_url }}" title="{{ $a->name }}" class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 -ml-1 first:ml-0">
                        @endforeach
                    </div>
                    <a href="{{ route('tasks.edit', $task) }}" class="opacity-0 group-hover:opacity-100 btn-ghost btn-sm py-1 px-1.5 transition-opacity">
                        <i class="bi bi-pencil text-xs"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach
@else
<div class="card p-16 text-center">
    <i class="bi bi-inbox text-4xl text-slate-300 dark:text-slate-600"></i>
    <p class="text-sm text-slate-500 mt-3 mb-4">Backlog is empty — all tasks are in progress or done!</p>
    <a href="{{ route('projects.tasks.create', $project) }}" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Add Task
    </a>
</div>
@endif
</div>
@endsection
