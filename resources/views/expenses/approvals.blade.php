@extends('layouts.app')
@section('title', 'Expense Approvals')

@section('content')
@php $statuses = \App\Models\Expense::STATUSES; @endphp

<div class="page-header">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('expenses.index') }}" class="hover:text-violet-600 transition-colors flex items-center gap-1">
                <i class="bi bi-arrow-left text-[10px]"></i> My Expenses
            </a>
        </div>
        <h1 class="page-title flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <i class="bi bi-inbox text-emerald-600 dark:text-emerald-400"></i>
            </span>
            Expense Approvals
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Review and approve your team's expense submissions.</p>
    </div>
</div>

{{-- Status filter tabs --}}
<div class="flex items-center gap-1 mb-5 border-b border-slate-200 dark:border-slate-700">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $val => $label)
    <a href="{{ route('expenses.approvals', ['status' => $val]) }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px
              {{ $status === $val
                  ? 'border-violet-600 text-violet-600 dark:text-violet-400 dark:border-violet-400'
                  : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Expenses --}}
<div class="card overflow-hidden">
    @forelse($expenses as $e)
    <div class="px-5 py-4 border-b border-slate-50 dark:border-slate-800/60 last:border-b-0"
         x-data="{ reviewOpen: false }">
        <div class="flex flex-col sm:flex-row sm:items-start gap-3">

            {{-- Avatar --}}
            <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center font-bold text-sm text-violet-700 dark:text-violet-300 flex-shrink-0">
                {{ strtoupper(substr($e->user->name, 0, 2)) }}
            </div>

            {{-- Main details --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $e->title }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">{{ $e->category }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            by <span class="font-medium text-slate-700 dark:text-slate-200">{{ $e->user->name }}</span>
                            · {{ $e->expense_date->format('D, M j, Y') }}
                            · submitted {{ $e->created_at->diffForHumans() }}
                        </p>
                        @if($e->description)
                        <p class="text-xs text-slate-400 mt-1 italic">"{{ $e->description }}"</p>
                        @endif
                        @if($e->reviewer)
                        <p class="text-xs text-slate-400 mt-1">
                            {{ ucfirst($e->status) }} by {{ $e->reviewer->name }} {{ $e->reviewed_at?->diffForHumans() }}
                            @if($e->review_note) — "{{ $e->review_note }}"@endif
                        </p>
                        @endif
                    </div>

                    {{-- Right side: amount + status + receipt --}}
                    <div class="flex items-center gap-3 flex-shrink-0 self-start">
                        @if($e->receipt_path)
                        <a href="{{ route('expenses.receipt', $e) }}" target="_blank"
                           class="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-400 hover:underline">
                            <i class="bi bi-paperclip text-xs"></i> Receipt
                        </a>
                        @endif
                        <p class="text-base font-bold text-slate-900 dark:text-white">
                            {{ $e->currency }} {{ number_format($e->amount, 2) }}
                        </p>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium {{ $statuses[$e->status]['class'] }}">
                            {{ $statuses[$e->status]['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Approve/Reject actions (pending only) --}}
                @if($e->status === 'pending')
                <div class="mt-3">
                    <div x-show="!reviewOpen" class="flex items-center gap-2">
                        <form method="POST" action="{{ route('expenses.review', $e) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="approved">
                            <button class="btn-primary btn-sm">
                                <i class="bi bi-check-lg text-xs"></i> Approve
                            </button>
                        </form>
                        <button @click="reviewOpen = true" class="btn-ghost btn-sm text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                            <i class="bi bi-x-lg text-xs"></i> Reject
                        </button>
                    </div>

                    <div x-show="reviewOpen" x-cloak class="mt-2">
                        <form method="POST" action="{{ route('expenses.review', $e) }}" class="flex flex-col gap-2">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="rejected">
                            <textarea name="note" rows="2" required
                                      class="form-input resize-none text-sm"
                                      placeholder="Reason for rejection (required)…"></textarea>
                            <div class="flex items-center gap-2">
                                <button type="submit" class="btn-sm text-white font-semibold px-3 py-1.5 rounded-lg transition-colors"
                                        style="background:#ef4444">
                                    Confirm Reject
                                </button>
                                <button type="button" @click="reviewOpen = false" class="btn-ghost btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="py-16 text-center">
        <i class="bi bi-inbox text-4xl text-slate-200 dark:text-slate-700"></i>
        <p class="mt-3 text-sm text-slate-400">
            @if($status === 'pending') No pending expenses to review. @else No {{ $status }} expenses found. @endif
        </p>
    </div>
    @endforelse

    @if($expenses->hasPages())
    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
        {{ $expenses->links() }}
    </div>
    @endif
</div>

@endsection
