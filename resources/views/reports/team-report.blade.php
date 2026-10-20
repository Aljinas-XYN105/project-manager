@extends('layouts.app')

@section('title', 'Team Report')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">Team Report</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Member workload and productivity metrics</p>
        </div>
        <a href="{{ route('reports.teams', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Export PDF
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('reports.teams') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="form-label">Team</label>
                <select name="team_id" class="form-select">
                    <option value="">All Teams</option>
                    @foreach($teams as $t)
                    <option value="{{ $t->id }}" @selected(request('team_id') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from', now()->startOfMonth()->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}" class="form-input">
            </div>
            <button type="submit" class="btn-primary">Apply</button>
            <a href="{{ route('reports.teams') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- Member Workload --}}
    <div class="card">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-semibold text-slate-900 dark:text-white">Member Workload</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Member</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Assigned Tasks</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Completed</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Overdue</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Hours Logged</th>
                        <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Completion Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($members as $member)
                    @php
                        $rate = $member->assigned_count > 0 ? round($member->completed_count / $member->assigned_count * 100) : 0;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ $member->avatar_url }}" class="w-8 h-8 rounded-full" alt="">
                                <div>
                                    <a href="{{ route('users.show', $member) }}" class="font-medium text-slate-900 dark:text-white hover:text-violet-600">{{ $member->name }}</a>
                                    <p class="text-xs text-slate-500">{{ $member->job_title ?? $member->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300">{{ $member->assigned_count }}</td>
                        <td class="px-5 py-4 text-green-600 dark:text-green-400 font-medium">{{ $member->completed_count }}</td>
                        <td class="px-5 py-4 text-red-600 dark:text-red-400 font-medium">{{ $member->overdue_count }}</td>
                        <td class="px-5 py-4 font-mono text-slate-700 dark:text-slate-300">{{ number_format($member->hours_logged, 1) }}h</td>
                        <td class="px-5 py-4 w-40">
                            <div class="flex items-center gap-2">
                                <div class="progress-bar flex-1">
                                    <div class="progress-fill" style="width:{{ $rate }}%; background: {{ $rate >= 80 ? '#22c55e' : ($rate >= 50 ? '#6366f1' : '#ef4444') }}"></div>
                                </div>
                                <span class="text-xs text-slate-500 w-8 text-right">{{ $rate }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-400">No member data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
