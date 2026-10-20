@extends('layouts.app')
@section('title', 'Edit — '.$task->title)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="page-header">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                <a href="{{ route('projects.show', $task->project) }}" class="hover:text-violet-600 transition-colors">{{ $task->project->name }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <a href="{{ route('projects.tasks.show', $task) }}" class="hover:text-violet-600 transition-colors">{{ $task->ticket_key }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>Edit</span>
            </div>
            <h1 class="page-title">Edit Task</h1>
        </div>
        <a href="{{ route('projects.tasks.show', $task) }}" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Cancel
        </a>
    </div>

    @if($errors->any())
    <div class="mb-4 p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800 rounded-xl">
        <ul class="text-sm text-rose-700 dark:text-rose-400 space-y-1">
            @foreach($errors->all() as $error)
            <li class="flex items-center gap-2"><i class="bi bi-exclamation-circle flex-shrink-0"></i>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card p-6">
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-5"
              x-data="{ type: '{{ old('type', $task->type) }}' }">
            @csrf
            @method('PUT')

            {{-- Title --}}
            <div>
                <label class="form-label">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $task->title) }}"
                       required autofocus class="form-input text-base font-medium"
                       placeholder="What needs to be done?">
                @error('title')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- Type --}}
            <div>
                <label class="form-label">Ticket Type</label>
                <div class="flex flex-wrap gap-2">
                    @php
                    $types = ['task'=>['icon'=>'bi-check-square-fill','color'=>'text-slate-500','label'=>'Task'],
                              'bug'=>['icon'=>'bi-bug-fill','color'=>'text-rose-500','label'=>'Bug'],
                              'feature'=>['icon'=>'bi-stars','color'=>'text-violet-500','label'=>'Feature'],
                              'story'=>['icon'=>'bi-bookmark-fill','color'=>'text-blue-500','label'=>'Story'],
                              'epic'=>['icon'=>'bi-lightning-fill','color'=>'text-amber-500','label'=>'Epic']];
                    @endphp
                    @foreach($types as $tv => $td)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-all border-slate-200 dark:border-slate-600 hover:border-violet-400"
                           :class="type==='{{ $tv }}' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 shadow-sm' : ''"
                           @click="type='{{ $tv }}'">
                        <input type="radio" name="type" value="{{ $tv }}" :checked="type==='{{ $tv }}'" class="sr-only">
                        <i class="bi {{ $td['icon'] }} {{ $td['color'] }}"></i>
                        <span class="text-sm">{{ $td['label'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="5" class="form-input resize-none"
                          placeholder="Add more details…">{{ old('description', $task->description) }}</textarea>
            </div>

            {{-- Grid fields --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Done','cancelled'=>'Cancelled'] as $v=>$l)
                        <option value="{{ $v }}" @selected(old('status',$task->status)===$v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(['low','medium','high','critical'] as $p)
                        <option value="{{ $p }}" @selected(old('priority',$task->priority)===$p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Column / Stage</label>
                    <select name="column_id" class="form-select">
                        @foreach($columns as $col)
                        <option value="{{ $col->id }}" @selected(old('column_id',$task->column_id)===$col->id)>{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date',$task->due_date?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours',$task->estimated_hours) }}"
                           min="0" step="0.5" class="form-input" placeholder="0">
                </div>
                <div>
                    <label class="form-label">Story Points</label>
                    <input type="number" name="story_points" value="{{ old('story_points',$task->story_points) }}"
                           min="0" max="100" class="form-input" placeholder="0">
                </div>
            </div>

            {{-- Bug fields --}}
            <div x-show="type === 'bug'" x-transition
                 class="border border-rose-200 dark:border-rose-800 rounded-xl p-4 space-y-4 bg-rose-50 dark:bg-rose-900/10">
                <h4 class="font-semibold text-sm text-rose-700 dark:text-rose-400 flex items-center gap-2">
                    <i class="bi bi-bug-fill"></i> Bug Details
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-xs">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="">Select severity</option>
                            @foreach(['blocker'=>'🔴 Blocker','critical'=>'🟠 Critical','major'=>'🟡 Major','minor'=>'🟢 Minor','trivial'=>'⚪ Trivial'] as $sv=>$sl)
                            <option value="{{ $sv }}" @selected(old('severity',$task->severity??'')===$sv)>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Environment</label>
                        <input type="text" name="environment" value="{{ old('environment',$task->environment??'') }}"
                               class="form-input" placeholder="e.g. Production, Chrome 120">
                    </div>
                </div>
                <div>
                    <label class="form-label text-xs">Steps to Reproduce</label>
                    <textarea name="steps_to_reproduce" rows="3" class="form-input resize-none"
                              placeholder="1. Go to …&#10;2. Click on …&#10;3. See error">{{ old('steps_to_reproduce',$task->steps_to_reproduce??'') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-xs">Expected Behavior</label>
                        <textarea name="expected_behavior" rows="2" class="form-input resize-none"
                                  placeholder="What should happen?">{{ old('expected_behavior',$task->expected_behavior??'') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label text-xs">Actual Behavior</label>
                        <textarea name="actual_behavior" rows="2" class="form-input resize-none"
                                  placeholder="What actually happens?">{{ old('actual_behavior',$task->actual_behavior??'') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Assignees --}}
            @if($members->count())
            <div>
                <label class="form-label">Assignees</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($members as $m)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-all border-slate-200 dark:border-slate-600 hover:border-violet-400 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20">
                        <input type="checkbox" name="assignee_ids[]" value="{{ $m->id }}" class="sr-only"
                               @checked(in_array($m->id, old('assignee_ids', $task->assignees->pluck('id')->toArray())))>
                        <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-xs font-semibold text-violet-600 dark:text-violet-400">
                            {{ strtoupper(substr($m->name, 0, 1)) }}
                        </div>
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $m->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Labels --}}
            @if($labels->count())
            <div>
                <label class="form-label">Labels</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($labels as $label)
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer border border-transparent hover:border-slate-300 transition-colors has-[:checked]:ring-2 has-[:checked]:ring-offset-1"
                           style="background-color: {{ $label->color }}20;">
                        <input type="checkbox" name="label_ids[]" value="{{ $label->id }}" class="sr-only"
                               @checked(in_array($label->id, old('label_ids', $task->labels->pluck('id')->toArray())))>
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $label->color }}"></span>
                        <span class="text-xs font-medium" style="color: {{ $label->color }}">{{ $label->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex justify-between items-center pt-4 border-t border-slate-100 dark:border-slate-700">
                @can('delete', $task)
                <button type="button"
                        onclick="if(confirm('Delete this task permanently?')) document.getElementById('del-form').submit()"
                        class="btn-ghost text-rose-500 hover:text-rose-600 text-sm">
                    <i class="bi bi-trash"></i> Delete
                </button>
                @else
                <span></span>
                @endcan
                <div class="flex gap-3">
                    <a href="{{ route('projects.tasks.show', $task) }}" class="btn-ghost">Cancel</a>
                    <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                </div>
            </div>
        </form>

        @can('delete', $task)
        <form id="del-form" method="POST" action="{{ route('tasks.destroy', $task) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
        @endcan
    </div>
</div>
@endsection
