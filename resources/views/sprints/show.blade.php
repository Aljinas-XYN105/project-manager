@extends('layouts.app')
@section('title', $sprint->name)

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-violet-600 transition-colors">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ route('projects.sprints.index', $project) }}" class="hover:text-violet-600 transition-colors">Sprints</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>{{ $sprint->name }}</span>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="page-title">{{ $sprint->name }}</h1>
            @php
            $statusColors = [
                'planning'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
                'active'    => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                'completed' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
            ];
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$sprint->status] ?? '' }}">
                {{ ucfirst($sprint->status) }}
            </span>
        </div>
        @if($sprint->goal)
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">{{ $sprint->goal }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg flex items-center gap-1.5">
            <i class="bi bi-calendar3 text-slate-400"></i>
            {{ $sprint->start_date->format('M d') }} – {{ $sprint->end_date->format('M d, Y') }}
        </span>
        @can('update', $project)
        @if($sprint->status === 'planning')
        <form method="POST" action="{{ route('projects.sprints.start', [$project, $sprint]) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn-primary btn-sm"
                    onclick="return confirm('Start this sprint?')">
                <i class="bi bi-play-fill"></i> Start Sprint
            </button>
        </form>
        @elseif($sprint->status === 'active')
        <form method="POST" action="{{ route('projects.sprints.complete', [$project, $sprint]) }}">
            @csrf @method('PATCH')
            <button type="submit"
                    class="btn-sm px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors"
                    onclick="return confirm('Complete sprint? Unfinished tasks return to backlog.')">
                <i class="bi bi-check2-all"></i> Complete Sprint
            </button>
        </form>
        @endif
        @endcan
    </div>
</div>

@php
    $totalTasks = $sprint->tasks()->count();
    $doneTasks  = $sprint->tasks()->where('status','done')->count();
    $inProg     = $sprint->tasks()->where('status','in_progress')->count();
    $bugs       = $sprint->tasks()->where('type','bug')->count();
    $progress   = $totalTasks > 0 ? round(($doneTasks/$totalTasks)*100) : 0;
@endphp

