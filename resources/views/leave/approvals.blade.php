@extends('layouts.app')
@section('title', 'Leave approvals')

@section('content')
@php $statuses = \App\Models\LeaveRequest::STATUSES; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Leave approvals</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            {{ auth()->user()->hasRole('admin') || auth()->user()->can('leave.approve') ? 'Requests from everyone.' : 'Requests from people who report to you.' }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('leave.calendar') }}" class="btn-ghost"><i class="bi bi-calendar3"></i> Team calendar</a>
        <div class="tab-bar">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Declined', 'all' => 'All'] as $k => $l)
            <a href="{{ route('leave.approvals', ['status' => $k]) }}" class="tab-item {{ $status === $k ? 'active' : '' }}">{{ $l }}</a>
            @endforeach
        </div>
    </div>
</div>

@if($errors->any())
<div class="alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
@endif

<div class="space-y-3">
    @forelse($requests as $r)
    <div class="card p-5 flex flex-col lg:flex-row lg:items-center gap-4" x-data="{ declining: false }">
        <div class="flex items-center gap-3 lg:w-64 flex-shrink-0">
            <img src="{{ $r->user->avatar_url }}" class="w-10 h-10 rounded-full" alt="">
            <div class="min-w-0">
                <a href="{{ route('users.show', $r->user) }}" class="font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">{{ $r->user->name }}</a>
                <p class="text-xs text-slate-500 truncate">{{ $r->user->department ?: $r->user->job_title }}</p>
            </div>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-slate-900 dark:text-white">
                <span class="inline-block w-2 h-2 rounded-full bg-{{ $r->type->color }}-500 mr-1"></span>
                <strong>{{ $r->type->name }}</strong> · {{ rtrim(rtrim(number_format($r->days, 1), '0'), '.') }} working {{ Str::plural('day', $r->days > 1 ? 2 : 1) }}
            </p>
            <p class="text-xs text-slate-500">{{ $r->date_range }} · requested {{ $r->created_at->diffForHumans() }}</p>
            @if($r->reason)<p class="text-xs text-slate-600 dark:text-slate-400 mt-1">“{{ $r->reason }}”</p>@endif
            @if(!empty($overlaps[$r->id]) && $overlaps[$r->id]->isNotEmpty())
            <p class="text-xs text-amber-600 mt-1"><i class="bi bi-people"></i> Also off then: {{ $overlaps[$r->id]->implode(', ') }}</p>
            @endif
            @if($r->reviewer)
            <p class="text-xs text-slate-400 mt-1">{{ $statuses[$r->status]['label'] }} by {{ $r->reviewer->name }} {{ $r->reviewed_at?->diffForHumans() }}@if($r->review_note) — “{{ $r->review_note }}”@endif</p>
            @endif
        </div>
        @if($r->status === 'pending' && $r->canBeReviewedBy(auth()->user()))
        <div class="flex-shrink-0">
            <div x-show="!declining" class="flex gap-2">
                <form method="POST" action="{{ route('leave.review', $r) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="decision" value="approved">
                    <button class="btn-primary btn-sm"><i class="bi bi-check-lg"></i> Approve</button>
                </form>
                <button type="button" @click="declining = true" class="btn-secondary btn-sm">Decline</button>
            </div>
            <form x-show="declining" x-cloak method="POST" action="{{ route('leave.review', $r) }}" class="flex gap-2">
                @csrf @method('PATCH')
                <input type="hidden" name="decision" value="rejected">
                <input type="text" name="note" required maxlength="500" placeholder="Reason for declining" class="form-input text-sm py-1.5 w-56">
                <button class="btn-danger btn-sm">Decline</button>
                <button type="button" @click="declining = false" class="btn-ghost btn-sm">✕</button>
            </form>
        </div>
        @else
        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium self-start lg:self-center {{ $statuses[$r->status]['class'] }}">{{ $statuses[$r->status]['label'] }}</span>
        @endif
    </div>
    @empty
    <div class="card empty-state py-16">
        <i class="bi bi-inbox text-4xl text-slate-300 dark:text-slate-600"></i>
        <p class="text-sm text-slate-500 mt-3">{{ $status === 'pending' ? 'No requests waiting for you.' : 'Nothing here.' }}</p>
    </div>
    @endforelse
</div>

@if($requests->hasPages())
<div class="mt-4">{{ $requests->links() }}</div>
@endif
@endsection
