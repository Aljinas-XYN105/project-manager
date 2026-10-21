@extends('layouts.app')
@section('title', 'New Sprint — '.$project->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="page-header">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                <a href="{{ route('projects.show', $project) }}" class="hover:text-violet-600 transition-colors">{{ $project->name }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <a href="{{ route('projects.sprints.index', $project) }}" class="hover:text-violet-600 transition-colors">Sprints</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>New</span>
            </div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="bi bi-speedometer2 text-violet-600 dark:text-violet-400"></i>
                </span>
                Create Sprint
            </h1>
        </div>
        <a href="{{ route('projects.sprints.index', $project) }}" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('projects.sprints.store', $project) }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">Sprint Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name"
                       value="{{ old('name', 'Sprint '.($project->sprints()->count()+1)) }}"
                       required class="form-input text-base font-medium"
                       placeholder="Sprint 1">
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Sprint Goal</label>
                <textarea name="goal" rows="3" class="form-input resize-none"
                          placeholder="What does the team aim to achieve in this sprint?">{{ old('goal') }}</textarea>
                <p class="text-xs text-slate-400 mt-1">Helps the team stay focused on the most important outcome.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date"
                           value="{{ old('start_date', today()->toDateString()) }}"
                           required class="form-input">
                    @error('start_date')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">End Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="end_date"
                           value="{{ old('end_date', today()->addDays(13)->toDateString()) }}"
                           required class="form-input">
                    @error('end_date')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                <a href="{{ route('projects.sprints.index', $project) }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-lg"></i> Create Sprint
                </button>
            </div>
        </form>
    </div>

    {{-- Tips --}}
    <div class="mt-5 card p-5 bg-violet-50 dark:bg-violet-900/10 border-violet-200 dark:border-violet-800">
        <h3 class="font-semibold text-violet-800 dark:text-violet-300 text-sm mb-3 flex items-center gap-2">
            <i class="bi bi-lightbulb-fill text-violet-500"></i> Sprint Tips
        </h3>
        <ul class="space-y-1.5 text-sm text-violet-700 dark:text-violet-400">
            <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Keep sprints 1–2 weeks for most teams</li>
            <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Write a clear, measurable sprint goal before adding tasks</li>
            <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Add tasks from the backlog after creating the sprint</li>
            <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Only start the sprint when the team is aligned on the plan</li>
        </ul>
    </div>
</div>
@endsection
