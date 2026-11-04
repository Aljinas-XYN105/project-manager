@extends('layouts.app')
@section('title', 'My Expenses')

@section('content')
@php $statuses = \App\Models\Expense::STATUSES; $categories = \App\Models\Expense::CATEGORIES; @endphp

<div x-data="{ submit: {{ $errors->any() ? 'true' : 'false' }} }">

{{-- Header --}}
<div class="page-header">
    <div>
        <h1 class="page-title flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <i class="bi bi-receipt text-emerald-600 dark:text-emerald-400"></i>
            </span>
            My Expenses
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Submit and track your expense reimbursements.</p>
    </div>
    <div class="flex items-center gap-2">
        @if($pendingReview > 0)
        <a href="{{ route('expenses.approvals') }}" class="btn-secondary">
            <i class="bi bi-inbox"></i> Approvals
            <span class="ml-1 px-1.5 rounded-full bg-amber-500 text-white text-[10px]">{{ $pendingReview }}</span>
        </a>
        @endif
        <button @click="submit = true" class="btn-primary">
            <i class="bi bi-plus-lg"></i> New Expense
        </button>
    </div>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="card p-5">
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Pending Amount</p>
        <p class="mt-1.5 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($summary['total_pending'], 2) }}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ $summary['count_pending'] }} {{ Str::plural('expense', $summary['count_pending']) }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Approved Amount</p>
        <p class="mt-1.5 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($summary['total_approved'], 2) }}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ $summary['count_approved'] }} {{ Str::plural('expense', $summary['count_approved']) }}</p>
    </div>
</div>

{{-- Expenses table --}}
<div class="card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h2 class="font-semibold text-slate-900 dark:text-white">Submitted Expenses</h2>
    </div>

    @forelse($expenses as $e)
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-slate-50 dark:border-slate-800/60 last:border-b-0">
        {{-- Category icon --}}
        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
            <i class="bi {{ match($e->category) {
                'Travel' => 'bi-airplane',
                'Meals' => 'bi-cup-hot',
                'Office Supplies' => 'bi-pencil-square',
                'Software' => 'bi-laptop',
                'Training' => 'bi-mortarboard',
                'Accommodation' => 'bi-building',
                default => 'bi-receipt'
            } }} text-slate-500 dark:text-slate-400 text-sm"></i>
        </div>

        {{-- Details --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $e->title }}</p>
                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">{{ $e->category }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ $e->expense_date->format('D, M j, Y') }}</p>
            @if($e->description)
            <p class="text-xs text-slate-400 mt-0.5 truncate">"{{ $e->description }}"</p>
            @endif
            @if($e->reviewer)
            <p class="text-xs text-slate-400 mt-0.5">
                {{ ucfirst($e->status) }} by {{ $e->reviewer->name }} {{ $e->reviewed_at?->diffForHumans() }}
                @if($e->review_note) — "{{ $e->review_note }}"@endif
            </p>
            @endif
        </div>

        {{-- Amount + receipt --}}
        <div class="flex items-center gap-3 flex-shrink-0">
            @if($e->receipt_path)
            <a href="{{ route('expenses.receipt', $e) }}" target="_blank"
               class="text-slate-400 hover:text-violet-600 transition-colors" title="View receipt">
                <i class="bi bi-paperclip text-sm"></i>
            </a>
            @endif
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $e->currency }} {{ number_format($e->amount, 2) }}</p>
        </div>

        {{-- Status badge --}}
        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium flex-shrink-0 self-start sm:self-center {{ $statuses[$e->status]['class'] }}">
            {{ $statuses[$e->status]['label'] }}
        </span>

        {{-- Cancel --}}
        @if($e->status === 'pending')
        <form method="POST" action="{{ route('expenses.cancel', $e) }}" onsubmit="return confirm('Cancel this expense?')">
            @csrf @method('PATCH')
            <button class="btn-ghost btn-sm text-rose-500 flex-shrink-0">Cancel</button>
        </form>
        @endif
    </div>
    @empty
    <div class="py-16 text-center">
        <i class="bi bi-receipt text-4xl text-slate-200 dark:text-slate-700"></i>
        <p class="mt-3 text-sm text-slate-400">No expenses submitted yet.</p>
        <button @click="submit = true" class="mt-4 btn-primary btn-sm">Submit your first expense</button>
    </div>
    @endforelse

    @if($expenses->hasPages())
    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
        {{ $expenses->links() }}
    </div>
    @endif
