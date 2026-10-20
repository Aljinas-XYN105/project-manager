@extends('layouts.app')
@section('title', 'Edit Report — '.$dailyReport->report_date->format('M j, Y'))

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="bi bi-journal-check text-amber-600 dark:text-amber-400"></i>
                </span>
                Edit Daily Report
            </h1>
            <p class="text-sm text-slate-500 mt-1">{{ $dailyReport->report_date->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('daily-reports.show', $dailyReport) }}" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @php
    $existingTasks = $dailyReport->reportTasks->map(fn ($rt) => [
        'task_id'       => $rt->task_id ?? '',
        'task_title'    => $rt->task ? $rt->task->title : null,
        'description'   => $rt->task_description,
        'hours'         => $rt->hours_spent,
        'notes'         => $rt->notes ?? '',
        'status_update' => $rt->status_update ?? '',
        'is_blocked'    => (bool) $rt->is_blocked,
    ])->values();
    @endphp
@php
    $taskOptions = $myTasks->map(fn ($t) => [
        'id'    => $t->id,
        'title' => $t->title,
        'label' => '['.($t->project->name ?? '?').'] '.$t->title.($t->ticket_key ? ' ('.$t->ticket_key.')' : ''),
    ])->values();
@endphp

    <form method="POST" action="{{ route('daily-reports.update', $dailyReport) }}"
          x-data="dailyReportForm({{ Js::from([
              'myTasks'  => $taskOptions,
              'rows'     => old('tasks') ? array_values(old('tasks')) : $existingTasks,
              'summary'  => old('summary', $dailyReport->summary),
              'plan'     => old('plan_for_tomorrow', $dailyReport->plan_for_tomorrow),
              'blockers' => old('blockers', $dailyReport->blockers),
          ]) }})">
        @csrf
        @method('PUT')

        <div class="space-y-5">

            {{-- Summary --}}
            <div class="card p-5">
                <label class="form-label">What did you accomplish? *</label>
                <textarea name="summary" rows="4" required x-model="summary"
                          class="form-input resize-none"
                          placeholder="Describe your accomplishments, meetings, reviews…"></textarea>
                @error('summary')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            @include('daily-reports._task-rows')

            {{-- Plan & Blockers --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="card p-5">
                    <label class="form-label">Plan for Tomorrow</label>
                    <textarea name="plan_for_tomorrow" rows="4" x-model="planForTomorrow"
                              class="form-input resize-none"
                              placeholder="What will you work on tomorrow?"></textarea>
                </div>
                <div class="card p-5">
                    <label class="form-label flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-amber-500"></i>
                        Blockers / Impediments
                    </label>
                    <textarea name="blockers" rows="4" x-model="blockers"
                              class="form-input resize-none border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-400/20"
                              placeholder="Anything blocking you?"></textarea>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <form method="POST" action="{{ route('daily-reports.destroy', $dailyReport) }}"
                      onsubmit="return confirm('Delete this report?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                        <i class="bi bi-trash3"></i> Delete Report
                    </button>
                </form>
                <div class="flex gap-3">
                    <a href="{{ route('daily-reports.show', $dailyReport) }}" class="btn-ghost">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-check-lg text-sm"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection
