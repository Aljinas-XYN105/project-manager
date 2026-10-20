@extends('layouts.app')

@section('title', $user->name)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="card p-6">
        <div class="flex items-start gap-5">
            <img src="{{ $user->avatar_url }}" class="w-20 h-20 rounded-2xl object-cover" alt="">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $user->name }}</h1>
                    @if($user->is_active)
                        <span class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
                    @else
                        <span class="badge bg-slate-100 text-slate-500">Inactive</span>
                    @endif
                </div>
                <p class="text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->job_title ?? $user->email }}</p>
                @if(auth()->user()->hasRole('admin') || auth()->user()->can('users.manage'))
                <a href="{{ route('users.edit', $user) }}" class="btn-secondary btn-sm mt-3"><i class="bi bi-pencil"></i> Edit member</a>
                @endif
                <div class="flex items-center gap-4 mt-3 text-sm text-slate-500 dark:text-slate-400 flex-wrap">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $user->email }}
                    </span>
                    @if($user->timezone)
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $user->timezone }}
                    </span>
                    @endif
                    <span>Joined {{ $user->created_at->format('M Y') }}</span>
                </div>
                @if($user->roles->count())
                <div class="flex items-center gap-2 mt-3">
                    @foreach($user->roles as $role)
                    <span class="badge bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">{{ $role->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Stats --}}
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="stat-card">
                    <span class="text-xs text-slate-500">Assigned Tasks</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['assigned_tasks'] }}</div>
                </div>
                <div class="stat-card">
                    <span class="text-xs text-slate-500">Completed</span>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed_tasks'] }}</div>
                </div>
                <div class="stat-card">
                    <span class="text-xs text-slate-500">Hours Logged</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['hours_logged'], 1) }}</div>
                </div>
                <div class="stat-card">
                    <span class="text-xs text-slate-500">Projects</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['projects_count'] }}</div>
                </div>
            </div>

            {{-- Employment --}}
            @php
                $viewer = auth()->user();
                $canSeePrivate = $viewer->is($user) || $viewer->hasRole('admin') || $viewer->can('employees.view-private') || $user->manager_id === $viewer->id;
                $user->loadMissing(['manager', 'directReports']);
                $rows = array_filter([
                    'Employee ID'  => $user->employee_code,
                    'Department'   => $user->department,
                    'Type'         => \App\Models\User::EMPLOYMENT_TYPES[$user->employment_type] ?? null,
                    'Joined'       => $user->joined_on ? $user->joined_on->format('M j, Y').' ('.$user->joined_on->diffForHumans(null, true).')' : null,
                    'Birthday'     => $user->date_of_birth ? ($canSeePrivate ? $user->date_of_birth->format('M j, Y') : $user->date_of_birth->format('F j')) : null,
                    'Phone'        => $user->phone,
                    'Emergency contact' => $canSeePrivate && $user->emergency_contact_name
                        ? $user->emergency_contact_name.($user->emergency_contact_phone ? ' · '.$user->emergency_contact_phone : '') : null,
                ]);
            @endphp
            <div class="card p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Employment</h3>
                @if($rows || $user->manager || $user->directReports->count())
                <dl class="space-y-2 text-sm">
                    @foreach($rows as $label => $value)
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-slate-800 dark:text-slate-200 text-right">{{ $value }}</dd></div>
                    @endforeach
                    @if($user->manager)
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Reports to</dt><dd><a href="{{ route('users.show', $user->manager) }}" class="text-violet-600 hover:underline">{{ $user->manager->name }}</a></dd></div>
                    @endif
                </dl>
                @if($user->directReports->count())
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mt-4 mb-2">Direct reports</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($user->directReports as $report)
                    <a href="{{ route('users.show', $report) }}" title="{{ $report->name }}"><img src="{{ $report->avatar_url }}" class="w-7 h-7 rounded-full ring-2 ring-white dark:ring-slate-900" alt=""></a>
                    @endforeach
                </div>
                @endif
                @else
                <p class="text-xs text-slate-400">No employment details yet.</p>
                @endif
            </div>

            {{-- Teams --}}
            @if($user->teams->count())
            <div class="card p-4">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Teams</h3>
                <div class="space-y-2">
                    @foreach($user->teams as $team)
                    <a href="{{ route('teams.show', $team) }}" class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600">
                        <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-xs font-bold text-violet-600">{{ substr($team->name,0,1) }}</div>
                        {{ $team->name }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Recent Tasks --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="card">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Recent Tasks</h3>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($recentTasks as $task)
                    <a href="{{ route('projects.tasks.show', $task) }}" class="flex items-start gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <span class="badge-{{ $task->status }} mt-0.5">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $task->title }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $task->project->name }}</p>
                        </div>
                        @if($task->due_date)
                        <span class="text-xs {{ $task->is_overdue ? 'text-red-500' : 'text-slate-400' }} flex-shrink-0">{{ $task->due_date->format('M j') }}</span>
                        @endif
                    </a>
                    @empty
                    <div class="p-6 text-center text-sm text-slate-400">No tasks assigned.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
