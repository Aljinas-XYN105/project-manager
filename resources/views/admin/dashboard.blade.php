@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 text-xs font-semibold ring-1 ring-rose-200 dark:ring-rose-800">
                <i class="bi bi-shield-fill-check text-xs"></i> Super Admin
            </span>
        </div>
        <h1 class="page-title">Admin Overview</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Organisation-wide performance dashboard</p>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET">
            <select name="period" onchange="this.form.submit()" class="form-select text-sm">
                <option value="7"  {{ $period=='7'  ? 'selected':'' }}>Last 7 days</option>
                <option value="14" {{ $period=='14' ? 'selected':'' }}>Last 14 days</option>
                <option value="30" {{ $period=='30' ? 'selected':'' }}>Last 30 days</option>
                <option value="90" {{ $period=='90' ? 'selected':'' }}>Last 90 days</option>
            </select>
        </form>
        <a href="{{ route('users.index') }}" class="btn-primary">
            <i class="bi bi-people-fill"></i> Manage Members
        </a>
    </div>
</div>

{{-- Top KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Total Users</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totalUsers }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $activeUsers }} active this period</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-people-fill text-violet-600 dark:text-violet-400"></i>
            </div>
        </div>
        <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
            <div class="h-1.5 bg-violet-500 rounded-full transition-all" style="width: {{ $totalUsers > 0 ? round($activeUsers/$totalUsers*100) : 0 }}%"></div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Active Projects</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $activeProjects }}</p>
                <p class="text-xs text-slate-500 mt-1">of {{ $totalProjects }} total</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-folder2-open text-blue-600 dark:text-blue-400"></i>
            </div>
        </div>
        <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
            <div class="h-1.5 bg-blue-500 rounded-full" style="width: {{ $totalProjects > 0 ? round($activeProjects/$totalProjects*100) : 0 }}%"></div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Tasks Done</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $doneTasks }}</p>
                <p class="text-xs {{ $overdueTasks > 0 ? 'text-rose-500' : 'text-slate-500' }} mt-1">
                    {{ $overdueTasks }} overdue
                </p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-check-circle-fill text-emerald-600 dark:text-emerald-400"></i>
            </div>
        </div>
        <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
            <div class="h-1.5 bg-emerald-500 rounded-full" style="width: {{ $totalTasks > 0 ? round($doneTasks/$totalTasks*100) : 0 }}%"></div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="section-heading mb-2">Hours Logged</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($totalHours, 1) }}</p>
                <p class="text-xs text-slate-500 mt-1">in the last {{ $period }} days</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-clock-history text-amber-600 dark:text-amber-400"></i>
            </div>
        </div>
        <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
            <div class="h-1.5 bg-amber-500 rounded-full w-3/4"></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

    {{-- Daily Hours Chart --}}
    <div class="xl:col-span-2 card p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-900 dark:text-white">Daily Hours Logged</h2>
            <span class="text-xs text-slate-400">Last 14 days</span>
        </div>
        @php $maxHours = $dailyHours->max('hours') ?: 1; @endphp
        <div class="flex items-end gap-1.5 h-36">
            @foreach($dailyHours as $day)
            <div class="flex-1 flex flex-col items-center gap-1 group">
                <div class="relative w-full">
                    <div class="w-full bg-violet-500/10 dark:bg-violet-500/20 rounded-t-sm group-hover:bg-violet-500/20 transition-colors cursor-default"
                         style="height: {{ max(4, round($day['hours']/$maxHours*120)) }}px"
                         title="{{ $day['label'] }}: {{ $day['hours'] }}h">
                        @if($day['hours'] > 0)
                        <div class="w-full bg-violet-500 rounded-t-sm transition-all" style="height: 100%"></div>
                        @endif
                    </div>
                </div>
                <span class="text-[9px] text-slate-400 rotate-0 truncate">{{ substr($day['label'],4) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Task Type Breakdown --}}
    <div class="card p-5">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Task Types</h2>
        @php
        $typeConfig = [
            'task'    => ['icon'=>'bi-check-square-fill','color'=>'bg-slate-400','label'=>'Task'],
            'bug'     => ['icon'=>'bi-bug-fill','color'=>'bg-rose-500','label'=>'Bug'],
            'feature' => ['icon'=>'bi-stars','color'=>'bg-violet-500','label'=>'Feature'],
            'story'   => ['icon'=>'bi-bookmark-fill','color'=>'bg-blue-500','label'=>'Story'],
            'epic'    => ['icon'=>'bi-lightning-fill','color'=>'bg-amber-500','label'=>'Epic'],
        ];
        $typeTotal = $taskTypes->sum() ?: 1;
        @endphp
        <div class="space-y-3">
            @foreach($typeConfig as $key => $tc)
            @php $cnt = $taskTypes->get($key, 0); @endphp
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg {{ str_replace('bg-','bg-',str_replace('500','100',$tc['color'])) }} flex items-center justify-center flex-shrink-0">
                    <i class="bi {{ $tc['icon'] }} text-xs {{ str_replace('bg-','text-',$tc['color']) }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $tc['label'] }}</span>
                        <span class="text-xs text-slate-500">{{ $cnt }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1.5 {{ $tc['color'] }} rounded-full" style="width: {{ round($cnt/$typeTotal*100) }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">

    {{-- Employee Performance Table --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Employee Performance</h2>
            <span class="text-xs text-slate-400">Last {{ $period }} days</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-right">Hours</th>
                        <th class="text-right">Done</th>
                        <th class="text-right">Open</th>
                        <th class="text-right hidden sm:table-cell">Reports</th>
                        <th class="text-right hidden md:table-cell">Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <img src="{{ $emp->user->avatar_url }}" class="w-7 h-7 rounded-full flex-shrink-0">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $emp->user->name }}</p>
                                    <p class="text-xs text-slate-400 capitalize">{{ $emp->user->getRoleNames()->first() ?? 'member' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $emp->hours_logged }}h</span>
                        </td>
                        <td class="text-right">
                            <span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ $emp->completed_tasks }}</span>
                        </td>
                        <td class="text-right">
                            <span class="text-sm {{ $emp->overdue_tasks > 0 ? 'text-rose-500 font-medium' : 'text-slate-500' }}">
                                {{ $emp->open_tasks }}
                                @if($emp->overdue_tasks > 0)
                                <span class="text-xs text-rose-400">({{ $emp->overdue_tasks }}⚠)</span>
                                @endif
                            </span>
                        </td>
                        <td class="text-right hidden sm:table-cell">
                            <span class="text-sm text-slate-500">{{ $emp->daily_reports }}</span>
                        </td>
                        <td class="text-right hidden md:table-cell">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                                    <div class="h-1.5 rounded-full {{ $emp->performance_pct >= 70 ? 'bg-emerald-500' : ($emp->performance_pct >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                         style="width: {{ $emp->performance_pct }}%"></div>
                                </div>
                                <span class="text-xs text-slate-500 w-8 text-right">{{ $emp->performance_pct }}%</span>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.employee', $emp->user) }}" class="btn-xs btn-ghost">
                                <i class="bi bi-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-8 text-slate-400 text-sm">No employee data found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Project Burn Rate --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Project Burn Rates</h2>
            <span class="text-xs text-slate-400">Active projects</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="text-right">Progress</th>
                        <th class="text-right hidden sm:table-cell">Est. hrs</th>
                        <th class="text-right hidden sm:table-cell">Logged</th>
                        <th class="text-right">Burn</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $p)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="w-6 h-6 rounded-md flex-shrink-0 flex items-center justify-center"
                                     style="background-color: {{ $p->project->color }}25;">
                                    <div class="w-2 h-2 rounded-full" style="background-color: {{ $p->project->color }};"></div>
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('projects.show', $p->project) }}"
                                       class="text-sm font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">
                                        {{ $p->project->name }}
                                    </a>
                                    <p class="text-xs text-slate-400">{{ $p->done_tasks }}/{{ $p->total_tasks }} tasks
                                        @if($p->overdue_tasks > 0)
                                        · <span class="text-rose-500">{{ $p->overdue_tasks }} overdue</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-14 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full hidden sm:block">
                                    <div class="h-1.5 rounded-full bg-violet-500" style="width: {{ $p->progress }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $p->progress }}%</span>
                            </div>
                        </td>
                        <td class="text-right hidden sm:table-cell">
                            <span class="text-sm text-slate-500">{{ $p->estimated_hrs > 0 ? $p->estimated_hrs.'h' : '—' }}</span>
                        </td>
                        <td class="text-right hidden sm:table-cell">
                            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">{{ $p->logged_hrs }}h</span>
                        </td>
                        <td class="text-right">
                            @if($p->burn_rate !== null)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold
                                {{ $p->burn_rate > 100 ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400' :
                                   ($p->burn_rate > 80 ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' :
                                   'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400') }}">
                                {{ $p->burn_rate }}%
                            </span>
                            @else
                            <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-8 text-slate-400 text-sm">No active projects.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Recent Daily Reports --}}
<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="font-semibold text-slate-900 dark:text-white">Recent Standups</h2>
        <a href="{{ route('daily-reports.team') }}" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all →</a>
    </div>
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($recentReports as $report)
        <div class="flex items-start gap-4 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <img src="{{ $report->user->avatar_url }}" class="w-8 h-8 rounded-full flex-shrink-0 mt-0.5">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $report->user->name }}</span>
                    <span class="text-xs text-slate-400">{{ $report->report_date->format('M j, Y') }}</span>
                    @if($report->blockers)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                        <i class="bi bi-exclamation-triangle-fill text-[9px]"></i> Blocker
                    </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ Str::limit($report->summary, 120) }}</p>
            </div>
            <div class="flex-shrink-0 text-right">
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $report->total_hours }}h</p>
                <a href="{{ route('daily-reports.show', $report) }}" class="text-[10px] text-violet-600 hover:underline">View</a>
            </div>
        </div>
        @empty
        <div class="py-10 text-center text-slate-400 text-sm">
            <i class="bi bi-journal-x text-2xl block mb-2"></i>
            No standup reports yet.
        </div>
        @endforelse
    </div>
</div>
@endsection
