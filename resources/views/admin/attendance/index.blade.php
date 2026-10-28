@extends('layouts.app')
@section('title', 'Team Attendance')

@section('content')
@php
    $fmt = fn ($m) => sprintf('%dh %02dm', intdiv($m, 60), $m % 60);
    $statuses = \App\Models\User::AVAILABILITY;
    $canManage = auth()->user()->hasRole('admin') || auth()->user()->can('attendance.manage');
    $tz = auth()->user()->timezone;
@endphp

@include('admin._nav')

<div x-data="{ modal: false, form: {}, action: '', method: 'POST',
               openNew() { this.form = { user_id: '', work_date: '{{ now($tz)->toDateString() }}', clock_in: '09:00', clock_out: '', note: '' }; this.action = '{{ route('admin.attendance.store') }}'; this.method = 'POST'; this.modal = true; },
               openEdit(rec, url) { this.form = rec; this.action = url; this.method = 'PUT'; this.modal = true; } }">

<div class="page-header">
    <div>
        <h1 class="page-title">Team Attendance</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Who is available right now, and everyone's sign-in / sign-out times. Times shown in {{ $tz }}.</p>
    </div>
    @if($canManage)
    <button @click="openNew()" class="btn-primary"><i class="bi bi-plus-lg"></i> Add entry</button>
    @endif
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Online now', $stats['online'], 'bi-broadcast', 'emerald'],
        ['Present today', $stats['present'], 'bi-person-check', 'violet'],
        ['Absent (not on leave)', $stats['absent'], 'bi-person-dash', 'amber'],
        ['Active members', $stats['total'], 'bi-people', 'blue'],
    ] as [$label, $value, $icon, $color])
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 flex items-center justify-center flex-shrink-0">
            <i class="bi {{ $icon }} text-{{ $color }}-600 dark:text-{{ $color }}-400"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ $value }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $label }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Live board --}}
