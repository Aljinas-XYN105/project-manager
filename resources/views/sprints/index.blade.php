@extends('layouts.app')
@section('title', $project->name.' — Sprints')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-violet-600 transition-colors">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>Sprints</span>
        </div>
        <h1 class="page-title flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                <i class="bi bi-speedometer2 text-violet-600 dark:text-violet-400 text-sm"></i>
            </span>
            Sprints
        </h1>
    </div>
    @can('update', $project)
    <a href="{{ route('projects.sprints.create', $project) }}" class="btn-primary">
        <i class="bi bi-plus-lg"></i> New Sprint
    </a>
    @endcan
</div>

@if($sprints->isEmpty())
<div class="empty-state">
    <div class="empty-icon"><i class="bi bi-speedometer2"></i></div>
    <h3>No sprints yet</h3>
    <p>Create a sprint to plan your team's work in time-boxed iterations.</p>
    @can('update', $project)
    <a href="{{ route('projects.sprints.create', $project) }}" class="btn-primary btn-sm mt-2">
        <i class="bi bi-plus-lg"></i> Create First Sprint
    </a>
    @endcan
</div>
@else
<div class="space-y-3">
    @foreach($sprints as $sprint)
    @php
    $statusColors = [
        'planning'  => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        'active'    => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        'completed' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
        'cancelled' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    ];
    $statusColor = $statusColors[$sprint->status] ?? $statusColors['planning'];
    @endphp
    <div class="card p-5 transition-all hover:shadow-card-md
                {{ $sprint->status === 'active' ? 'border-l-4 border-l-emerald-400 dark:border-l-emerald-600' : '' }}">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2.5 flex-wrap mb-1">
                    <a href="{{ route('projects.sprints.show', [$project, $sprint]) }}"
                       class="font-semibold text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 text-base transition-colors">
                        {{ $sprint->name }}
                    </a>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                        {{ ucfirst($sprint->status) }}
                    </span>
                    @if($sprint->is_overdue)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                        <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Overdue
                    </span>
                    @endif
                </div>
                @if($sprint->goal)
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed">{{ $sprint->goal }}</p>
                @endif
                <div class="flex items-center gap-4 text-xs text-slate-400 flex-wrap">
                    <span class="flex items-center gap-1">
                        <i class="bi bi-calendar3"></i>
                        {{ $sprint->start_date->format('M d') }} – {{ $sprint->end_date->format('M d, Y') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="bi bi-list-task"></i>
                        {{ $sprint->tasks_count }} tasks
                    </span>
                    @if($sprint->status !== 'planning')
                    <span class="flex items-center gap-1">
                        <i class="bi bi-pie-chart"></i>
                        {{ $sprint->progress }}% complete
                    </span>
                    @endif
                </div>
                @if($sprint->status === 'active')
                <div class="mt-3">
                    <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all duration-700"
                             style="width: {{ $sprint->progress }}%; background: linear-gradient(90deg, #7c3aed, #a855f7)"></div>
                    </div>
                </div>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('projects.sprints.show', [$project, $sprint]) }}" class="btn-ghost btn-sm">
                    <i class="bi bi-eye"></i> View
                </a>
                @can('update', $project)
                @if($sprint->status === 'planning')
                <form method="POST" action="{{ route('projects.sprints.start', [$project, $sprint]) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-primary btn-sm"
                            onclick="return confirm('Start sprint {{ addslashes($sprint->name) }}?')">
                        <i class="bi bi-play-fill"></i> Start
                    </button>
                </form>
                @elseif($sprint->status === 'active')
                <form method="POST" action="{{ route('projects.sprints.complete', [$project, $sprint]) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-sm px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors"
                            onclick="return confirm('Complete sprint? Unfinished tasks return to backlog.')">
                        <i class="bi bi-check2-all"></i> Complete
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('projects.sprints.destroy', [$project, $sprint]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost btn-sm text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                            onclick="return confirm('Delete sprint? Tasks return to backlog.')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
