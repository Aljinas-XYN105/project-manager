@extends('layouts.app')
@section('title', 'Bug Tracker')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                <i class="bi bi-bug-fill text-rose-600 dark:text-rose-400"></i>
            </span>
            Bug Tracker
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track and resolve bugs across all your projects</p>
    </div>
    <a href="{{ route('tasks.create', ['type' => 'bug']) }}" class="btn-primary">
        <i class="bi bi-bug-fill"></i> Report Bug
    </a>
</div>

{{-- Stats strip --}}
@php
$bugsByStatus = \App\Models\Task::where('type','bug')
    ->whereHas('project', fn($q) => $q->whereHas('members', fn($m) => $m->where('users.id', auth()->id()))->orWhere('owner_id', auth()->id()))
    ->selectRaw('status, count(*) as cnt')->groupBy('status')->pluck('cnt','status');
$statsConfig = [
    'todo'        => ['label'=>'Open',        'color'=>'text-slate-700 dark:text-slate-300',    'bg'=>'bg-slate-100 dark:bg-slate-800', 'icon'=>'bi-circle',             'ring'=>'ring-slate-200 dark:ring-slate-700'],
    'in_progress' => ['label'=>'In Progress', 'color'=>'text-blue-700 dark:text-blue-400',     'bg'=>'bg-blue-50 dark:bg-blue-900/20',  'icon'=>'bi-arrow-right-circle', 'ring'=>'ring-blue-200 dark:ring-blue-800'],
    'in_review'   => ['label'=>'In Review',   'color'=>'text-amber-700 dark:text-amber-400',   'bg'=>'bg-amber-50 dark:bg-amber-900/20','icon'=>'bi-eye',                'ring'=>'ring-amber-200 dark:ring-amber-800'],
    'done'        => ['label'=>'Resolved',    'color'=>'text-emerald-700 dark:text-emerald-400','bg'=>'bg-emerald-50 dark:bg-emerald-900/20','icon'=>'bi-check-circle', 'ring'=>'ring-emerald-200 dark:ring-emerald-800'],
    'cancelled'   => ['label'=>'Closed',      'color'=>'text-slate-500',                       'bg'=>'bg-slate-100 dark:bg-slate-800',  'icon'=>'bi-x-circle',           'ring'=>'ring-slate-200 dark:ring-slate-700'],
];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    @foreach($statsConfig as $sv => $sc)
    <a href="{{ route('bugs.index', ['status'=>$sv]) }}"
       class="card p-3.5 flex items-center gap-3 hover:shadow-card-md transition-all
              {{ request('status') === $sv ? 'ring-2 ring-violet-500' : '' }}">
        <div class="w-8 h-8 rounded-xl {{ $sc['bg'] }} flex items-center justify-center flex-shrink-0 ring-1 {{ $sc['ring'] }}">
            <i class="bi {{ $sc['icon'] }} text-sm {{ $sc['color'] }}"></i>
        </div>
        <div class="min-w-0">
            <p class="text-lg font-bold {{ $sc['color'] }} leading-none">{{ $bugsByStatus->get($sv, 0) }}</p>
            <p class="text-[10px] text-slate-400 mt-0.5 leading-none truncate">{{ $sc['label'] }}</p>
        </div>
    </a>
    @endforeach
</div>

