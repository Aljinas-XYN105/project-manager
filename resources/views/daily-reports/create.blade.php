@extends('layouts.app')
@section('title', 'Submit Daily Report')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="bi bi-journal-plus text-amber-600 dark:text-amber-400"></i>
                </span>
                Daily Standup
            </h1>
            <p class="text-sm text-slate-500 mt-1">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('daily-reports.index') }}" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

@php
    $taskOptions = $myTasks->map(fn ($t) => [
        'id'    => $t->id,
        'title' => $t->title,
        'label' => '['.($t->project->name ?? '?').'] '.$t->title.($t->ticket_key ? ' ('.$t->ticket_key.')' : ''),
    ])->values();
@endphp
    @if(count($timedTaskRows))
    <div class="mb-4 flex items-start gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-800 dark:text-emerald-300">
        <i class="bi bi-stopwatch-fill mt-0.5 flex-shrink-0"></i>
        <div>
            <span class="font-semibold">{{ count($timedTaskRows) }} task{{ count($timedTaskRows) > 1 ? 's' : '' }} pre-filled from your timer</span>
            — hours and descriptions are auto-populated from today's tracked time. You can edit any field before submitting.
        </div>
    </div>
    @endif
    <form method="POST" action="{{ route('daily-reports.store') }}"
          x-data="dailyReportForm({{ Js::from([
              'myTasks'  => $taskOptions,
              'rows'     => old('tasks') ? array_values(old('tasks')) : $timedTaskRows,
              'summary'  => old('summary', ''),
              'plan'     => old('plan_for_tomorrow', ''),
              'blockers' => old('blockers', ''),
          ]) }})">
        @csrf
        <input type="hidden" name="report_date" value="{{ $today }}">

        <div class="space-y-5">

            {{-- Summary --}}
            <div class="card p-5">
                <label class="form-label">What did you accomplish today? *</label>
                <textarea name="summary" rows="4" required x-model="summary"
                          class="form-input resize-none"
                          placeholder="Describe your accomplishments, meetings, reviews…">{{ old('summary') }}</textarea>
                @error('summary')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            @include('daily-reports._task-rows')

            {{-- Plan & Blockers --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="card p-5">
                    <label class="form-label">Plan for Tomorrow</label>
                    <textarea name="plan_for_tomorrow" rows="4" x-model="planForTomorrow"
                              class="form-input resize-none"
                              placeholder="What will you work on tomorrow?">{{ old('plan_for_tomorrow') }}</textarea>
                </div>
                <div class="card p-5">
                    <label class="form-label flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-amber-500"></i>
                        Blockers / Impediments
                    </label>
                    <textarea name="blockers" rows="4" x-model="blockers"
                              class="form-input resize-none border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-400/20"
                              placeholder="Anything blocking you? (leave empty if none)">{{ old('blockers') }}</textarea>
                    <p class="text-xs text-slate-400 mt-1.5">Leave empty if no blockers</p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('daily-reports.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">
                    <i class="bi bi-send-fill text-sm"></i> Submit Report
                </button>
            </div>
        </div>
    </form>
</div>

@endsection
