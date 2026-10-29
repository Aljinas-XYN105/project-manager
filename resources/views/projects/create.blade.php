@extends('layouts.app')
@section('title', 'New Project')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="page-header">
        <div>
            <h1 class="page-title">New Project</h1>
            <p class="text-sm text-slate-500 mt-0.5">Fill in the details to create a new project</p>
        </div>
        <a href="{{ route('projects.index') }}" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="form-label">Project Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="form-input" placeholder="e.g. Website Redesign">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input resize-none"
                              placeholder="Brief description of the project...">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold'] as $val => $label)
                        <option value="{{ $val }}" {{ old('status','planning') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $val => $label)
                        <option value="{{ $val }}" {{ old('priority','medium') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Project Color</label>
                    <div class="flex items-center gap-3">
                        <div class="color-swatch-btn">
                            <input type="color" name="color" value="{{ old('color','#3B82F6') }}">
                        </div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Click to choose accent color</span>
                    </div>
                </div>
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Budget ($)</label>
                    <input type="number" name="budget" value="{{ old('budget') }}" min="0" step="0.01"
                           class="form-input" placeholder="0.00">
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_public" id="is_public" value="1"
                               {{ old('is_public') ? 'checked' : '' }}
                               class="w-4 h-4 text-violet-600 border-slate-300 rounded focus:ring-violet-500">
                        <label for="is_public" class="text-sm text-slate-700 dark:text-slate-300">Make project public</label>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 ml-7">All members can view this project, even if they're not assigned to it.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('projects.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-lg"></i> Create Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