{{-- Filters --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('bugs.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[160px]">
            <label class="form-label">Status</label>
            <select name="status" class="form-select text-sm">
                <option value="">All Statuses</option>
                @foreach(['todo'=>'Open','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Resolved','cancelled'=>'Closed'] as $v=>$l)
                <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="form-label">Severity</label>
            <select name="severity" class="form-select text-sm">
                <option value="">All Severity</option>
                @foreach(['blocker'=>'🔴 Blocker','critical'=>'🟠 Critical','major'=>'🟡 Major','minor'=>'🟢 Minor','trivial'=>'⚪ Trivial'] as $v=>$l)
                <option value="{{ $v }}" {{ request('severity')===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[120px]">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-select text-sm">
                <option value="">All Priority</option>
                @foreach(['critical','high','medium','low'] as $p)
                <option value="{{ $p }}" {{ request('priority')===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-select text-sm">
                <option value="">All Projects</option>
                @foreach($projects as $proj)
                <option value="{{ $proj->id }}" {{ request('project_id')==$proj->id?'selected':'' }}>{{ $proj->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->anyFilled(['status','severity','priority','project_id']))
            <a href="{{ route('bugs.index') }}" class="btn-ghost btn-sm">Clear</a>
            @endif
        </div>
    </form>
</div>

@if($bugs->isEmpty())
<div class="empty-state">
    <div class="w-16 h-16 rounded-2xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mb-4">
        <i class="bi bi-bug text-3xl text-rose-300 dark:text-rose-600"></i>
    </div>
    <p class="font-semibold text-slate-600 dark:text-slate-400 text-lg">No bugs found</p>
    <p class="text-sm text-slate-400 mt-1">
        @if(request()->anyFilled(['status','severity','priority','project_id']))
            Try adjusting your filters
        @else
            Use <strong>Report Bug</strong> above or create a task with type "Bug" inside any project.
        @endif
    </p>
    @unless(request()->anyFilled(['status','severity','priority','project_id']))
    <a href="{{ route('projects.index') }}" class="mt-4 btn-secondary btn-sm">
        <i class="bi bi-arrow-right"></i> Go to a project to report a bug
    </a>
    @endunless
</div>
@else
<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
        <span class="text-xs font-semibold text-slate-500">{{ $bugs->total() }} bugs</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-28">Ticket</th>
                    <th>Bug Title</th>
                    <th class="hidden md:table-cell">Project</th>
                    <th class="hidden lg:table-cell">Severity</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th class="hidden md:table-cell">Assignees</th>
                    <th class="hidden lg:table-cell text-right">Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bugs as $bug)
                @php
                $severityColors = [
                    'blocker'  => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 ring-1 ring-rose-200 dark:ring-rose-800',
                    'critical' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 ring-1 ring-orange-200 dark:ring-orange-800',
                    'major'    => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800',
                    'minor'    => 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400 ring-1 ring-sky-200 dark:ring-sky-800',
                    'trivial'  => 'bg-slate-100 dark:bg-slate-800 text-slate-500 ring-1 ring-slate-200 dark:ring-slate-700',
                ];
                $statusConfig = [
                    'todo'        => ['label'=>'Open',        'class'=>'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'],
                    'in_progress' => ['label'=>'In Progress', 'class'=>'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'],
                    'in_review'   => ['label'=>'In Review',   'class'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'],
                    'done'        => ['label'=>'Resolved',    'class'=>'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'],
                    'cancelled'   => ['label'=>'Closed',      'class'=>'bg-slate-100 dark:bg-slate-800 text-slate-500'],
                ];
                @endphp
                <tr>
                    <td>
                        <span class="font-mono text-xs text-slate-400 bg-slate-50 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                            {{ $bug->ticket_key }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('projects.tasks.show', $bug) }}"
                           class="font-medium text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors leading-snug">
                            {{ $bug->title }}
                        </a>
                        @if($bug->environment)
                        <p class="text-xs text-slate-400 mt-0.5">
                            <i class="bi bi-display text-[10px]"></i> {{ $bug->environment }}
                        </p>
                        @endif
                    </td>
                    <td class="hidden md:table-cell">
                        <a href="{{ route('projects.show', $bug->project) }}"
                           class="text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                            {{ $bug->project->name }}
                        </a>
                    </td>
                    <td class="hidden lg:table-cell">
                        @if($bug->severity)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold capitalize
                                     {{ $severityColors[$bug->severity] ?? $severityColors['trivial'] }}">
                            {{ $bug->severity }}
                        </span>
                        @else
                        <span class="text-slate-300 dark:text-slate-600">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-{{ $bug->priority }}">{{ ucfirst($bug->priority) }}</span>
                    </td>
                    <td>
                        @php $sc = $statusConfig[$bug->status] ?? $statusConfig['todo']; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $sc['class'] }}">
                            {{ $sc['label'] }}
                        </span>
                    </td>
                    <td class="hidden md:table-cell">
                        @if($bug->assignees->count())
                        <div class="avatar-stack">
                            @foreach($bug->assignees->take(3) as $a)
                            <img src="{{ $a->avatar_url }}" title="{{ $a->name }}"
                                 class="w-7 h-7 rounded-full">
                            @endforeach
                            @if($bug->assignees->count() > 3)
                            <div class="avatar-more">+{{ $bug->assignees->count()-3 }}</div>
                            @endif
                        </div>
                        @else
                        <span class="text-xs text-slate-400">Unassigned</span>
                        @endif
                    </td>
                    <td class="hidden lg:table-cell text-right">
                        <span class="text-xs text-slate-400">{{ $bug->created_at->diffForHumans() }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($bugs->hasPages())
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
        {{ $bugs->links() }}
    </div>
    @endif
</div>
@endif
@endsection