</div>

{{-- Submit expense modal --}}
<template x-teleport="body">
<div x-show="submit" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="submit = false"></div>

    {{-- Modal --}}
    <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Submit Expense</h3>
            <button @click="submit = false" class="btn-ghost btn-sm p-1.5">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            @csrf

            {{-- Title --}}
            <div>
                <label class="form-label">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="form-input @error('title') border-rose-400 @enderror"
                       placeholder="e.g. Team lunch, Flight to Bangalore" required>
                @error('title')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            {{-- Amount + Category row --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Amount <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01"
                           class="form-input @error('amount') border-rose-400 @enderror"
                           placeholder="0.00" required>
                    @error('amount')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Category <span class="text-rose-500">*</span></label>
                    <select name="category" class="form-select @error('category') border-rose-400 @enderror" required>
                        <option value="">Select…</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Date --}}
            <div>
                <label class="form-label">Expense Date <span class="text-rose-500">*</span></label>
                <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}"
                       max="{{ today()->toDateString() }}"
                       class="form-input @error('expense_date') border-rose-400 @enderror" required>
                @error('expense_date')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="2"
                          class="form-input resize-none @error('description') border-rose-400 @enderror"
                          placeholder="Purpose or additional details…">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            {{-- Receipt --}}
            <div x-data="{
                fileName: '',
                fileSize: '',
                fileType: '',
                isDragging: false,
                clearFile() {
                    this.fileName = '';
                    this.fileSize = '';
                    this.fileType = '';
                    this.$refs.fileInput.value = '';
                },
                onFile(file) {
                    if (!file) return;
                    this.fileName = file.name;
                    this.fileSize = file.size > 1048576
                        ? (file.size / 1048576).toFixed(1) + ' MB'
                        : Math.round(file.size / 1024) + ' KB';
                    const ext = file.name.split('.').pop().toLowerCase();
                    this.fileType = ['jpg','jpeg','png','webp'].includes(ext) ? 'image' : 'pdf';
                },
                onDrop(e) {
                    this.isDragging = false;
                    const file = e.dataTransfer?.files?.[0];
                    if (file) { this.$refs.fileInput.files = e.dataTransfer.files; this.onFile(file); }
                }
            }">
                <label class="form-label">Receipt <span class="text-xs font-normal text-slate-400">(optional)</span></label>

                {{-- Hidden real input --}}
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf,.webp"
                       x-ref="fileInput"
                       @change="onFile($event.target.files[0])"
                       class="sr-only @error('receipt') border-rose-400 @enderror">

                {{-- Drop zone (shown when no file) --}}
                <div x-show="!fileName"
                     @click="$refs.fileInput.click()"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="onDrop($event)"
                     :class="isDragging
                         ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20'
                         : 'border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                     class="flex flex-col items-center justify-center gap-2 py-5 px-4 border-2 border-dashed rounded-xl cursor-pointer transition-colors">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         :class="isDragging ? 'bg-violet-100 dark:bg-violet-900/40' : 'bg-slate-100 dark:bg-slate-800'">
                        <i class="bi bi-cloud-upload text-lg"
                           :class="isDragging ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400'"></i>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                            <span class="text-violet-600 dark:text-violet-400">Click to upload</span> or drag & drop
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">JPG, PNG, PDF, WEBP — max 5 MB</p>
                    </div>
                </div>

                {{-- File preview (shown after selection) --}}
                <div x-show="fileName" x-cloak
                     class="flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                         :class="fileType === 'pdf'
                             ? 'bg-rose-100 dark:bg-rose-900/30'
                             : 'bg-violet-100 dark:bg-violet-900/30'">
                        <i :class="fileType === 'pdf'
                               ? 'bi-file-earmark-pdf text-rose-600 dark:text-rose-400'
                               : 'bi-file-earmark-image text-violet-600 dark:text-violet-400'"
                           class="bi text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 dark:text-white truncate" x-text="fileName"></p>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="fileSize"></p>
                    </div>
                    <button type="button" @click="clearFile()"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors flex-shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>

                @error('receipt')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="submit = false" class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="bi bi-send text-sm"></i> Submit for Approval
                </button>
            </div>
        </form>
    </div>
</div>
</template>

</div>
@endsection