<div class="card overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h2 class="font-semibold text-slate-900 dark:text-white">Today</h2>
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400">Shown as offline after 5 minutes without activity</span>
            @php $clockedInCount = $board->where('clocked_in', true)->count(); @endphp
            @if($clockedInCount)
            <form method="POST" action="{{ route('admin.attendance.clock-out-all') }}"
                  onsubmit="return confirm('Clock out all {{ $clockedInCount }} clocked-in member(s)?')">
                @csrf
                <button type="submit" class="btn-secondary btn-sm text-red-600 border-red-200 hover:bg-red-50">
                    <i class="bi bi-box-arrow-right"></i> Clock out all
                </button>
            </form>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 divide-y sm:divide-y-0 divide-slate-50 dark:divide-slate-800">
        @foreach($board as $row)
        @php $st = $statuses[$row->status] ?? $statuses['offline']; @endphp
        <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800">
            <div class="relative flex-shrink-0">
                <img src="{{ $row->user->avatar_url }}" class="w-9 h-9 rounded-full" alt="">
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900 {{ $st['dot'] }}"></span>
            </div>
            <div class="flex-1 min-w-0">
                <a href="{{ route('users.show', $row->user) }}" class="text-sm font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">{{ $row->user->name }}</a>
                <p class="text-xs text-slate-500 truncate">
                    {{ $st['label'] }}@if($row->user->availability_note && $row->status !== 'offline') · {{ $row->user->availability_note }}@endif
                    @if($row->status === 'offline' && $row->user->last_seen_at) · seen {{ $row->user->last_seen_at->diffForHumans() }}@endif
                </p>
            </div>
            <div class="text-right flex-shrink-0">
                @if($row->first_in)
                <p class="text-xs text-slate-600 dark:text-slate-300">
                    In {{ $row->first_in->inUserTz()->format('g:i A') }}
                    @unless($row->clocked_in) · Out {{ $row->last_out?->inUserTz()->format('g:i A') }}@endunless
                </p>
                <p class="text-[11px] font-mono text-slate-400">{{ $fmt($row->minutes) }}</p>
                @elseif($row->leave)
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">On leave · {{ $row->leave->type->name }}{{ $row->leave->is_half_day ? ' ½' : '' }}</span>
                @else
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">Not in today</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- History --}}
<form method="GET" class="card p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="form-label">Member</label>
        <select name="user_id" class="form-select">
            <option value="">Everyone</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">From</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-input">
    </div>
    <div>
        <label class="form-label">To</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-input">
    </div>
    <button class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700 text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3 font-medium">Member</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Sign in</th>
                    <th class="px-5 py-3 font-medium">Sign out</th>
                    <th class="px-5 py-3 font-medium">Duration</th>
                    <th class="px-5 py-3 font-medium">Source</th>
                    @if($canManage)<th class="px-5 py-3"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($records as $r)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ $r->user->avatar_url }}" class="w-7 h-7 rounded-full" alt="">
                            <span class="font-medium text-slate-900 dark:text-white">{{ $r->user->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $r->work_date->format('D, M j') }}</td>
                    <td class="px-5 py-3">
                        <span class="text-slate-900 dark:text-white">{{ $r->clock_in_at->inUserTz()->format('g:i A') }}</span>
                        @if($r->clock_in_ip)<p class="text-[11px] text-slate-400 font-mono">{{ $r->clock_in_ip }}</p>@endif
                    </td>
                    <td class="px-5 py-3">
                        @if($r->clock_out_at)
                        <span class="text-slate-900 dark:text-white">{{ $r->clock_out_at->inUserTz()->format('g:i A') }}</span>
                        @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Still in</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 font-mono text-slate-700 dark:text-slate-300">{{ $r->duration_formatted }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $r->source === 'admin' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ ['login' => 'Sign-in', 'manual' => 'Clock in', 'admin' => 'Admin entry'][$r->source] ?? $r->source }}
                        </span>
                        @if($r->note)<p class="text-[11px] text-slate-400 mt-0.5 max-w-[14rem] truncate" title="{{ $r->note }}">{{ $r->note }}</p>@endif
                        @if($r->editor)<p class="text-[11px] text-slate-400">edited by {{ $r->editor->name }}</p>@endif
                    </td>
                    @if($canManage)
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <button type="button" class="btn-ghost btn-sm"
                                @click="openEdit({{ Js::from([
                                    'user_id'   => $r->user_id,
                                    'work_date' => $r->work_date->toDateString(),
                                    'clock_in'  => $r->clock_in_at->inUserTz()->format('H:i'),
                                    'clock_out' => $r->clock_out_at?->inUserTz()->format('H:i') ?? '',
                                    'note'      => $r->note ?? '',
                                ]) }}, '{{ route('admin.attendance.update', $r) }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.attendance.destroy', $r) }}" class="inline"
                              onsubmit="return confirm('Delete this attendance entry?')">
                            @csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-rose-500"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No attendance in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="p-4 border-t border-slate-100 dark:border-slate-800">{{ $records->links() }}</div>
    @endif
</div>

{{-- Add / edit modal --}}
@if($canManage)
<div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @keydown.escape.window="modal = false">
    <div @click.away="modal = false" class="card w-full max-w-md p-6">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4" x-text="method === 'PUT' ? 'Edit attendance' : 'Add attendance entry'"></h3>
        <form method="POST" :action="action" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <div>
                <label class="form-label">Member</label>
                <select name="user_id" x-model="form.user_id" required class="form-select">
                    <option value="">Select member…</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Date</label>
                <input type="date" name="work_date" x-model="form.work_date" required class="form-input">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Sign-in time</label>
                    <input type="time" name="clock_in" x-model="form.clock_in" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Sign-out time</label>
                    <input type="time" name="clock_out" x-model="form.clock_out" class="form-input">
                    <p class="form-hint">Leave empty if still working</p>
                </div>
            </div>
            <div>
                <label class="form-label">Note</label>
                <input type="text" name="note" x-model="form.note" maxlength="500" class="form-input" placeholder="e.g. Forgot to sign in, worked from site">
            </div>
            <p class="text-xs text-slate-400">Times are in your timezone ({{ $tz }}).</p>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="modal = false" class="btn-ghost">Cancel</button>
                <button class="btn-primary"><i class="bi bi-check-lg"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endif
</div>

@if($errors->any())
<script>document.addEventListener('DOMContentLoaded', () => window.toast(@js($errors->first()), 'error'));</script>
@endif
@endsection
