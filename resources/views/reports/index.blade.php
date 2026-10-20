@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">Reports</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Analytics and insights across your projects</p>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Hours Logged</span>
                <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_hours'], 1) }}h</div>
            <p class="text-xs text-slate-500">This month</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Tasks Completed</span>
                <div class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['tasks_completed'] }}</div>
            <p class="text-xs text-slate-500">This month</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Active Projects</span>
                <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['active_projects'] }}</div>
            <p class="text-xs text-slate-500">Currently active</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Team Members</span>
                <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['team_members'] }}</div>
            <p class="text-xs text-slate-500">Active contributors</p>
        </div>
    </div>

    {{-- Report Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('reports.time') }}" class="card-hover p-6 group">
            <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Time Report</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Analyze time logs by project, user, or date range. Export billable hours.</p>
            <div class="mt-4 flex items-center text-violet-600 dark:text-violet-400 text-sm font-medium">
                View report <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>

        <a href="{{ route('reports.projects') }}" class="card-hover p-6 group">
            <div class="w-12 h-12 rounded-2xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Project Report</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Track progress, completion rates, and task distribution per project.</p>
            <div class="mt-4 flex items-center text-green-600 dark:text-green-400 text-sm font-medium">
                View report <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>

        <a href="{{ route('reports.teams') }}" class="card-hover p-6 group">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Team Report</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Member workload, task velocity, and productivity metrics.</p>
            <div class="mt-4 flex items-center text-purple-600 dark:text-purple-400 text-sm font-medium">
                View report <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
    </div>

    {{-- Recent Snapshots --}}
    @if($snapshots->count())
    <div class="card">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-semibold text-slate-900 dark:text-white">Saved Report Snapshots</h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach($snapshots as $snap)
            <div class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $snap->name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ ucfirst($snap->type) }} · Saved {{ $snap->created_at->diffForHumans() }}</p>
                </div>
                <span class="badge badge-{{ $snap->type }}">{{ ucfirst($snap->type) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
