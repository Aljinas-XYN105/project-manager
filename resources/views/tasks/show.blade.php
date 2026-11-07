@extends('layouts.app')
@section('title', $task->title)

@php
$csrfToken = csrf_token();
$taskId    = $task->id;
$canUpdate = auth()->user()->can('update', $task);
$canDelete = auth()->user()->can('delete', $task);

$statusOptions = [
    ['val'=>'todo',        'label'=>'To Do',        'icon'=>'bi-circle',             'chip'=>'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
    ['val'=>'in_progress', 'label'=>'In Progress',   'icon'=>'bi-arrow-right-circle-fill', 'chip'=>'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'],
    ['val'=>'qa_testing',  'label'=>'QA Testing',    'icon'=>'bi-bug-fill',           'chip'=>'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300'],
    ['val'=>'in_review',   'label'=>'In Review',     'icon'=>'bi-eye-fill',           'chip'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'],
    ['val'=>'done',        'label'=>'Done',           'icon'=>'bi-check-circle-fill',  'chip'=>'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'],
    ['val'=>'cancelled',   'label'=>'Cancelled',      'icon'=>'bi-x-circle-fill',      'chip'=>'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400'],
];
$priorityOptions = [
    ['val'=>'low',      'label'=>'Low',      'icon'=>'bi-arrow-down-circle',      'chip'=>'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
    ['val'=>'medium',   'label'=>'Medium',   'icon'=>'bi-dash-circle',            'chip'=>'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300'],
    ['val'=>'high',     'label'=>'High',     'icon'=>'bi-arrow-up-circle',        'chip'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'],
    ['val'=>'critical', 'label'=>'Critical', 'icon'=>'bi-exclamation-circle-fill','chip'=>'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300'],
];
$typeOptions = [
    ['val'=>'task',    'label'=>'Task',    'icon'=>'bi-check-square-fill', 'chip'=>'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'],
    ['val'=>'bug',     'label'=>'Bug',     'icon'=>'bi-bug-fill',          'chip'=>'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300'],
    ['val'=>'feature', 'label'=>'Feature', 'icon'=>'bi-stars',             'chip'=>'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'],
    ['val'=>'story',   'label'=>'Story',   'icon'=>'bi-bookmark-fill',     'chip'=>'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'],
    ['val'=>'epic',    'label'=>'Epic',    'icon'=>'bi-lightning-fill',    'chip'=>'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300'],
];
@endphp

@section('content')
{{-- Breadcrumb --}}
<div class="flex items-center gap-1.5 text-xs text-slate-400 mb-4 flex-wrap">
    <a href="{{ route('projects.show', $task->project) }}" class="hover:text-violet-600 transition-colors font-medium">{{ $task->project->name }}</a>
    <i class="bi bi-chevron-right text-[9px]"></i>
    <a href="{{ route('projects.tasks.index', $task->project) }}" class="hover:text-violet-600 transition-colors">Tasks</a>
    <i class="bi bi-chevron-right text-[9px]"></i>
    <span class="font-mono text-[11px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md text-slate-500">{{ $task->ticket_key }}</span>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

    {{-- ══ MAIN COLUMN ══ --}}
    <div class="space-y-4 min-w-0">

        {{-- Title card --}}
        <div class="card p-5 pb-4"
             x-data="{
                 editing: false,
                 titleVal: @js($task->title),
                 original: @js($task->title),
                 async save() {
                     if (!this.titleVal.trim()) { this.titleVal = this.original; this.editing = false; return; }
                     if (this.titleVal === this.original) { this.editing = false; return; }
                     const r = await fetch('/tasks/{{ $taskId }}', {
                         method: 'PATCH',
                         headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                         body: JSON.stringify({title: this.titleVal})
                     });
                     if (r.ok) { this.original = this.titleVal; window.toast?.('Title saved', 'success'); }
                     else { this.titleVal = this.original; window.toast?.('Failed to save', 'error'); }
                     this.editing = false;
                 }
             }">
            <div class="flex items-start gap-3">
                {{-- Type badge --}}
                <span class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0
                    @if($task->type==='bug') bg-rose-100 dark:bg-rose-900/30
                    @elseif($task->type==='feature') bg-purple-100 dark:bg-purple-900/30
                    @elseif($task->type==='story') bg-blue-100 dark:bg-blue-900/30
                    @elseif($task->type==='epic') bg-orange-100 dark:bg-orange-900/30
                    @else bg-slate-100 dark:bg-slate-800 @endif">
                    <i class="bi {{ $task->type_icon }} {{ $task->type_color }} text-sm"></i>
                </span>
                <div class="flex-1 min-w-0">
                    {{-- Editable title --}}
                    <h1 x-show="!editing"
                        @click="@if($canUpdate) editing=true; $nextTick(()=>$refs.titleInput.select()) @endif"
                        class="text-xl font-bold text-slate-900 dark:text-white leading-snug {{ $canUpdate ? 'cursor-text hover:bg-slate-50 dark:hover:bg-slate-800/60 rounded-lg px-1.5 py-0.5 -mx-1.5 transition-colors' : '' }}">
                        <span x-text="titleVal"></span>
                    </h1>
                    <input x-show="editing" x-cloak x-ref="titleInput" x-model="titleVal"
                           @blur="save()" @keydown.enter.prevent="save()" @keydown.escape="editing=false; titleVal=original"
                           class="w-full text-xl font-bold text-slate-900 dark:text-white bg-white dark:bg-slate-900 border border-violet-400 rounded-lg px-2 py-0.5 outline-none shadow-sm leading-snug -mx-0.5">
                    <div class="flex items-center gap-2.5 mt-2 flex-wrap text-xs text-slate-400">
                        <span class="capitalize">{{ $task->type }}</span>
                        <span class="text-slate-200 dark:text-slate-700">·</span>
                        <span>by <strong class="text-slate-600 dark:text-slate-300">{{ $task->creator->name }}</strong></span>
                        <span class="text-slate-200 dark:text-slate-700">·</span>
                        <span>{{ $task->created_at->diffForHumans() }}</span>
                        @if($task->sprint)
                        <span class="text-slate-200 dark:text-slate-700">·</span>
                        <a href="{{ route('projects.sprints.show', [$task->project, $task->sprint]) }}"
                           class="inline-flex items-center gap-1 text-violet-500 hover:underline">
                            <i class="bi bi-speedometer2"></i> {{ $task->sprint->name }}
                        </a>
                        @endif
                    </div>
                </div>
                {{-- Action buttons --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                    @if($task->status !== 'done')
                    <form method="POST" action="{{ route('tasks.update', $task) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="done">
                        <button type="submit"
                                class="btn-sm inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                            <i class="bi bi-check-lg"></i> Done
                        </button>
                    </form>
                    @endif
                    @can('update', $task)
                    <a href="{{ route('tasks.edit', $task) }}"
                       class="btn-sm inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                       title="Full edit">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="card p-5"
             x-data="{
                 editing: false,
                 val: @js($task->description ?? ''),
                 original: @js($task->description ?? ''),
                 async save() {
                     if (this.val === this.original) { this.editing = false; return; }
                     const r = await fetch('/tasks/{{ $taskId }}', {
                         method: 'PATCH',
                         headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                         body: JSON.stringify({description: this.val})
                     });
                     if (r.ok) { this.original = this.val; window.toast?.('Description saved', 'success'); }
                     else { this.val = this.original; window.toast?.('Failed to save', 'error'); }
                     this.editing = false;
                 }
             }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                    <i class="bi bi-text-paragraph"></i> Description
                </h3>
                @if($canUpdate)
                <button x-show="!editing" @click="editing=true; $nextTick(()=>$refs.descInput.focus())"
                        class="text-xs text-slate-400 hover:text-violet-600 transition-colors flex items-center gap-1">
                    <i class="bi bi-pencil text-[10px]"></i> Edit
                </button>
                @endif
            </div>
            <div x-show="!editing">
                @if($task->description)
                <div class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                    {!! nl2br(e($task->description)) !!}
                </div>
                @else
                <p class="text-sm text-slate-400 italic @if($canUpdate) cursor-text @endif"
                   @if($canUpdate) @click="editing=true; $nextTick(()=>$refs.descInput.focus())" @endif>
                    @if($canUpdate) Click to add a description… @else No description. @endif
                </p>
                @endif
            </div>
            <div x-show="editing" x-cloak class="space-y-2">
                <textarea x-ref="descInput" x-model="val" rows="5"
                          placeholder="Add a description…"
                          class="w-full form-input resize-none text-sm leading-relaxed"></textarea>
                <div class="flex gap-2">
                    <button @click="save()"
                            class="px-3 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors">
                        Save
                    </button>
                    <button @click="editing=false; val=original"
                            class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        {{-- Bug Report --}}
        @if($task->type === 'bug')
        <div class="card p-5 border-l-4 border-l-rose-400">
            <h3 class="font-semibold text-rose-700 dark:text-rose-400 mb-4 flex items-center gap-2">
                <i class="bi bi-bug-fill"></i> Bug Report
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Severity</span>
                    @if($task->severity)
                    @php
                    $sevCls = ['blocker'=>'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400','critical'=>'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400','major'=>'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400','minor'=>'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400','trivial'=>'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'];
                    $sevIcon = ['blocker'=>'🔴','critical'=>'🟠','major'=>'🟡','minor'=>'🟢','trivial'=>'⚪'];
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold capitalize w-fit {{ $sevCls[$task->severity] ?? $sevCls['trivial'] }}">
                        {{ $sevIcon[$task->severity] ?? '' }} {{ ucfirst($task->severity) }}
                    </span>
                    @else
                    <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-slate-400 hover:text-violet-500 italic">Not set — add severity</a>
                    @endif
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Environment</span>
                    @if($task->environment ?? false)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 w-fit">
                        <i class="bi bi-display text-slate-400 text-xs"></i> {{ $task->environment }}
                    </span>
                    @else
                    <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-slate-400 hover:text-violet-500 italic">Not set</a>
                    @endif
                </div>
            </div>
            @if($task->steps_to_reproduce ?? false)
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Steps to Reproduce</p>
                <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-4 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap border border-slate-200 dark:border-slate-700 font-mono leading-relaxed">{{ $task->steps_to_reproduce }}</div>
            </div>
            @endif
            @if(($task->expected_behavior ?? false) || ($task->actual_behavior ?? false))
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-1.5 flex items-center gap-1.5 uppercase tracking-wide"><i class="bi bi-check-circle-fill"></i> Expected</p>
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-xl p-3.5 text-sm text-slate-700 dark:text-slate-300 border border-emerald-200 dark:border-emerald-900 leading-relaxed">{{ $task->expected_behavior ?? '—' }}</div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mb-1.5 flex items-center gap-1.5 uppercase tracking-wide"><i class="bi bi-x-circle-fill"></i> Actual</p>
                    <div class="bg-rose-50 dark:bg-rose-900/10 rounded-xl p-3.5 text-sm text-slate-700 dark:text-slate-300 border border-rose-200 dark:border-rose-900 leading-relaxed">{{ $task->actual_behavior ?? '—' }}</div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Subtasks --}}
        <div class="card overflow-hidden"
             x-data="{
                 adding: false,
                 newTitle: '',
                 submitting: false,
                 async addSubtask() {
                     if (!this.newTitle.trim() || this.submitting) return;
                     this.submitting = true;
                     const r = await fetch('{{ route('projects.tasks.store', $task->project) }}', {
                         method: 'POST',
                         headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                         body: JSON.stringify({
                             title: this.newTitle,
                             project_id: {{ $task->project_id }},
                             column_id: {{ $task->column_id ?? 'null' }},
                             parent_id: {{ $taskId }},
                             type: '{{ $task->type }}',
                             priority: '{{ $task->priority ?? 'medium' }}',
                         })
                     });
                     if (r.ok) {
                         window.toast?.('Subtask created', 'success');
                         this.newTitle = '';
                         this.adding = false;
                         window.location.reload();
                     } else {
                         window.toast?.('Failed to create subtask', 'error');
                     }
                     this.submitting = false;
                 }
             }">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                    <i class="bi bi-diagram-2 text-slate-400 text-xs"></i>
                    Subtasks
                    @if($task->subtasks->count())
                    @php $subDone = $task->subtasks->where('status','done')->count(); @endphp
                    <span class="text-xs font-normal text-slate-400">{{ $subDone }}/{{ $task->subtasks->count() }}</span>
                    @endif
                </h3>
                @if($canUpdate)
                <button @click="adding=!adding; $nextTick(()=>{ if(adding) $refs.subInput.focus() })"
                        class="text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-1 font-medium">
                    <i class="bi bi-plus-lg text-sm"></i> Add subtask
                </button>
                @endif
            </div>
            @if($task->subtasks->count())
            @php $pct = round($task->subtasks->where('status','done')->count() / $task->subtasks->count() * 100); @endphp
            <div class="px-5 pt-3 pb-1">
                <div class="h-1 bg-slate-100 dark:bg-slate-800 rounded-full">
                    <div class="h-1 bg-violet-400 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-800/60">
                @foreach($task->subtasks as $sub)
                <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <div class="w-4 h-4 rounded-full border-2 flex-shrink-0 flex items-center justify-center
                                {{ $sub->status === 'done' ? 'bg-emerald-400 border-emerald-400' : 'border-slate-300 dark:border-slate-600' }}">
                        @if($sub->status === 'done')<i class="bi bi-check text-white text-[9px]"></i>@endif
                    </div>
                    <a href="{{ route('projects.tasks.show', $sub) }}"
                       class="flex-1 text-sm {{ $sub->status === 'done' ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300' }} hover:text-violet-600 transition-colors">
                        {{ $sub->title }}
                    </a>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 capitalize">
                        {{ str_replace('_',' ',$sub->status) }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="px-5 py-4 text-center">
                <p class="text-sm text-slate-400">No subtasks yet</p>
            </div>
            @endif
            {{-- Inline add form --}}
            @if($canUpdate)
            <div x-show="adding" x-cloak class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                <div class="flex items-center gap-2">
                    <input x-ref="subInput" x-model="newTitle"
                           placeholder="Subtask title…"
                           @keydown.enter.prevent="addSubtask()"
                           @keydown.escape="adding=false; newTitle=''"
                           class="flex-1 form-input text-sm py-1.5">
                    <button @click="addSubtask()" :disabled="!newTitle.trim() || submitting"
                            class="px-3 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors disabled:opacity-40">
                        <span x-text="submitting ? 'Adding…' : 'Add'"></span>
                    </button>
                    <button @click="adding=false; newTitle=''"
                            class="px-2 py-1.5 text-xs text-slate-400 hover:text-slate-600 rounded-lg transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            @endif
        </div>

        {{-- Comments --}}
        <div class="card overflow-hidden" id="comments">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                    <i class="bi bi-chat text-slate-400 text-xs"></i>
                    Comments
                    @if($task->comments->count())
                    <span class="text-xs font-normal text-slate-400">({{ $task->comments->count() }})</span>
                    @endif
                </h3>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($task->comments as $comment)
                <div class="p-5 flex gap-3">
                    <img src="{{ $comment->user->avatar_url }}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="font-semibold text-sm text-slate-900 dark:text-white">{{ $comment->user->name }}</span>
                            <span class="text-xs text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{{ $comment->body }}</p>
                    </div>
                </div>
                @empty
                <div class="py-10 text-center">
                    <i class="bi bi-chat text-3xl text-slate-200 dark:text-slate-700 block mb-2"></i>
                    <p class="text-sm text-slate-400">No comments yet — start the conversation</p>
                </div>
                @endforelse
            </div>
            <div class="p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20"
                 x-data="{ body: '', submitting: false }">
                <div class="flex gap-3">
                    <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover">
                    <div class="flex-1">
                        <textarea x-model="body" rows="2" placeholder="Add a comment… (⌘+Enter to post)"
                                  class="form-input resize-none w-full text-sm"
                                  @keydown.meta.enter.prevent="
                                      if (!body.trim() || submitting) return; submitting=true;
                                      fetch('/comments',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},body:JSON.stringify({body,commentable_type:'task',commentable_id:{{ $taskId }}})})
                                          .then(()=>{body='';submitting=false;window.location.reload()})"></textarea>
                        <div class="flex justify-end mt-2">
                            <button @click="
                                if (!body.trim() || submitting) return; submitting=true;
                                fetch('/comments',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},body:JSON.stringify({body,commentable_type:'task',commentable_id:{{ $taskId }}})})
                                    .then(()=>{body='';submitting=false;window.location.reload()})
                                    .catch(()=>submitting=false)"
                                :disabled="!body.trim() || submitting"
                                class="btn-primary btn-sm disabled:opacity-40">
                                <i class="bi" :class="submitting ? 'bi-hourglass-split' : 'bi-send'"></i>
                                <span x-text="submitting ? 'Posting…' : 'Post'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attachments --}}
        <div class="card overflow-hidden"
             x-data="attachmentManager('{{ route('attachments.store', $task) }}', '{{ route('attachments.destroy', ':id') }}')">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                    <i class="bi bi-paperclip text-slate-400 text-xs"></i>
                    Attachments
                    <span class="text-xs font-normal text-slate-400" x-text="'(' + files.length + ')'"></span>
                </h3>
                <label class="btn-ghost btn-sm cursor-pointer text-xs">
                    <i class="bi bi-plus-lg text-sm"></i> Add
                    <input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                </label>
            </div>
            <div class="px-5 py-3 border-b border-dashed border-slate-200 dark:border-slate-700"
                 :class="dragging ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-400' : ''"
                 @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; upload($event.dataTransfer.files)">
                <div class="flex items-center justify-center gap-2 py-1 text-center">
                    <i class="bi bi-cloud-upload text-slate-300 dark:text-slate-600 text-lg" :class="dragging ? 'text-violet-400' : ''"></i>
                    <p class="text-xs text-slate-400">
                        Drop files or <label class="text-violet-500 hover:text-violet-600 cursor-pointer font-medium">browse<input type="file" class="sr-only" multiple @change="upload($event.target.files)"></label>
                        <span class="text-slate-300 dark:text-slate-600"> · max 100 MB</span>
                    </p>
                </div>
            </div>
            <template x-if="uploading">
                <div class="px-5 py-3 flex items-center gap-3 border-b border-slate-50 dark:border-slate-800 bg-violet-50/50 dark:bg-violet-900/10">
                    <i class="bi bi-arrow-up text-violet-500 animate-pulse"></i>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-violet-700 dark:text-violet-300" x-text="uploadStatus"></p>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1 mt-1">
                            <div class="bg-violet-500 h-1 rounded-full transition-all" :style="'width:'+uploadProgress+'%'"></div>
                        </div>
                    </div>
                </div>
            </template>
            <div class="divide-y divide-slate-50 dark:divide-slate-800/60">
                <template x-if="files.length === 0 && !uploading">
                    <div class="px-5 py-6 text-center"><p class="text-xs text-slate-400">No attachments yet</p></div>
                </template>
                <template x-for="att in files" :key="att.id">
                    <div class="flex items-center gap-3 px-5 py-3 group hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="att.is_image ? 'bg-sky-100 dark:bg-sky-900/30' : att.mime_type?.includes('pdf') ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-slate-100 dark:bg-slate-800'">
                            <i class="text-sm" :class="att.is_image ? 'bi bi-image text-sky-500' : att.is_video ? 'bi bi-play-circle-fill text-violet-500' : att.mime_type?.includes('pdf') ? 'bi bi-file-earmark-pdf text-rose-500' : att.mime_type?.includes('word') ? 'bi bi-file-earmark-word text-blue-500' : att.mime_type?.includes('sheet') || att.mime_type?.includes('excel') ? 'bi bi-file-earmark-excel text-emerald-500' : att.mime_type?.includes('zip') ? 'bi bi-file-earmark-zip text-amber-500' : 'bi bi-file-earmark text-slate-400'"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="att.original_name"></p>
                            <p class="text-xs text-slate-400 mt-0.5"><span x-text="att.size_formatted"></span> · <span x-text="att.uploader_name"></span> · <span x-text="att.created_ago"></span></p>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a :href="att.url" target="_blank" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500" title="Download"><i class="bi bi-download text-sm"></i></a>
                            <button @click="remove(att.id)" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 text-slate-400 hover:text-rose-500" title="Delete"><i class="bi bi-trash3 text-sm"></i></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Activity --}}
        @if(isset($activityLogs) && $activityLogs->count())
        <div class="card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                    <i class="bi bi-clock-history text-slate-400 text-xs"></i> Activity
                </h3>
            </div>
            <div class="p-5 space-y-3">
                @foreach($activityLogs as $log)
                <div class="flex gap-3">
                    <div class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="bi bi-circle-fill text-[5px] text-slate-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-slate-700 dark:text-slate-300">
                            @if($log->causer ?? false)<span class="font-semibold">{{ $log->causer->name }}</span> @endif
                            {{ $log->description }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ══ SIDEBAR ══ --}}
    <div class="space-y-3">

        {{-- Details card --}}
        <div class="card divide-y divide-slate-100 dark:divide-slate-800" style="overflow:visible">
            <div class="px-4 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Details</p>
            </div>

            {{-- STATUS --}}
            <div class="px-4 py-2.5 flex items-center gap-3"
                 x-data="{
                     open: false,
                     status: @js($task->status),
                     options: @js($statusOptions),
                     get current() { return this.options.find(o => o.val === this.status) || this.options[0] },
                     async set(val) {
                         this.status = val; this.open = false;
                         await fetch('/tasks/{{ $taskId }}/status', {
                             method:'PATCH',
                             headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                             body: JSON.stringify({status: val})
                         });
                         window.toast?.('Status updated', 'success');
                     }
                 }">
                <i class="bi bi-circle-half text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Status</span>
                <div class="relative flex-1">
                    <button @click="{{ $canUpdate ? 'open=!open' : '' }}"
                            @click.outside="open=false"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors max-w-full"
                            :class="[current.chip, '{{ $canUpdate }}' ? 'hover:opacity-90 cursor-pointer' : 'cursor-default']">
                        <i class="bi text-[10px] flex-shrink-0" :class="current.icon"></i>
                        <span x-text="current.label" class="truncate"></span>
                        @if($canUpdate)<i class="bi bi-chevron-down text-[9px] opacity-50 flex-shrink-0"></i>@endif
                    </button>
                    @if($canUpdate)
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1 z-50 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5 overflow-hidden">
                        <template x-for="opt in options" :key="opt.val">
                            <button @click="set(opt.val)"
                                    class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="opt.chip">
                                    <i class="bi text-[10px]" :class="opt.icon"></i>
                                </span>
                                <span x-text="opt.label" class="flex-1"></span>
                                <i class="bi bi-check text-violet-500 text-xs" x-show="opt.val === status"></i>
                            </button>
                        </template>
                    </div>
                    @endif
                </div>
            </div>

            {{-- PRIORITY --}}
            <div class="px-4 py-2.5 flex items-center gap-3"
                 x-data="{
                     open: false,
                     priority: @js($task->priority ?? 'medium'),
                     options: @js($priorityOptions),
                     get current() { return this.options.find(o => o.val === this.priority) || this.options[1] },
                     async set(val) {
                         this.priority = val; this.open = false;
                         await fetch('/tasks/{{ $taskId }}', {
                             method:'PATCH',
                             headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                             body: JSON.stringify({priority: val})
                         });
                         window.toast?.('Priority updated', 'success');
                     }
                 }">
                <i class="bi bi-flag text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Priority</span>
                <div class="relative flex-1">
                    <button @click="{{ $canUpdate ? 'open=!open' : '' }}"
                            @click.outside="open=false"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                            :class="[current.chip, '{{ $canUpdate }}' ? 'hover:opacity-90 cursor-pointer' : 'cursor-default']">
                        <i class="bi text-[10px]" :class="current.icon"></i>
                        <span x-text="current.label"></span>
                        @if($canUpdate)<i class="bi bi-chevron-down text-[9px] opacity-50 ml-0.5"></i>@endif
                    </button>
                    @if($canUpdate)
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1 z-50 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                        <template x-for="opt in options" :key="opt.val">
                            <button @click="set(opt.val)"
                                    class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="opt.chip">
                                    <i class="bi text-[10px]" :class="opt.icon"></i>
                                </span>
                                <span x-text="opt.label"></span>
                                <i class="bi bi-check text-violet-500 text-xs ml-auto" x-show="opt.val === priority"></i>
                            </button>
                        </template>
                    </div>
                    @endif
                </div>
            </div>

            {{-- TYPE --}}
            <div class="px-4 py-2.5 flex items-center gap-3"
                 x-data="{
                     open: false,
                     type: @js($task->type ?? 'task'),
                     options: @js($typeOptions),
                     get current() { return this.options.find(o => o.val === this.type) || this.options[0] },
                     async set(val) {
                         this.type = val; this.open = false;
                         await fetch('/tasks/{{ $taskId }}', {
                             method:'PATCH',
                             headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                             body: JSON.stringify({type: val})
                         });
                         window.toast?.('Type updated', 'success');
                     }
                 }">
                <i class="bi bi-tag text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Type</span>
                <div class="relative flex-1">
                    <button @click="{{ $canUpdate ? 'open=!open' : '' }}"
                            @click.outside="open=false"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                            :class="[current.chip, '{{ $canUpdate }}' ? 'hover:opacity-90 cursor-pointer' : 'cursor-default']">
                        <i class="bi text-[10px]" :class="current.icon"></i>
                        <span x-text="current.label"></span>
                        @if($canUpdate)<i class="bi bi-chevron-down text-[9px] opacity-50 ml-0.5"></i>@endif
                    </button>
                    @if($canUpdate)
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1 z-50 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                        <template x-for="opt in options" :key="opt.val">
                            <button @click="set(opt.val)"
                                    class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="opt.chip">
                                    <i class="bi text-[10px]" :class="opt.icon"></i>
                                </span>
                                <span x-text="opt.label"></span>
                                <i class="bi bi-check text-violet-500 text-xs ml-auto" x-show="opt.val === type"></i>
                            </button>
                        </template>
                    </div>
                    @endif
                </div>
            </div>

            {{-- DUE DATE --}}
            <div class="px-4 py-2.5 flex items-center gap-3"
                 x-data="{
                     editing: false,
                     val: @js($task->due_date?->format('Y-m-d') ?? ''),
                     display: @js($task->due_date?->format('M j, Y') ?? ''),
                     async save() {
                         const r = await fetch('/tasks/{{ $taskId }}', {
                             method:'PATCH',
                             headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ $csrfToken }}','Accept':'application/json'},
                             body: JSON.stringify({due_date: this.val || null})
                         });
                         if (r.ok) {
                             this.display = this.val ? new Date(this.val + 'T00:00:00').toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}) : '';
                             window.toast?.('Due date saved', 'success');
                         }
                         this.editing = false;
                     }
                 }">
                <i class="bi bi-calendar3 text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Due</span>
                <div class="flex-1">
                    <div x-show="!editing">
                        <template x-if="display">
                            <button @click="{{ $canUpdate ? 'editing=true' : '' }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium {{ $task->is_overdue ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200' }} {{ $canUpdate ? 'hover:text-violet-600 cursor-pointer' : 'cursor-default' }} transition-colors">
                                <span x-text="display"></span>
                                @if($task->is_overdue)<span class="text-[10px] font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-600 px-1.5 py-0.5 rounded">Overdue</span>@endif
                            </button>
                        </template>
                        <template x-if="!display">
                            <button @click="{{ $canUpdate ? 'editing=true' : '' }}"
                                    class="text-xs text-slate-400 {{ $canUpdate ? 'hover:text-violet-500 cursor-pointer' : 'cursor-default' }} italic">
                                @if($canUpdate) Set date @else — @endif
                            </button>
                        </template>
                    </div>
                    <div x-show="editing" x-cloak class="flex items-center gap-1.5" @keydown.escape.window="editing=false" @click.outside="editing=false">
                        <x-ui.datepicker name="due_date" x-model="val" @change="save()" />
                        <button @click="val=''; save()" x-show="val"
                                class="text-slate-400 hover:text-rose-500 transition-colors flex-shrink-0">
                            <i class="bi bi-x-circle text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ASSIGNEES --}}
            <div class="px-4 py-2.5 flex items-start gap-3"
                 x-data="{
                     assignees: {{ Js::from($task->assignees->map(fn($a) => ['id'=>$a->id,'name'=>$a->name,'avatar'=>$a->avatar_url])) }},
                     available: {{ Js::from($members->whereNotIn('id', $task->assignees->pluck('id'))->values()->map(fn($m) => ['id'=>$m->id,'name'=>$m->name,'avatar'=>$m->avatar_url])) }},
                     open: false,
                     addUrl: '{{ route('tasks.assignees.add', $task) }}',
                     removeBase: '{{ url('tasks/'.$task->id.'/assignees') }}',
                     csrfToken: '{{ $csrfToken }}',
                     async add(user) {
                         this.open = false;
                         const r = await fetch(this.addUrl, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},body:JSON.stringify({user_id:user.id})});
                         if (r.ok) { this.assignees.push(user); this.available = this.available.filter(u => u.id !== user.id); }
                     },
                     async remove(user) {
                         const r = await fetch(this.removeBase+'/'+user.id, {method:'DELETE',headers:{'X-CSRF-TOKEN':this.csrfToken}});
                         if (r.ok) { this.assignees = this.assignees.filter(u => u.id !== user.id); this.available.push(user); this.available.sort((a,b)=>a.name.localeCompare(b.name)); }
                     }
                 }">
                <i class="bi bi-person text-[10px] text-slate-400 w-3 text-center flex-shrink-0 mt-0.5"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0 mt-0.5">Assigned</span>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap gap-1.5 mb-1">
                        <template x-if="assignees.length === 0">
                            <span class="text-xs text-slate-400 italic">Unassigned</span>
                        </template>
                        <template x-for="a in assignees" :key="a.id">
                            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 rounded-full px-2 py-0.5 group">
                                <img :src="a.avatar" class="w-4 h-4 rounded-full object-cover flex-shrink-0">
                                <span class="text-xs text-slate-700 dark:text-slate-300 max-w-[80px] truncate" x-text="a.name"></span>
                                @if($canUpdate)
                                <button @click="remove(a)" class="opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 hover:text-rose-500 leading-none">
                                    <i class="bi bi-x text-[10px]"></i>
                                </button>
                                @endif
                            </div>
                        </template>
                    </div>
                    @if($canUpdate)
                    <div class="relative inline-block">
                        <button @click="open=!open" @click.outside="open=false"
                                class="text-[10px] font-medium text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-0.5">
                            <i class="bi bi-plus-circle text-xs"></i> Add
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 top-5 z-30 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-1.5 max-h-48 overflow-y-auto">
                            <template x-if="available.length === 0">
                                <p class="text-xs text-slate-400 text-center py-3">All members assigned</p>
                            </template>
                            <template x-for="u in available" :key="u.id">
                                <button @click="add(u)"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <img :src="u.avatar" class="w-5 h-5 rounded-full flex-shrink-0 object-cover">
                                    <span class="text-sm text-slate-700 dark:text-slate-300 truncate" x-text="u.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- LABELS --}}
            @if($task->labels->count())
            <div class="px-4 py-2.5 flex items-start gap-3">
                <i class="bi bi-bookmark text-[10px] text-slate-400 w-3 text-center flex-shrink-0 mt-0.5"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0 mt-0.5">Labels</span>
                <div class="flex-1 flex flex-wrap gap-1.5">
                    @foreach($task->labels as $label)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold text-white"
                          style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- SPRINT --}}
            @if($task->sprint)
            <div class="px-4 py-2.5 flex items-center gap-3">
                <i class="bi bi-speedometer2 text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Sprint</span>
                <a href="{{ route('projects.sprints.show', [$task->project, $task->sprint]) }}"
                   class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline truncate flex-1">
                    {{ $task->sprint->name }}
                </a>
            </div>
            @endif

            {{-- ESTIMATES --}}
            @if($task->estimated_hours || $task->story_points)
            <div class="px-4 py-2.5 flex items-center gap-3">
                <i class="bi bi-clock text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Estimates</span>
                <div class="flex gap-3">
                    @if($task->estimated_hours)<span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $task->estimated_hours }}h</span>@endif
                    @if($task->story_points)<span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $task->story_points }} pts</span>@endif
                </div>
            </div>
            @endif

            {{-- Project row --}}
            <div class="px-4 py-2.5 flex items-center gap-3">
                <i class="bi bi-folder2-open text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Project</span>
                <a href="{{ route('projects.show', $task->project) }}"
                   class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline truncate flex-1">
                    {{ $task->project->name }}
                </a>
            </div>
        </div>{{-- end Details card --}}

        {{-- ── TIME TRACKER ── --}}
        <div class="card overflow-hidden"
             x-data="{
                 running: false,
                 entryId: null,
                 elapsed: '00:00:00',
                 elapsedSec: 0,
                 startedAt: null,
                 _tick: null,
                 totalLogged: @js($task->timeEntries?->sum('duration_minutes') ?? 0),
                 entries: @js(($task->timeEntries ?? collect())->map(fn($e)=>['id'=>$e->id,'started'=>$e->started_at?->format('M j, g:ia'),'duration'=>$e->duration_formatted ?? ($e->duration_minutes ? floor($e->duration_minutes/60).'h '.($e->duration_minutes%60).'m' : '—'),'user'=>$e->user?->name ?? ''])->values()),
                 init() {
                     fetch('/time-entries/running',{headers:{'Accept':'application/json'}})
                         .then(r=>r.ok?r.json():null)
                         .then(d=>{
                             if(d?.entry?.task_id=={{ $taskId }}){
                                 this.running=true; this.entryId=d.entry.id;
                                 this.startedAt=new Date(d.entry.started_at);
                                 this._tick=setInterval(()=>this._tick_fn(),1000);
                             }
                         }).catch(()=>{});
                 },
                 _tick_fn() {
                     this.elapsedSec=Math.floor((Date.now()-this.startedAt)/1000);
                     const h=Math.floor(this.elapsedSec/3600), m=Math.floor((this.elapsedSec%3600)/60), s=this.elapsedSec%60;
                     this.elapsed=[h,m,s].map(n=>String(n).padStart(2,'0')).join(':');
                 },
                 async toggle() {
                     const tok=document.querySelector('meta[name=csrf-token]').content;
                     if(this.running) {
                         const r=await fetch('/time-entries/'+this.entryId+'/stop',{method:'POST',headers:{'X-CSRF-TOKEN':tok,'Accept':'application/json'}});
                         if(r.ok){
                             this.running=false; clearInterval(this._tick); this._tick=null;
                             const mins=Math.round(this.elapsedSec/60);
                             this.totalLogged+=mins;
                             this.entries.unshift({id:this.entryId,started:'just now',duration:(Math.floor(mins/60)>0?Math.floor(mins/60)+'h ':'')+(mins%60)+'m',user:'{{ auth()->user()->name }}'});
                             this.elapsed='00:00:00'; this.elapsedSec=0; this.entryId=null;
                         }
                     } else {
                         const r=await fetch('/time-entries/start',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok,'Accept':'application/json'},body:JSON.stringify({task_id:{{ $taskId }}})});
                         if(r.ok){
                             const d=await r.json();
                             this.running=true; this.entryId=d.entry.id;
                             this.startedAt=new Date(d.entry.started_at);
                             this._tick=setInterval(()=>this._tick_fn(),1000);
                         }
                     }
                 },
                 get totalFormatted() {
                     const h=Math.floor(this.totalLogged/60), m=this.totalLogged%60;
                     return (h>0?h+'h ':'')+(m>0?m+'m':'0m');
                 }
             }">

            {{-- Header row: label + live clock --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                    <i class="bi bi-stopwatch text-violet-500"></i> Time Tracker
                </span>
                <div class="flex items-center gap-2">
                    <span x-show="running" x-cloak
                          class="font-mono text-base font-bold tabular-nums tracking-wider text-rose-500"
                          x-text="elapsed"></span>
                    <span x-show="!running"
                          class="font-mono text-base font-bold tabular-nums tracking-wider text-slate-400">00:00:00</span>
                    {{-- Pulsing dot when running --}}
                    <span x-show="running" x-cloak class="w-2 h-2 rounded-full bg-rose-500 animate-pulse flex-shrink-0"></span>
                </div>
            </div>

            {{-- Start / Stop button --}}
            <div class="p-4">
                <button @click="toggle()"
                        class="w-full flex items-center justify-center gap-2.5 py-3 rounded-xl text-sm font-bold transition-all"
                        :class="running
                            ? 'bg-rose-500 hover:bg-rose-600 text-white'
                            : 'bg-violet-600 hover:bg-violet-700 text-white'">
                    <i class="bi text-base" :class="running ? 'bi-stop-circle-fill' : 'bi-play-circle-fill'"></i>
                    <span x-text="running ? 'Stop Tracking' : 'Start Tracking'"></span>
                </button>

                {{-- Total logged --}}
                <div class="flex items-center gap-2 mt-3 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                    <i class="bi bi-clock-history text-xs text-violet-500"></i>
                    <span class="text-xs text-slate-500">Total logged</span>
                    <span class="ml-auto text-xs font-bold text-slate-900 dark:text-white" x-text="totalFormatted"></span>
                </div>
            </div>

            {{-- Recent sessions --}}
            <div class="border-t border-slate-100 dark:border-slate-800">
                <div class="px-4 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Sessions</span>
                </div>
                <template x-if="entries.length === 0">
                    <div class="px-4 pb-4 text-center">
                        <p class="text-xs text-slate-400">No sessions yet</p>
                    </div>
                </template>
                <div class="divide-y divide-slate-50 dark:divide-slate-800/60 max-h-36 overflow-y-auto">
                    <template x-for="(e, i) in entries" :key="e.id ?? i">
                        <div class="flex items-center gap-2.5 px-4 py-2">
                            <div class="w-5 h-5 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-play-fill text-violet-500 text-[8px]"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300" x-text="e.duration"></p>
                                <p class="text-[10px] text-slate-400 truncate" x-text="(e.user ? e.user + ' · ' : '') + e.started"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>{{-- end Time Tracker card --}}

        {{-- Actions --}}
        @can('delete', $task)
        <button onclick="if(confirm('Delete this task? This cannot be undone.')) document.getElementById('delete-form').submit()"
                class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-white bg-rose-500 hover:bg-rose-600 dark:bg-rose-600 dark:hover:bg-rose-700 transition-all">
            <i class="bi bi-trash3 text-sm"></i> Delete task
        </button>
        <form id="delete-form" method="POST" action="{{ route('tasks.destroy', $task) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
        @endcan

    </div>
</div>

@push('scripts')
@php
$attachmentsJson = $task->attachments->map(fn($a) => [
    'id'=>$a->id,'original_name'=>$a->original_name,'mime_type'=>$a->mime_type,
    'size_formatted'=>$a->size_formatted,'is_image'=>$a->is_image,'is_video'=>$a->is_video,
    'url'=>$a->url,'uploader_name'=>$a->user->name,'created_ago'=>$a->created_at->diffForHumans(),
]);
@endphp
<script>
function attachmentManager(uploadUrl, destroyUrlTemplate) {
    return {
        files: @json($attachmentsJson),
        uploading: false, uploadProgress: 0, uploadStatus: '', dragging: false,
        validate(file) {
            const maxBytes = 100 * 1024 * 1024;
            const blocked = ['exe','bat','sh','cmd','msi','dmg','app','bin','jar','vbs','ps1'];
            const ext = file.name.split('.').pop().toLowerCase();
            if (blocked.includes(ext)) { window.toast?.(`${file.name}: file type not allowed`, 'error'); return false; }
            if (file.size > maxBytes) { window.toast?.(`${file.name}: exceeds 100 MB limit`, 'error'); return false; }
            return true;
        },
        async upload(fileList) {
            if (!fileList?.length) return;
            this.uploading = true; this.uploadProgress = 0;
            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                if (!this.validate(file)) continue;
                this.uploadStatus = `Uploading ${file.name}… (${i+1}/${fileList.length})`;
                const form = new FormData();
                form.append('file', file);
                form.append('_token', document.querySelector('meta[name=csrf-token]').content);
                try {
                    const res = await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', uploadUrl);
                        xhr.upload.onprogress = e => { if (e.lengthComputable) this.uploadProgress = Math.round(e.loaded/e.total*100); };
                        xhr.onload = () => xhr.status >= 200 && xhr.status < 300 ? resolve(JSON.parse(xhr.responseText)) : reject();
                        xhr.onerror = reject;
                        xhr.send(form);
                    });
                    const att = res.attachment;
                    this.files.push({id:att.id,original_name:att.original_name,mime_type:att.mime_type,size_formatted:att.size_formatted,is_image:att.is_image,is_video:att.is_video,url:res.url,uploader_name:att.user?.name??'You',created_ago:'just now'});
                    window.toast?.(`${file.name} uploaded`, 'success');
                } catch { window.toast?.(`Failed to upload ${file.name}`, 'error'); }
            }
            this.uploading = false; this.uploadProgress = 0;
        },
        async remove(id) {
            if (!confirm('Delete this attachment?')) return;
            const res = await fetch(destroyUrlTemplate.replace(':id', id), {method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}});
            if (res.ok) { this.files = this.files.filter(f => f.id !== id); window.toast?.('Deleted', 'success'); }
        }
    };
}
</script>
@endpush
@endsection
