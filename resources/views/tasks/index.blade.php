@extends('layouts.app')
@section('title', $project->name.' — Tasks')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-violet-600 transition-colors">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>Tasks</span>
        </div>
        <h1 class="page-title">Task List</h1>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <div class="tab-bar">
            <a href="{{ route('projects.tasks.index', $project) }}"
               class="tab-item active text-xs px-2.5 py-1.5">
                <i class="bi bi-list-task"></i> <span class="hidden sm:inline">List</span>
            </a>
            <a href="{{ route('projects.tasks.kanban', $project) }}"
               class="tab-item text-xs px-2.5 py-1.5">
                <i class="bi bi-kanban"></i> <span class="hidden sm:inline">Board</span>
            </a>
            <a href="{{ route('projects.tasks.calendar', $project) }}"
               class="tab-item text-xs px-2.5 py-1.5">
                <i class="bi bi-calendar3"></i> <span class="hidden sm:inline">Calendar</span>
            </a>
            <a href="{{ route('projects.tasks.backlog', $project) }}"
               class="tab-item text-xs px-2.5 py-1.5">
                <i class="bi bi-inbox"></i> <span class="hidden sm:inline">Backlog</span>
            </a>
        </div>
        <a href="{{ route('projects.tasks.create', $project) }}" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Task
        </a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-2 mb-5">
    <div class="relative flex-1 min-w-[160px] max-w-xs">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tasks…"
               class="form-input pl-9 text-sm">
    </div>
    <select name="status" class="form-select w-auto text-sm">
        <option value="">All Status</option>
        @foreach(['todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Done','cancelled'=>'Cancelled'] as $v=>$l)
        <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
        @endforeach
    </select>
    <select name="priority" class="form-select w-auto text-sm">
        <option value="">All Priority</option>
        @foreach(['low','medium','high','critical'] as $p)
        <option value="{{ $p }}" {{ request('priority')===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
        @endforeach
    </select>
    <select name="assignee" class="form-select w-auto text-sm">
        <option value="">All Assignees</option>
        @foreach($members as $m)
        <option value="{{ $m->id }}" {{ request('assignee')==$m->id?'selected':'' }}>{{ $m->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn-primary btn-sm">Filter</button>
    @if(request()->anyFilled(['search','status','priority','assignee']))
    <a href="{{ route('projects.tasks.index', $project) }}" class="btn-ghost btn-sm">Clear</a>
    @endif
</form>

<div x-data="{
    selected: [],
    toggleAll(checked) { this.selected = checked ? {{ $tasks->pluck('id')->toJson() }} : []; },
    toggle(id, checked) { if(checked) this.selected.push(id); else this.selected = this.selected.filter(i=>i!==id); },
    async bulkAction(action, value=null) {
        if (!this.selected.length) return;
        await axios.post('/tasks/bulk-update', { task_ids: this.selected, action, value });
        window.toast?.(`Updated ${this.selected.length} tasks`, 'success');
        window.location.reload();
    }
}" class="space-y-4">

{{-- Bulk action floating bar --}}
<div x-show="selected.length > 0" x-cloak x-transition
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 dark:bg-slate-800 text-white
            rounded-2xl shadow-2xl shadow-slate-900/30 px-5 py-3 flex items-center gap-4 border border-white/10">
    <span class="text-sm font-medium" x-text="selected.length + ' selected'"></span>
    <div class="w-px h-4 bg-white/20"></div>
    <div class="flex items-center gap-2">
        <select @change="if($event.target.value) { bulkAction('status', $event.target.value); $event.target.value=''; }"
                class="bg-white/10 text-white text-xs rounded-lg px-2.5 py-1.5 border border-white/20 focus:ring-1 focus:ring-violet-500 cursor-pointer">
            <option value="">Change status…</option>
            <option value="todo">To Do</option>
            <option value="in_progress">In Progress</option>
            <option value="in_review">In Review</option>
            <option value="done">Done</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <button @click="bulkAction('delete')"
                class="flex items-center gap-1.5 text-xs text-rose-400 hover:text-rose-300 px-2.5 py-1.5 rounded-lg hover:bg-rose-900/30 transition-colors">
            <i class="bi bi-trash"></i> Delete
        </button>
    </div>
    <button @click="selected = []" class="text-white/40 hover:text-white/80 transition-colors ml-1">
        <i class="bi bi-x-lg text-sm"></i>
    </button>
</div>

@if($tasks->count())
<div class="card overflow-hidden" :class="selected.length > 0 ? 'mb-24' : ''">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
        <span class="text-xs font-semibold text-slate-500">{{ $tasks->total() }} tasks</span>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400">
                <span class="font-semibold text-emerald-600">{{ $tasks->where('status','done')->count() }}</span> done
            </span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-8 pr-0">
                        <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500"
                               @change="toggleAll($event.target.checked)">
                    </th>
                    <th>Task</th>
                    <th class="hidden sm:table-cell">Status</th>
                    <th class="hidden md:table-cell">Priority</th>
                    <th class="hidden lg:table-cell">Assignees</th>
                    <th class="hidden md:table-cell text-right">Due</th>
                    <th class="w-10"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                @php
                $statusStyles = [
                    'todo'        => 'text-slate-500',
                    'in_progress' => 'text-blue-600 dark:text-blue-400',
                    'in_review'   => 'text-amber-600 dark:text-amber-400',
                    'done'        => 'text-emerald-600 dark:text-emerald-400',
                    'cancelled'   => 'text-slate-400',
                ];
                @endphp
                <tr class="group {{ $task->status === 'done' ? 'opacity-70' : '' }}">
                    <td class="pr-0">
                        <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500"
                               :checked="selected.includes({{ $task->id }})"
                               @change="toggle({{ $task->id }}, $event.target.checked)">
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            @if($task->type)
                            <i class="bi {{ $task->type_icon }} text-sm {{ $task->type_color }} flex-shrink-0"></i>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('projects.tasks.show', $task) }}"
                                   class="font-medium text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors
                                          {{ $task->status === 'done' ? 'line-through text-slate-400 dark:text-slate-500' : '' }} truncate block max-w-xs lg:max-w-sm">
                                    {{ $task->title }}
                                </a>
                                @if($task->ticket_key)
                                <span class="font-mono text-[10px] text-slate-400">{{ $task->ticket_key }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="hidden sm:table-cell">
                        <div x-data="{ status: '{{ $task->status }}' }">
                            <select x-model="status"
                                    @change="axios.patch('/tasks/{{ $task->id }}/status', {status}).then(()=>window.toast?.('Status updated','success'))"
                                    class="text-xs font-medium bg-transparent border-0 focus:ring-0 p-0 pr-5 cursor-pointer
                                           {{ $statusStyles[$task->status] ?? 'text-slate-500' }}">
                                <option value="todo">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="in_review">In Review</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </td>
                    <td class="hidden md:table-cell">
                        <span class="badge-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
                    </td>
                    <td class="hidden lg:table-cell">
                        @if($task->assignees->count())
                        <div class="avatar-stack">
                            @foreach($task->assignees->take(3) as $a)
                            <img src="{{ $a->avatar_url }}" title="{{ $a->name }}"
                                 class="w-7 h-7 rounded-full">
                            @endforeach
                            @if($task->assignees->count() > 3)
                            <div class="avatar-more text-[10px]">+{{ $task->assignees->count()-3 }}</div>
                            @endif
                        </div>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="hidden md:table-cell text-right">
                        @if($task->due_date)
                        <span class="text-xs {{ $task->is_overdue && $task->status !== 'done' ? 'text-rose-500 font-semibold' : 'text-slate-400' }}">
                            {{ $task->due_date->format('M j') }}
                        </span>
                        @else
                        <span class="text-slate-300 dark:text-slate-600">—</span>
                        @endif
                    </td>
                    <td>
                        <div x-data="{open:false}" class="relative">
                            <button @click="open=!open"
                                    class="p-1.5 rounded-lg text-slate-300 dark:text-slate-600
                                           hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800
                                           opacity-0 group-hover:opacity-100 transition-all">
                                <i class="bi bi-three-dots-vertical text-sm"></i>
                            </button>
                            <div x-show="open" x-cloak @click.away="open=false" x-transition
                                 class="dropdown-menu absolute right-0 z-20 w-40">
                                <a href="{{ route('projects.tasks.show', $task) }}" class="dropdown-item">
                                    <i class="bi bi-eye text-slate-400"></i> View
                                </a>
                                <a href="{{ route('tasks.edit', $task) }}" class="dropdown-item">
                                    <i class="bi bi-pencil text-slate-400"></i> Edit
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                                      onsubmit="return confirm('Delete this task?')">
                                    @csrf @method('DELETE')
                                    <button class="dropdown-item w-full text-rose-600 dark:text-rose-400">
                                        <i class="bi bi-trash3"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
        {{ $tasks->withQueryString()->links() }}
    </div>
    @endif
</div>
@else
<div class="empty-state">
    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
        <i class="bi bi-check2-square text-2xl text-slate-400"></i>
    </div>
    <p class="font-semibold text-slate-600 dark:text-slate-400 text-lg">No tasks yet</p>
    <p class="text-sm text-slate-400 mt-1">Create your first task to start tracking work</p>
    <a href="{{ route('projects.tasks.create', $project) }}" class="btn-primary mt-4">
        <i class="bi bi-plus-lg"></i> Create Task
    </a>
</div>
@endif
</div>
@endsection