{{-- KPI Strip --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $totalTasks }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Total Tasks</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ $inProg }}</p>
        <p class="text-xs text-slate-400 mt-0.5">In Progress</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $doneTasks }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Completed</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-2xl font-bold {{ $bugs > 0 ? 'text-rose-500 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">{{ $bugs }}</p>
        <p class="text-xs text-slate-400 mt-0.5">Bugs</p>
    </div>
</div>

{{-- Progress bar --}}
@if($totalTasks > 0)
<div class="card p-4 mb-5">
    <div class="flex items-center justify-between text-sm mb-2">
        <span class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2">
            <i class="bi bi-speedometer2 text-violet-500"></i> Sprint Progress
        </span>
        <span class="font-bold text-slate-900 dark:text-white">{{ $progress }}%</span>
    </div>
    <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="h-2.5 rounded-full transition-all duration-700"
             style="width: {{ $progress }}%; background: linear-gradient(90deg, #7c3aed, #a855f7)"></div>
    </div>
    <div class="flex justify-between text-xs text-slate-400 mt-1.5">
        <span>{{ $doneTasks }} done</span>
        <span>{{ $totalTasks - $doneTasks }} remaining</span>
    </div>
</div>
@endif

{{-- Sprint Board --}}
<div class="flex gap-3 overflow-x-auto pb-6 -mx-4 lg:-mx-6 px-4 lg:px-6 min-h-[calc(100vh-28rem)] items-start">
    @foreach($columns as $column)
    <div class="flex flex-col flex-shrink-0 w-[270px] rounded-2xl
                bg-slate-100/70 dark:bg-slate-800/50 backdrop-blur-sm
                border border-slate-200/60 dark:border-slate-700/50">
        <div class="flex items-center gap-2 px-3 py-3 flex-shrink-0">
            <div class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $column->color }}"></div>
            <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ $column->name }}</span>
            <span class="min-w-[20px] h-5 bg-white dark:bg-slate-700 rounded-full text-xs font-semibold
                         text-slate-500 dark:text-slate-400 flex items-center justify-center px-1.5 shadow-sm ml-auto">
                {{ $column->tasks->count() }}
            </span>
        </div>
        <div class="flex-1 overflow-y-auto px-2.5 pb-2.5 space-y-2">
            @forelse($column->tasks as $task)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700/80
                        shadow-sm p-3 hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700/60
                        hover:-translate-y-px transition-all duration-150 group">
                <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                    <i class="bi {{ $task->type_icon }} text-xs {{ $task->type_color }}"></i>
                    @if($task->ticket_key)
                    <span class="font-mono text-[9px] text-slate-400">{{ $task->ticket_key }}</span>
                    @endif
                    @foreach($task->labels as $label)
                    <span class="px-1.5 py-px rounded-full text-[9px] font-semibold text-white leading-none"
                          style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                </div>
                <a href="{{ route('projects.tasks.show', $task) }}"
                   class="block font-medium text-sm text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 leading-snug mb-2.5">
                    {{ $task->title }}
                </a>
                <div class="flex items-center justify-between">
                    <span class="badge-{{ $task->priority }} py-0 text-[9px]">{{ ucfirst($task->priority) }}</span>
                    <div class="flex -space-x-1">
                        @foreach($task->assignees->take(3) as $a)
                        <img src="{{ $a->avatar_url }}" title="{{ $a->name }}"
                             class="w-5 h-5 rounded-full border-2 border-white dark:border-slate-900">
                        @endforeach
                    </div>
                </div>
                @can('update', $project)
                @if($sprint->status === 'active')
                <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 opacity-0 group-hover:opacity-100 transition-opacity">
                    <form method="POST" action="{{ route('projects.sprints.remove-task', [$project, $sprint, $task]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-slate-400 hover:text-rose-500 transition-colors">
                            <i class="bi bi-x-circle mr-0.5"></i> Remove from sprint
                        </button>
                    </form>
                </div>
                @endif
                @endcan
            </div>
            @empty
            <div class="py-8 text-center">
                <i class="bi bi-inbox text-2xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                <p class="text-xs text-slate-400">No tasks</p>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- Add from Backlog --}}
@can('update', $project)
@if($backlogTasks->count())
<div class="mt-6" x-data="{ open: false }">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-inbox text-slate-400"></i>
            Backlog
            <span class="text-sm font-normal text-slate-400">({{ $backlogTasks->count() }} tasks)</span>
        </h2>
        <button @click="open = !open" class="btn-ghost btn-sm text-sm">
            <span x-text="open ? 'Hide' : 'Add from Backlog'"></span>
            <i class="bi transition-transform duration-200" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
    </div>
    <div x-show="open" x-transition class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
            <p class="text-sm text-slate-500">Select backlog tasks to add to this sprint.</p>
            <button onclick="addSelectedTasks()" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add Selected
            </button>
        </div>
        <form id="add-to-sprint-form" class="divide-y divide-slate-50 dark:divide-slate-800 max-h-80 overflow-y-auto">
            @foreach($backlogTasks as $task)
            <label class="flex items-center gap-3 px-5 py-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <input type="checkbox" name="task_ids[]" value="{{ $task->id }}"
                       class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <i class="bi {{ $task->type_icon }} text-xs {{ $task->type_color }}"></i>
                        <span class="text-xs text-slate-400 font-mono">{{ $task->ticket_key }}</span>
                        <span class="font-medium text-sm text-slate-800 dark:text-slate-200 truncate">{{ $task->title }}</span>
                    </div>
                </div>
                <span class="badge-{{ $task->priority }} text-[10px] flex-shrink-0 py-0">{{ ucfirst($task->priority) }}</span>
            </label>
            @endforeach
        </form>
    </div>
</div>
@endif
@endcan

@push('scripts')
<script>
function addSelectedTasks() {
    const form = document.getElementById('add-to-sprint-form');
    const checked = [...form.querySelectorAll('input:checked')].map(el => el.value);
    if (!checked.length) return alert('Select at least one task.');
    fetch('{{ route("projects.sprints.add-task", [$project, $sprint]) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ task_ids: checked }),
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
@endpush
@endsection
