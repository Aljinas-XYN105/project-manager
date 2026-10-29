@extends('layouts.app')
@section('title', 'Projects')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Projects</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $projects->total() }} project{{ $projects->total() !== 1 ? 's' : '' }}</p>
    </div>
    @can('create', App\Models\Project::class)
    <a href="{{ route('projects.create') }}" class="btn-primary">
        <i class="bi bi-plus-lg"></i> New Project
    </a>
    @endcan
</div>

{{-- Filters --}}
<div class="mb-6">
    <form method="GET" class="flex flex-wrap gap-2 items-center">
        <div class="relative flex-1 min-w-[180px] max-w-xs">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search projects…"
                   class="form-input pl-9 text-sm">
        </div>
        <select name="status" class="form-select w-auto text-sm">
            <option value="">All Status</option>
            @foreach(['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold','completed'=>'Completed','archived'=>'Archived'] as $v=>$l)
            <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="priority" class="form-select w-auto text-sm">
            <option value="">All Priority</option>
            @foreach(['low','medium','high','critical'] as $p)
            <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary btn-sm">Filter</button>
        @if(request()->anyFilled(['search','status','priority']))
        <a href="{{ route('projects.index') }}" class="btn-ghost btn-sm">Clear</a>
        @endif
    </form>
</div>

@if($projects->count())
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($projects as $project)
    @php
    $statusColors = [
        'planning'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        'active'    => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        'on_hold'   => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        'completed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        'archived'  => 'bg-slate-100 dark:bg-slate-800 text-slate-500',
    ];
    $priorityColors = [
        'critical' => 'text-rose-600 dark:text-rose-400',
        'high'     => 'text-amber-600 dark:text-amber-400',
        'medium'   => 'text-blue-600 dark:text-blue-400',
        'low'      => 'text-slate-500',
    ];
    @endphp
    <div class="card p-5 flex flex-col gap-4 group hover:shadow-card-md transition-all duration-200">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm"
                     style="background-color: {{ $project->color }}20; border: 1px solid {{ $project->color }}30;">
                    <div class="w-3.5 h-3.5 rounded-full" style="background-color: {{ $project->color }};"></div>
                </div>
                <div class="min-w-0">
                    <a href="{{ route('projects.show', $project) }}"
                       class="font-semibold text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 text-sm truncate block">
                        {{ $project->name }}
                    </a>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $project->owner->name }}</p>
                </div>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium flex-shrink-0 {{ $statusColors[$project->status] ?? $statusColors['planning'] }}">
                {{ ucfirst(str_replace('_',' ',$project->status)) }}
            </span>
        </div>

        @if($project->description)
        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 -mt-1">{{ $project->description }}</p>
        @endif

        {{-- Progress --}}
        <div>
            <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                <span>Progress</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ $project->progress_percentage }}%</span>
            </div>
            <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                <div class="h-1.5 rounded-full transition-all duration-700"
                     style="width: {{ $project->progress_percentage }}%; background-color: {{ $project->color }};"></div>
            </div>
        </div>

        {{-- Meta row --}}
        <div class="flex items-center justify-between text-xs text-slate-400">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <i class="bi bi-people"></i>
                    {{ $project->members->count() + 1 }}
                </span>
                <span class="flex items-center gap-1 {{ $priorityColors[$project->priority] ?? '' }}">
                    <i class="bi bi-flag-fill text-[10px]"></i>
                    {{ ucfirst($project->priority ?? 'medium') }}
                </span>
            </div>
            @if($project->due_date)
            <span class="flex items-center gap-1 {{ $project->is_overdue ? 'text-rose-500 font-medium' : '' }}">
                <i class="bi bi-calendar3"></i>
                {{ $project->due_date->format('M j, Y') }}
            </span>
            @endif
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('projects.tasks.kanban', $project) }}"
               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                      bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                      hover:bg-violet-100 dark:hover:bg-violet-900/30 hover:text-violet-700 dark:hover:text-violet-400 transition-colors">
                <i class="bi bi-kanban text-sm"></i> Board
            </a>
            <a href="{{ route('projects.tasks.index', $project) }}"
               class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                      bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                      hover:bg-violet-100 dark:hover:bg-violet-900/30 hover:text-violet-700 dark:hover:text-violet-400 transition-colors">
                <i class="bi bi-list-task text-sm"></i> Tasks
            </a>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="bi bi-three-dots-vertical text-sm"></i>
                </button>
                <div x-show="open" x-cloak @click.away="open = false" x-transition
                     class="dropdown-menu absolute right-0 bottom-8 w-44 z-10">
                    <a href="{{ route('projects.show', $project) }}" class="dropdown-item">
                        <i class="bi bi-eye text-slate-400"></i> View details
                    </a>
                    <a href="{{ route('projects.edit', $project) }}" class="dropdown-item">
                        <i class="bi bi-pencil text-slate-400"></i> Edit
                    </a>
                    <a href="{{ route('projects.sprints.index', $project) }}" class="dropdown-item">
                        <i class="bi bi-lightning text-slate-400"></i> Sprints
                    </a>
                    @can('archive', $project)
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('projects.archive', $project) }}">
                        @csrf @method('PATCH')
                        <button class="dropdown-item w-full text-amber-600 dark:text-amber-400">
                            <i class="bi bi-archive"></i> Archive
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">
    {{ $projects->withQueryString()->links() }}
</div>

@else
<div class="empty-state">
    <i class="bi bi-folder-plus text-4xl text-slate-300 dark:text-slate-600 block mb-4"></i>
    <p class="font-semibold text-slate-600 dark:text-slate-400 text-lg">
        @if(request()->anyFilled(['search','status','priority']))
            No projects match your filters
        @else
            No projects yet
        @endif
    </p>
    <p class="text-sm text-slate-400 mt-1">
        @if(request()->anyFilled(['search','status','priority']))
            Try adjusting your search criteria
        @else
            Create your first project to start tracking work
        @endif
    </p>
    @if(!request()->anyFilled(['search','status','priority']))
    <a href="{{ route('projects.create') }}" class="btn-primary mt-4">
        <i class="bi bi-plus-lg"></i> Create Project
    </a>
    @endif
</div>
@endif
@endsection
