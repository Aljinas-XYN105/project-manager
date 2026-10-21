@extends('layouts.app')
@section('title', 'Edit — '.$project->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="page-header">
        <h1 class="page-title">Edit Project</h1>
        <a href="{{ route('projects.show', $project) }}" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card p-6">
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="form-label">Project Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $project->name) }}" required class="form-input">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input resize-none"
                              placeholder="What is this project about?">{{ old('description', $project->description) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold','completed'=>'Completed','archived'=>'Archived'] as $s=>$l)
                        <option value="{{ $s }}" {{ old('status', $project->status) === $s ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $p=>$l)
                        <option value="{{ $p }}" {{ old('priority', $project->priority) === $p ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Project Color</label>
                    <div class="flex items-center gap-3">
                        <div class="color-swatch-btn">
                            <input type="color" name="color" value="{{ old('color', $project->color) }}">
                        </div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Click to choose accent color</span>
                    </div>
                </div>
                <div>
                    <label class="form-label">Budget <span class="text-slate-400 font-normal text-xs">($, optional)</span></label>
                    <input type="number" name="budget" value="{{ old('budget', $project->budget) }}" min="0" step="0.01" class="form-input" placeholder="0.00">
                </div>
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" class="w-4 h-4 rounded text-violet-600 border-slate-300 dark:border-slate-600 focus:ring-violet-500"
                               {{ old('is_public', $project->is_public) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Make project public</span>
                    </label>
                    <p class="text-xs text-slate-400 mt-1 ml-7">All members can view this project, even if they're not assigned to it.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('projects.show', $project) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
