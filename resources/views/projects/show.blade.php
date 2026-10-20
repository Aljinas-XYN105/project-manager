@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div class="page-header">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
             style="background-color: {{ $project->color }}25;">
            <div class="w-4 h-4 rounded-full" style="background-color: {{ $project->color }};"></div>
        </div>
        <div>
            <h1 class="page-title">{{ $project->name }}</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="badge-{{ $project->status }}">{{ ucfirst(str_replace('_',' ',$project->status)) }}</span>
                <span class="badge-{{ $project->priority }}">{{ ucfirst($project->priority) }}</span>
                @if($project->is_overdue)
                <span class="badge bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <i class="bi bi-exclamation-triangle-fill mr-1"></i>Overdue
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('projects.notes', $project) }}" class="btn-ghost btn-sm">
            <i class="bi bi-journal-text"></i> Notes
        </a>
        <a href="{{ route('projects.sprints.index', $project) }}" class="btn-ghost btn-sm">
            <i class="bi bi-speedometer2"></i> Sprints
        </a>
        <a href="{{ route('projects.tasks.kanban', $project) }}" class="btn-secondary btn-sm">
            <i class="bi bi-kanban"></i> Board
        </a>
        <a href="{{ route('projects.tasks.create', $project) }}" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Task
        </a>
        @can('update', $project)
        <a href="{{ route('projects.edit', $project) }}" class="btn-ghost btn-sm">
            <i class="bi bi-pencil"></i>
        </a>
        @endcan
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">Total Tasks</span>
        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_tasks'] }}</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">Completed</span>
        <span class="text-2xl font-bold text-green-600">{{ $stats['done'] }}</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">In Progress</span>
        <span class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">Hours Logged</span>
        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_hours'] }}h</span>
    </div>
</div>

{{-- Progress --}}
<div class="card p-5 mb-6">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-semibold text-slate-900 dark:text-white">Overall Progress</span>
        <span class="text-lg font-bold text-violet-600">{{ $stats['progress'] }}%</span>
    </div>
    <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-700"
             style="width: {{ $stats['progress'] }}%"></div>
    </div>
    <div class="flex justify-between text-xs text-slate-400 mt-2">
        <span>{{ $stats['done'] }} of {{ $stats['total_tasks'] }} tasks done</span>
        @if($project->due_date)
        <span class="{{ $project->is_overdue ? 'text-red-500' : '' }}">
            Due {{ $project->due_date->format('M j, Y') }}
        </span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Task Status Breakdown --}}
    <div class="xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white">Tasks by Status</h3>
                <a href="{{ route('projects.tasks.index', $project) }}" class="text-xs text-violet-500 hover:underline">View all</a>
            </div>
            <div class="p-5 space-y-3">
                @foreach([
                    ['label'=>'To Do','key'=>'todo','color'=>'bg-slate-400'],
                    ['label'=>'In Progress','key'=>'in_progress','color'=>'bg-blue-500'],
                    ['label'=>'In Review','key'=>'in_review','color'=>'bg-yellow-500'],
                    ['label'=>'Done','key'=>'done','color'=>'bg-green-500'],
                ] as $row)
                @php $count = $stats[$row['key']]; $pct = $stats['total_tasks'] > 0 ? round($count/$stats['total_tasks']*100) : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700 dark:text-slate-300">{{ $row['label'] }}</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $count }} <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                    </div>
                    <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $row['color'] }} rounded-full transition-all duration-500" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Members --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-white">Members</h3>
            @can('manageMember', $project)
            <button x-data @click="$dispatch('open-modal', 'add-member')" class="text-xs text-violet-500 hover:underline">+ Add</button>
            @endcan
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($project->members as $member)
            <div class="flex items-center gap-3 px-5 py-3">
                <img src="{{ $member->avatar_url }}" class="w-8 h-8 rounded-full">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $member->name }}</p>
                    <p class="text-xs text-slate-400">{{ ucfirst($member->pivot->role) }}</p>
                </div>
                @can('manageMember', $project)
                @if($member->id !== $project->owner_id)
                <form method="POST" action="{{ route('projects.members.remove', [$project, $member]) }}">
                    @csrf @method('DELETE')
                    <button class="text-slate-300 hover:text-red-500 transition-colors">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </form>
                @endif
                @endcan
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
