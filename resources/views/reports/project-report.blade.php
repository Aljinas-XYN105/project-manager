@extends('layouts.app')

@section('title', 'Project Report')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">Project Report</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Progress and completion metrics for all projects</p>
        </div>
        <a href="{{ route('reports.projects', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Export PDF
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('reports.projects') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['planning','active','on_hold','completed','archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Team</label>
                <select name="team_id" class="form-select">
                    <option value="">All Teams</option>
                    @foreach($teams as $t)
                    <option value="{{ $t->id }}" @selected(request('team_id') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('reports.projects') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- Projects Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Project</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Progress</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Tasks</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Hours</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Due Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($projects as $project)
                    @php $progress = $project->progress_percentage; @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex-shrink-0" style="background:{{ $project->color ?? '#6366f1' }}20">
                                    <div class="w-full h-full rounded-lg flex items-center justify-center">
                                        <span class="text-xs font-bold" style="color:{{ $project->color ?? '#6366f1' }}">{{ substr($project->name,0,1) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('projects.show', $project) }}" class="font-medium text-slate-900 dark:text-white hover:text-violet-600">{{ $project->name }}</a>
                                    <p class="text-xs text-slate-500">{{ $project->team->name ?? 'No team' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4"><span class="badge-{{ $project->status }}">{{ ucfirst(str_replace('_',' ',$project->status)) }}</span></td>
                        <td class="px-5 py-4 w-40">
                            <div class="flex items-center gap-2">
                                <div class="progress-bar flex-1">
                                    <div class="progress-fill" style="width:{{ $progress }}%"></div>
                                </div>
                                <span class="text-xs text-slate-500 w-8 text-right">{{ $progress }}%</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                            {{ $project->tasks_count ?? $project->tasks()->count() }}
                        </td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                            {{ number_format($project->total_tracked_hours, 1) }}h
                        </td>
                        <td class="px-5 py-4">
                            @if($project->due_date)
                                <span class="{{ $project->is_overdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $project->due_date->format('M j, Y') }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-400">No projects found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($projects->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $projects->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
