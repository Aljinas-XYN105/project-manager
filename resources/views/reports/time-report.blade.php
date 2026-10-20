@extends('layouts.app')

@section('title', 'Time Report')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">Time Report</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.time', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
            <a href="{{ route('reports.time', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('reports.time') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Project</label>
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('reports.time') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total Hours</span>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['total_hours'], 1) }}h</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Billable Hours</span>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($summary['billable_hours'], 1) }}h</div>
            <p class="text-xs text-slate-500">{{ $summary['total_hours'] > 0 ? round($summary['billable_hours'] / $summary['total_hours'] * 100) : 0 }}% of total</p>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Entries</span>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $summary['entry_count'] }}</div>
        </div>
    </div>

    {{-- By Project breakdown --}}
    @if($byProject->count())
    <div class="card">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-semibold text-slate-900 dark:text-white">Hours by Project</h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach($byProject as $row)
            <div class="p-4 flex items-center gap-4">
                <div class="w-3 h-3 rounded-full flex-shrink-0" style="background:{{ $row->color ?? '#6366f1' }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $row->project_name }}</p>
                    <div class="progress-bar mt-1.5">
                        <div class="progress-fill" style="width:{{ $summary['total_hours'] > 0 ? round($row->hours / $summary['total_hours'] * 100) : 0 }}%; background:{{ $row->color ?? '#6366f1' }}"></div>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($row->hours, 1) }}h</p>
                    <p class="text-xs text-slate-500">{{ $summary['total_hours'] > 0 ? round($row->hours / $summary['total_hours'] * 100) : 0 }}%</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Detailed Entries --}}
    <div class="card">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900 dark:text-white">Detailed Entries</h2>
            <span class="text-sm text-slate-500">{{ $entries->total() }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Date</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">User</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Project / Task</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Duration</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Billable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($entries as $entry)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ $entry->started_at->format('M j') }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <img src="{{ $entry->user->avatar_url }}" class="w-6 h-6 rounded-full" alt="">
                                <span class="text-slate-700 dark:text-slate-300">{{ $entry->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $entry->project->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500">{{ $entry->task->title ?? 'Manual entry' }}</p>
                        </td>
                        <td class="px-5 py-3 font-mono text-slate-900 dark:text-white">{{ $entry->duration_formatted }}</td>
                        <td class="px-5 py-3">
                            @if($entry->is_billable)
                                <span class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Billable</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500">Non-billable</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-400">No time entries for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $entries->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
