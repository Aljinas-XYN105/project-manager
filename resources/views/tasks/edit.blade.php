@extends('layouts.app')
@section('title', 'Edit — '.$task->title)

@section('content')
<div class="max-w-3xl mx-auto"
     x-data="{ type: '{{ old('type', $task->type) }}' }">

    <div class="page-header">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                <a href="{{ route('projects.show', $task->project) }}" class="hover:text-violet-600 transition-colors">{{ $task->project->name }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <a href="{{ route('projects.tasks.show', $task) }}" class="hover:text-violet-600 transition-colors">{{ $task->ticket_key }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>Edit</span>
            </div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="bi bi-pencil text-violet-600 dark:text-violet-400 text-sm"></i>
                </span>
                Edit Task
            </h1>
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

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Type selector --}}
        <div class="card p-4">
            <label class="form-label mb-2">Type <span class="text-rose-500">*</span></label>
            <div class="flex flex-wrap gap-2">
                @php
                $types = [
                    'task'    => ['icon'=>'bi-check-square-fill','color'=>'text-slate-500','label'=>'Task','desc'=>'General work item'],
                    'bug'     => ['icon'=>'bi-bug-fill','color'=>'text-rose-500','label'=>'Bug','desc'=>'Something is broken'],
                    'feature' => ['icon'=>'bi-stars','color'=>'text-purple-500','label'=>'Feature','desc'=>'New functionality'],
                    'story'   => ['icon'=>'bi-bookmark-fill','color'=>'text-blue-500','label'=>'Story','desc'=>'User story'],
                    'epic'    => ['icon'=>'bi-lightning-fill','color'=>'text-orange-500','label'=>'Epic','desc'=>'Large initiative'],
                ];
                @endphp
                @foreach($types as $tv => $td)
                <label class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all
                              border-slate-200 dark:border-slate-700 hover:border-violet-400"
                       :class="type==='{{ $tv }}' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 shadow-sm' : ''"
                       @click="type='{{ $tv }}'">
                    <input type="radio" name="type" value="{{ $tv }}" :checked="type==='{{ $tv }}'" class="sr-only">
                    <i class="bi {{ $td['icon'] }} {{ $td['color'] }} text-base"></i>
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $td['label'] }}</span>
                        <p class="text-[10px] text-slate-400 leading-none mt-0.5">{{ $td['desc'] }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Title + Description --}}
        <div class="card p-5 space-y-4">
            <div>
                <label class="form-label">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $task->title) }}"
                       required autofocus class="form-input text-base font-medium"
                       placeholder="What needs to be done?">
                @error('title')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-input resize-none"
                          placeholder="Add details, context, or acceptance criteria…">{{ old('description', $task->description) }}</textarea>
            </div>
        </div>

        {{-- Details --}}
        <div class="card p-5">
            <p class="section-heading">Details</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                {{-- Status pill dropdown --}}
                <div x-data="{
                    open: false,
                    val: '{{ old('status',$task->status) }}',
                    opts: [
                        {val:'todo',        label:'To Do',      icon:'bi-circle',              chip:'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'},
                        {val:'in_progress', label:'In Progress',icon:'bi-arrow-repeat',        chip:'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'},
                        {val:'qa_testing',  label:'QA Testing', icon:'bi-clipboard2-check',    chip:'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'},
                        {val:'in_review',   label:'In Review',  icon:'bi-eye',                 chip:'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'},
                        {val:'done',        label:'Done',       icon:'bi-check-circle-fill',   chip:'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'},
                        {val:'cancelled',   label:'Cancelled',  icon:'bi-x-circle-fill',       chip:'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400'},
                    ],
                    get cur() { return this.opts.find(o=>o.val===this.val)||this.opts[0] }
                }">
                    <input type="hidden" name="status" :value="val">
                    <label class="form-label">Status</label>
                    <div class="relative">
                        <button type="button" @click="open=!open" @click.outside="open=false"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold w-full hover:opacity-90 transition-colors"
                                :class="cur.chip">
                            <i class="bi text-xs" :class="cur.icon"></i>
                            <span x-text="cur.label" class="flex-1 text-left"></span>
                            <i class="bi bi-chevron-down text-[10px] opacity-50"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 top-full mt-1 z-50 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                            <template x-for="o in opts" :key="o.val">
                                <button type="button" @click="val=o.val; open=false"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="o.chip">
                                        <i class="bi text-[10px]" :class="o.icon"></i>
                                    </span>
                                    <span x-text="o.label"></span>
                                    <i class="bi bi-check text-violet-500 text-xs ml-auto" x-show="o.val===val"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Priority pill dropdown --}}
                <div x-data="{
                    open: false,
                    val: '{{ old('priority',$task->priority) }}',
                    opts: [
                        {val:'low',      label:'Low',      icon:'bi-arrow-down',              chip:'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'},
                        {val:'medium',   label:'Medium',   icon:'bi-dash',                    chip:'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'},
                        {val:'high',     label:'High',     icon:'bi-arrow-up',                chip:'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'},
                        {val:'critical', label:'Critical', icon:'bi-exclamation-circle-fill', chip:'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300'},
                    ],
                    get cur() { return this.opts.find(o=>o.val===this.val)||this.opts[1] }
                }">
                    <input type="hidden" name="priority" :value="val">
                    <label class="form-label">Priority</label>
                    <div class="relative">
                        <button type="button" @click="open=!open" @click.outside="open=false"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold w-full hover:opacity-90 transition-colors"
                                :class="cur.chip">
                            <i class="bi text-xs" :class="cur.icon"></i>
                            <span x-text="cur.label" class="flex-1 text-left"></span>
                            <i class="bi bi-chevron-down text-[10px] opacity-50"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 top-full mt-1 z-50 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                            <template x-for="o in opts" :key="o.val">
                                <button type="button" @click="val=o.val; open=false"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="o.chip">
                                        <i class="bi text-[10px]" :class="o.icon"></i>
                                    </span>
                                    <span x-text="o.label"></span>
                                    <i class="bi bi-check text-violet-500 text-xs ml-auto" x-show="o.val===val"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Column / Stage</label>
                    <x-ui.select name="column_id"
                        :value="old('column_id',$task->column_id)"
                        :options="$columns->pluck('name','id')->toArray()" />
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date',$task->due_date?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Estimated Hours <span class="text-slate-400 font-normal text-xs">(optional)</span></label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours',$task->estimated_hours) }}"
                           min="0" step="0.5" class="form-input" placeholder="e.g. 4">
                    <p class="text-xs text-slate-400 mt-1">Used in burn-rate reports.</p>
                </div>
                <div>
                    <label class="form-label">Story Points <span class="text-slate-400 font-normal text-xs">(optional)</span></label>
                    <input type="number" name="story_points" value="{{ old('story_points',$task->story_points) }}"
                           min="0" max="100" class="form-input" placeholder="e.g. 3">
                    <p class="text-xs text-slate-400 mt-1">Fibonacci: 1, 2, 3, 5, 8…</p>
                </div>
                @if($sprints->count())
                <div>
                    <label class="form-label">Sprint</label>
                    <x-ui.select name="sprint_id" placeholder="Backlog (no sprint)"
                        :value="old('sprint_id',$task->sprint_id??'')"
                        :options="$sprints->mapWithKeys(fn($sp)=>[$sp->id=>$sp->name.' ('.ucfirst($sp->status).')'])->toArray()" />
                </div>
                @endif
            </div>
        </div>

        {{-- Bug-specific fields --}}
        <div x-show="type === 'bug'" x-transition
             class="card p-5 border-l-4 border-l-rose-400 space-y-4">
            <h4 class="font-semibold text-sm text-rose-700 dark:text-rose-400 flex items-center gap-2 -mb-1">
                <i class="bi bi-bug-fill"></i> Bug Report Details
            </h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Severity</label>
                    <x-ui.select name="severity" placeholder="Select severity"
                        :value="old('severity',$task->severity??'')"
                        :options="['blocker'=>'Blocker','critical'=>'Critical','major'=>'Major','minor'=>'Minor','trivial'=>'Trivial']" />
                </div>
                <div>
                    <label class="form-label text-xs">Environment</label>
                    <input type="text" name="environment" value="{{ old('environment',$task->environment??'') }}"
                           class="form-input" placeholder="e.g. Production, iOS 17, Chrome 120">
                </div>
            </div>
            <div>
                <label class="form-label text-xs">Steps to Reproduce</label>
                <textarea name="steps_to_reproduce" rows="3" class="form-input resize-none"
                          placeholder="1. Go to …&#10;2. Click …&#10;3. Observe the error">{{ old('steps_to_reproduce',$task->steps_to_reproduce??'') }}</textarea>
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
        <div class="card p-5">
            <label class="form-label mb-2">Assign To</label>
            <div class="flex flex-wrap gap-2">
                @foreach($members as $m)
                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600
                              cursor-pointer hover:border-violet-400 transition-colors
                              has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20">
                    <input type="checkbox" name="assignee_ids[]" value="{{ $m->id }}" class="sr-only"
                           @checked(in_array($m->id, old('assignee_ids', $task->assignees->pluck('id')->toArray())))>
                    <img src="{{ $m->avatar_url }}" class="w-6 h-6 rounded-full object-cover" title="{{ $m->name }}">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $m->name }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Labels --}}
        @if($labels->count())
        <div class="card p-5">
            <label class="form-label mb-2">Labels</label>
            <div class="flex flex-wrap gap-2">
                @foreach($labels as $label)
                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer border border-transparent
                              hover:border-slate-300 transition-colors has-[:checked]:ring-2 has-[:checked]:ring-offset-1"
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

        <div class="flex justify-between items-center pt-1">
            @can('delete', $task)
            <button type="button"
                    onclick="if(confirm('Delete this task permanently?')) document.getElementById('del-form').submit()"
                    class="btn-ghost text-rose-500 hover:text-rose-600 text-sm">
                <i class="bi bi-trash3"></i> Delete Task
            </button>
            @else
            <span></span>
            @endcan
            <div class="flex gap-3">
                <a href="{{ route('projects.tasks.show', $task) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary px-6">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </div>
    </form>

    @can('delete', $task)
    <form id="del-form" method="POST" action="{{ route('tasks.destroy', $task) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
    @endcan
</div>
@endsection
