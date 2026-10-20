@extends('layouts.app')
@section('title', 'My Leave')

@section('content')
@php $statuses = \App\Models\LeaveRequest::STATUSES; @endphp
<div x-data="{ apply: {{ $errors->any() ? 'true' : 'false' }} }">

<div class="page-header">
    <div>
        <h1 class="page-title">My Leave</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Balances for {{ $year }}. Weekends and company holidays are not counted.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('leave.calendar') }}" class="btn-ghost"><i class="bi bi-calendar3"></i> Team calendar</a>
        @if($pendingReview)
        <a href="{{ route('leave.approvals') }}" class="btn-secondary"><i class="bi bi-inbox"></i> Approvals <span class="ml-1 px-1.5 rounded-full bg-amber-500 text-white text-[10px]">{{ $pendingReview }}</span></a>
        @endif
        <button @click="apply = true" class="btn-primary"><i class="bi bi-plus-lg"></i> Request leave</button>
    </div>
</div>

{{-- Balances --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach($balances as $b)
    @php
        $c = $b->type->color;
        $pct = $b->type->is_unlimited || $b->type->days_per_year == 0 ? 0 : min(100, ($b->approved + $b->pending) / $b->type->days_per_year * 100);
    @endphp
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $b->type->name }}</p>
            <span class="w-2.5 h-2.5 rounded-full bg-{{ $c }}-500"></span>
        </div>
        <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white leading-none">
            {{ $b->remaining === null ? '∞' : rtrim(rtrim(number_format($b->remaining, 1), '0'), '.') }}
            <span class="text-sm font-medium text-slate-400">{{ $b->remaining === null ? 'unlimited' : 'days left' }}</span>
        </p>
        @unless($b->type->is_unlimited)
        <div class="mt-3 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
            <div class="h-full bg-{{ $c }}-500 rounded-full" style="width: {{ $pct }}%"></div>
        </div>
        @endunless
        <p class="mt-2 text-xs text-slate-500">
            {{ rtrim(rtrim(number_format($b->approved, 1), '0'), '.') }} used
            @if($b->pending) · {{ rtrim(rtrim(number_format($b->pending, 1), '0'), '.') }} pending @endif
            @unless($b->type->is_unlimited) · of {{ rtrim(rtrim(number_format($b->type->days_per_year, 1), '0'), '.') }} @endunless
            @unless($b->type->is_paid) · unpaid @endunless
        </p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Requests --}}
    <div class="xl:col-span-2 card overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">My requests</h2>
        </div>
        @forelse($requests as $r)
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5 border-b border-slate-50 dark:border-slate-800 last:border-b-0">
            <span class="w-1 self-stretch rounded-full bg-{{ $r->type->color }}-500 hidden sm:block"></span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $r->type->name }} · {{ rtrim(rtrim(number_format($r->days, 1), '0'), '.') }} {{ Str::plural('day', $r->days > 1 ? 2 : 1) }}</p>
                <p class="text-xs text-slate-500">{{ $r->date_range }}</p>
                @if($r->reason)<p class="text-xs text-slate-400 mt-0.5 truncate">“{{ $r->reason }}”</p>@endif
                @if($r->reviewer)
                <p class="text-xs text-slate-400 mt-0.5">{{ ucfirst($r->status) }} by {{ $r->reviewer->name }} {{ $r->reviewed_at?->diffForHumans() }}@if($r->review_note) — “{{ $r->review_note }}”@endif</p>
                @endif
            </div>
            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium self-start sm:self-center {{ $statuses[$r->status]['class'] }}">{{ $statuses[$r->status]['label'] }}</span>
            @if($r->status === 'pending' || ($r->status === 'approved' && ($r->start_date->isFuture() || $r->start_date->isToday())))
            <form method="POST" action="{{ route('leave.cancel', $r) }}" onsubmit="return confirm('Cancel this leave request?')">
                @csrf @method('PATCH')
                <button class="btn-ghost btn-sm text-rose-500">Cancel</button>
            </form>
            @endif
        </div>
        @empty
        <div class="empty-state py-14">
            <i class="bi bi-airplane text-4xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-sm text-slate-500 mt-3">No leave requests yet.</p>
        </div>
        @endforelse
        @if($requests->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">{{ $requests->links() }}</div>
        @endif
    </div>

    {{-- Holidays --}}
    <div class="card overflow-hidden self-start">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Upcoming holidays</h2>
        </div>
        @forelse($holidays as $h)
        <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0">
            <div class="w-11 text-center flex-shrink-0">
                <p class="text-[10px] font-semibold uppercase text-rose-500">{{ $h->date->format('M') }}</p>
                <p class="text-lg font-bold text-slate-900 dark:text-white leading-none">{{ $h->date->format('j') }}</p>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ $h->name }}</p>
                <p class="text-xs text-slate-400">{{ $h->date->format('l') }} · {{ $h->date->diffForHumans() }}</p>
            </div>
        </div>
        @empty
        <p class="px-5 py-8 text-center text-sm text-slate-400">No upcoming holidays have been added.</p>
        @endforelse
    </div>
</div>

{{-- Apply modal --}}
<div x-show="apply" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @keydown.escape.window="apply = false">
    <form method="POST" action="{{ route('leave.store') }}" @click.away="apply = false"
          x-data="{ start: @js(old('start_date', today()->toDateString())), end: @js(old('end_date', today()->toDateString())), half: {{ old('is_half_day') ? 'true' : 'false' }} }"
          class="card w-full max-w-md p-6 space-y-4">
        @csrf
        <h3 class="font-semibold text-slate-900 dark:text-white">Request leave</h3>
        @if($errors->any())
        <div class="alert-danger text-sm"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
        @endif
        <div>
            <label class="form-label">Leave type</label>
            <select name="leave_type_id" required class="form-select">
                @foreach($balances as $b)
                <option value="{{ $b->type->id }}" @selected(old('leave_type_id') == $b->type->id)>
                    {{ $b->type->name }} — {{ $b->remaining === null ? 'unlimited' : rtrim(rtrim(number_format($b->remaining, 1), '0'), '.').' left' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="start_date" x-model="start" @change="if (end < start) end = start" required class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="end_date" x-model="end" :min="start" required class="form-input">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300" :class="start !== end && 'opacity-50'">
            <input type="checkbox" name="is_half_day" value="1" x-model="half" :disabled="start !== end" class="rounded text-violet-600 border-slate-300">
            Half day (single-day requests only)
        </label>
        <div>
            <label class="form-label">Reason <span class="text-slate-400 font-normal">(optional)</span></label>
            <textarea name="reason" rows="3" maxlength="1000" class="form-input resize-none" placeholder="e.g. Family trip">{{ old('reason') }}</textarea>
        </div>
        <p class="text-xs text-slate-400">Goes to {{ auth()->user()->manager ? auth()->user()->manager->name.' (your manager)' : 'the leave approvers' }} for approval.</p>
        <div class="flex justify-end gap-2">
            <button type="button" @click="apply = false" class="btn-ghost">Cancel</button>
            <button class="btn-primary"><i class="bi bi-send"></i> Submit request</button>
        </div>
    </form>
</div>
</div>
@endsection
