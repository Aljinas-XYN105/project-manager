@extends('layouts.app')
@section('title', 'New Task')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="page-header">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('projects.show', $project) }}" class="hover:text-slate-600">{{ $project->name }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i><span>New Task</span>
            </div>
            <h1 class="page-title">Create Task</h1>
        </div>
        <a href="{{ route('projects.tasks.index', $project) }}" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card p-6">
        <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="space-y-5"
              x-data="{ type: '{{ old('type','task') }}' }">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <input type="hidden" name="column_id" value="{{ $column->id }}">

            {{-- Type selector --}}
            <div>
                <label class="form-label">Ticket Type *</label>
                <div class="flex flex-wrap gap-2">
                    @php
                    $types = ['task'=>['icon'=>'bi-check-square-fill','color'=>'text-slate-500','label'=>'Task'],
                              'bug'=>['icon'=>'bi-bug-fill','color'=>'text-red-500','label'=>'Bug'],
                              'feature'=>['icon'=>'bi-stars','color'=>'text-purple-500','label'=>'Feature'],
                              'story'=>['icon'=>'bi-bookmark-fill','color'=>'text-blue-500','label'=>'Story'],
                              'epic'=>['icon'=>'bi-lightning-fill','color'=>'text-orange-500','label'=>'Epic']];
                    @endphp
                    @foreach($types as $tv => $td)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-all
                                  border-slate-200 dark:border-slate-600 hover:border-violet-400"
                           :class="type==='{{ $tv }}' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 shadow-sm' : ''"
                           @click="type='{{ $tv }}'">
                        <input type="radio" name="type" value="{{ $tv }}" :checked="type==='{{ $tv }}'" class="sr-only">
                        <i class="bi {{ $td['icon'] }} {{ $td['color'] }}"></i>
                        <span class="text-sm">{{ $td['label'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="form-label">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="form-input text-base font-medium" placeholder="What needs to be done?">
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-input resize-none" placeholder="Add more details...">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                {{-- Status pill dropdown --}}
                <div x-data="{
                    open: false,
                    val: '{{ old('status','todo') }}',
                    opts: [
                        {val:'todo',        label:'To Do',      icon:'bi-circle',              chip:'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'},
                        {val:'in_progress', label:'In Progress',icon:'bi-arrow-repeat',        chip:'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'},
                        {val:'qa_testing',  label:'QA Testing', icon:'bi-clipboard2-check',    chip:'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'},
                        {val:'in_review',   label:'In Review',  icon:'bi-eye',                 chip:'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'},
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
                    val: '{{ old('priority','medium') }}',
                    opts: [
                        {val:'low',      label:'Low',      icon:'bi-arrow-down',             chip:'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'},
                        {val:'medium',   label:'Medium',   icon:'bi-dash',                   chip:'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'},
                        {val:'high',     label:'High',     icon:'bi-arrow-up',               chip:'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'},
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
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label flex items-center gap-1">
                        Estimated Hours
                        <span class="text-slate-400 font-normal text-xs">(optional)</span>
                    </label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}" min="0" step="0.5" class="form-input" placeholder="e.g. 4">
                    <p class="text-xs text-slate-400 mt-1">Used in burn-rate reports and project tracking.</p>
                </div>
                <div>
                    <label class="form-label flex items-center gap-1">
                        Story Points
                        <span class="text-slate-400 font-normal text-xs">(optional)</span>
                    </label>
                    <input type="number" name="story_points" value="{{ old('story_points') }}" min="0" max="100" class="form-input" placeholder="e.g. 3">
                    <p class="text-xs text-slate-400 mt-1">Relative effort estimate (Fibonacci: 1, 2, 3, 5, 8…).</p>
                </div>
                @if($project->sprints()->where('status','!=','completed')->exists())
                <div>
                    <label class="form-label">Sprint</label>
                    <select name="sprint_id" class="form-select">
                        <option value="">Backlog (no sprint)</option>
                        @foreach($project->sprints()->whereIn('status',['planning','active'])->get() as $sp)
                        <option value="{{ $sp->id }}" {{ old('sprint_id')==$sp->id?'selected':'' }}>
                            {{ $sp->name }} ({{ ucfirst($sp->status) }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            {{-- Bug-specific fields --}}
            <div x-show="type === 'bug'" x-transition class="border border-red-200 dark:border-red-800 rounded-xl p-4 space-y-4 bg-red-50 dark:bg-red-900/10">
                <h4 class="font-semibold text-sm text-red-700 dark:text-red-400 flex items-center gap-2">
                    <i class="bi bi-bug-fill"></i> Bug Details
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-xs">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="">Select severity</option>
                            @foreach(['blocker'=>'🔴 Blocker','critical'=>'🟠 Critical','major'=>'🟡 Major','minor'=>'🟢 Minor','trivial'=>'⚪ Trivial'] as $sv=>$sl)
                            <option value="{{ $sv }}" {{ old('severity')===$sv?'selected':'' }}>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Environment</label>
                        <input type="text" name="environment" value="{{ old('environment') }}"
                               class="form-input" placeholder="e.g. Production, Chrome 120">
                    </div>
                </div>
                <div>
                    <label class="form-label text-xs">Steps to Reproduce</label>
                    <textarea name="steps_to_reproduce" rows="3" class="form-input resize-none"
                              placeholder="1. Go to ...&#10;2. Click on ...&#10;3. See error">{{ old('steps_to_reproduce') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-xs">Expected Behavior</label>
                        <textarea name="expected_behavior" rows="2" class="form-input resize-none"
                                  placeholder="What should happen?">{{ old('expected_behavior') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label text-xs">Actual Behavior</label>
                        <textarea name="actual_behavior" rows="2" class="form-input resize-none"
                                  placeholder="What actually happens?">{{ old('actual_behavior') }}</textarea>
                    </div>
                </div>
            </div>
            <div>
                <label class="form-label">Assign To</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($members as $m)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 cursor-pointer hover:border-violet-400 transition-colors
                                  has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20">
                        <input type="checkbox" name="assignee_ids[]" value="{{ $m->id }}"
                               {{ in_array($m->id, old('assignee_ids',[])) ? 'checked' : '' }} class="sr-only">
                        <img src="{{ $m->avatar_url }}" class="w-6 h-6 rounded-full">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $m->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @if($labels->count())
            <div>
                <label class="form-label">Labels</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($labels as $label)
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer border border-transparent hover:border-slate-300 transition-colors has-[:checked]:ring-2 has-[:checked]:ring-offset-1"
                           style="background-color: {{ $label->color }}20;">
                        <input type="checkbox" name="label_ids[]" value="{{ $label->id }}" class="sr-only"
                               {{ in_array($label->id, old('label_ids',[])) ? 'checked' : '' }}>
                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $label->color }}"></span>
                        <span class="text-xs font-medium" style="color: {{ $label->color }}">{{ $label->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('projects.tasks.index', $project) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Create Task</button>
            </div>
        </form>
    </div>
</div>
@endsection
