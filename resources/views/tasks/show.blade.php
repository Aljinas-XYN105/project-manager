@extends('layouts.app')
@section('title', $task->title)

@section('content')
{{-- Breadcrumb + header --}}
<div class="page-header">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5 flex-wrap">
            <a href="{{ route('projects.show', $task->project) }}" class="hover:text-violet-600 transition-colors">{{ $task->project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ route('projects.tasks.index', $task->project) }}" class="hover:text-violet-600 transition-colors">Tasks</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="font-mono text-[11px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md text-slate-500">{{ $task->ticket_key }}</span>
        </div>
        <div class="flex items-start gap-3 flex-wrap">
            {{-- Type icon badge --}}
            <span class="mt-1 w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0
                @if($task->type==='bug') bg-rose-100 dark:bg-rose-900/30
                @elseif($task->type==='feature') bg-purple-100 dark:bg-purple-900/30
                @elseif($task->type==='story') bg-blue-100 dark:bg-blue-900/30
                @elseif($task->type==='epic') bg-orange-100 dark:bg-orange-900/30
                @else bg-slate-100 dark:bg-slate-800 @endif">
                <i class="bi {{ $task->type_icon }} {{ $task->type_color }} text-sm"></i>
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ $task->title }}</h1>
                <div class="flex items-center gap-3 mt-1 flex-wrap text-xs text-slate-400">
                    <span class="capitalize">{{ $task->type }}</span>
                    @if($task->sprint)
                    <a href="{{ route('projects.sprints.show', [$task->project, $task->sprint]) }}"
                       class="inline-flex items-center gap-1 text-violet-500 dark:text-violet-400 hover:underline">
                        <i class="bi bi-speedometer2"></i> {{ $task->sprint->name }}
                    </a>
                    @endif
                    <span>by <strong class="text-slate-600 dark:text-slate-300">{{ $task->creator->name }}</strong></span>
                    <span>{{ $task->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        @if($task->status !== 'done')
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="inline">
            @csrf @method('PUT')
            <input type="hidden" name="status" value="done">
            <button type="submit" class="btn-secondary btn-sm text-emerald-600 dark:text-emerald-400 border-emerald-300 dark:border-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                <i class="bi bi-check-lg"></i> Mark Done
            </button>
        </form>
        @endif
        @can('update', $task)
        <a href="{{ route('tasks.edit', $task) }}" class="btn-secondary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        @endcan
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-5">
    {{-- ── MAIN CONTENT ── --}}
    <div class="space-y-4 min-w-0">

        {{-- Description --}}
        <div class="card p-5">
            <h3 class="section-heading">Description</h3>
            @if($task->description)
            <div class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                {!! nl2br(e($task->description)) !!}
            </div>
            @else
            <p class="text-sm text-slate-400 italic">No description — <a href="{{ route('tasks.edit', $task) }}" class="text-violet-500 hover:underline">add one</a></p>
            @endif
        </div>

        {{-- Bug Report Details — always shown for bugs --}}
        @if($task->type === 'bug')
        <div class="card p-5 border-l-4 border-l-rose-400">
            <h3 class="font-semibold text-rose-700 dark:text-rose-400 mb-4 flex items-center gap-2">
                <i class="bi bi-bug-fill"></i> Bug Report
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                {{-- Severity --}}
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Severity</span>
                    @if($task->severity)
                    @php
                    $sevBg = ['blocker'=>'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 ring-rose-200',
                              'critical'=>'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 ring-orange-200',
                              'major'=>'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 ring-amber-200',
                              'minor'=>'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400 ring-sky-200',
                              'trivial'=>'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 ring-slate-200'];
                    $sevIcon = ['blocker'=>'🔴','critical'=>'🟠','major'=>'🟡','minor'=>'🟢','trivial'=>'⚪'];
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold capitalize ring-1 w-fit
                                 {{ $sevBg[$task->severity] ?? $sevBg['trivial'] }}">
                        {{ $sevIcon[$task->severity] ?? '' }} {{ ucfirst($task->severity) }}
                    </span>
                    @else
                    <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-slate-400 hover:text-violet-500 italic">Not set — add severity</a>
                    @endif
                </div>
                {{-- Environment --}}
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Environment</span>
                    @if($task->environment ?? false)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 w-fit">
                        <i class="bi bi-display text-slate-400 text-xs"></i> {{ $task->environment }}
                    </span>
                    @else
                    <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-slate-400 hover:text-violet-500 italic">Not set — add environment</a>
                    @endif
                </div>
            </div>

            {{-- Steps to reproduce --}}
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Steps to Reproduce</p>
                @if($task->steps_to_reproduce ?? false)
                <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-4 text-sm text-slate-700 dark:text-slate-300
                            whitespace-pre-wrap border border-slate-200 dark:border-slate-700 leading-relaxed font-mono text-xs">{{ $task->steps_to_reproduce }}</div>
                @else
                <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl p-4 border border-dashed border-slate-200 dark:border-slate-700 text-center">
                    <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-slate-400 hover:text-violet-500">
                        <i class="bi bi-plus-circle mr-1"></i> Add reproduction steps
                    </a>
                </div>
                @endif
            </div>

            {{-- Expected vs Actual --}}
            @if(($task->expected_behavior ?? false) || ($task->actual_behavior ?? false))
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-1.5 flex items-center gap-1.5 uppercase tracking-wide">
                        <i class="bi bi-check-circle-fill"></i> Expected
                    </p>
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-xl p-3.5 text-sm text-slate-700 dark:text-slate-300
                                border border-emerald-200 dark:border-emerald-900 leading-relaxed">
                        {{ $task->expected_behavior ?? '—' }}
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mb-1.5 flex items-center gap-1.5 uppercase tracking-wide">
                        <i class="bi bi-x-circle-fill"></i> Actual
                    </p>
                    <div class="bg-rose-50 dark:bg-rose-900/10 rounded-xl p-3.5 text-sm text-slate-700 dark:text-slate-300
                                border border-rose-200 dark:border-rose-900 leading-relaxed">
                        {{ $task->actual_behavior ?? '—' }}
                    </div>
                </div>
            </div>
            @else
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('tasks.edit', $task) }}" class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 text-sm text-slate-400 hover:text-violet-500 hover:border-violet-300 transition-colors">
                    <i class="bi bi-plus-circle text-xs"></i> Add expected behavior
                </a>
                <a href="{{ route('tasks.edit', $task) }}" class="flex items-center gap-2 p-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 text-sm text-slate-400 hover:text-rose-500 hover:border-rose-300 transition-colors">
                    <i class="bi bi-plus-circle text-xs"></i> Add actual behavior
                </a>
            </div>
            @endif
        </div>
        @endif

        {{-- Subtasks --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-diagram-2 text-slate-400 text-sm"></i>
                    Subtasks
                    @if($task->subtasks->count())
                    @php $subDone = $task->subtasks->where('status','done')->count(); @endphp
                    <span class="text-xs font-normal text-slate-400">{{ $subDone }}/{{ $task->subtasks->count() }}</span>
                    @endif
                </h3>
                @can('update', $task)
                <a href="{{ route('tasks.create', ['project_id' => $task->project->id]) }}?parent_id={{ $task->id }}"
                   class="btn-ghost btn-sm text-xs">
                    <i class="bi bi-plus-lg"></i> Add subtask
                </a>
                @endcan
            </div>
            @if($task->subtasks->count())
            @php
            $pct = $task->subtasks->count() > 0
                ? round($task->subtasks->where('status','done')->count() / $task->subtasks->count() * 100)
                : 0;
            @endphp
            <div class="px-5 pt-3 pb-1">
                <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                    <div class="h-1.5 bg-violet-400 rounded-full transition-all" style="width: {{ $pct }}%"></div>
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
            <div class="px-5 py-6 text-center">
                <p class="text-sm text-slate-400">No subtasks yet</p>
            </div>
            @endif
        </div>

        {{-- Comments --}}
        <div class="card overflow-hidden" id="comments">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-chat text-slate-400 text-sm"></i>
                    Comments
                    @if($task->comments->count())
                    <span class="text-xs font-normal text-slate-400">({{ $task->comments->count() }})</span>
                    @endif
                </h3>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($task->comments as $comment)
                <div class="p-5 flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0 text-sm font-bold text-violet-600 dark:text-violet-400">
                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="font-semibold text-sm text-slate-900 dark:text-white">{{ $comment->user->name }}</span>
                            <span class="text-xs text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{{ $comment->body }}</p>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center">
                    <i class="bi bi-chat text-2xl text-slate-200 dark:text-slate-700 block mb-2"></i>
                    <p class="text-sm text-slate-400">No comments yet</p>
                </div>
                @endforelse
            </div>
            {{-- Comment input --}}
            <div class="p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20"
                 x-data="{ body: '', submitting: false }">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0 text-sm font-bold text-violet-600 dark:text-violet-400">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <textarea x-model="body" rows="2" placeholder="Add a comment… (Shift+Enter for new line)"
                                  class="form-input resize-none w-full text-sm"
                                  @keydown.enter.meta.prevent="$el.closest('[x-data]').__x.$data.submitting || (async () => {
                                      if (!body.trim()) return; submitting=true;
                                      await fetch('/comments',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},body:JSON.stringify({body,commentable_type:'task',commentable_id:{{ $task->id }}})});
                                      body='';submitting=false;window.location.reload();
                                  })()"></textarea>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-[11px] text-slate-400">⌘+Enter to submit</span>
                            <button @click="
                                if (!body.trim() || submitting) return; submitting=true;
                                fetch('/comments',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},body:JSON.stringify({body,commentable_type:'task',commentable_id:{{ $task->id }}})})
                                    .then(()=>{body='';submitting=false;window.location.reload()})
                                    .catch(()=>submitting=false)"
                                :disabled="!body.trim() || submitting"
                                class="btn-primary btn-sm disabled:opacity-40">
                                <i class="bi" :class="submitting ? 'bi-hourglass-split animate-spin' : 'bi-send'"></i>
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
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-paperclip text-slate-400 text-sm"></i>
                    Attachments
                    <span class="text-xs font-normal text-slate-400" x-text="'(' + files.length + ')'"></span>
                </h3>
                <label class="btn-ghost btn-sm cursor-pointer" title="Upload file">
                    <i class="bi bi-plus-lg text-sm"></i> Add
                    <input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                </label>
            </div>

            {{-- Drop zone --}}
            <div class="px-5 py-3 border-b border-dashed border-slate-200 dark:border-slate-700 transition-colors"
                 :class="dragging ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-400' : ''"
                 @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; upload($event.dataTransfer.files)">
                <div class="flex items-center justify-center gap-2 py-2 text-center">
                    <i class="bi bi-cloud-upload text-slate-300 dark:text-slate-600 text-xl" :class="dragging ? 'text-violet-400' : ''"></i>
                    <p class="text-xs text-slate-400" :class="dragging ? 'text-violet-500' : ''">
                        Drop files here or <label class="text-violet-500 hover:text-violet-600 cursor-pointer font-medium">
                            browse<input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                        </label>
                        <span class="text-slate-300 dark:text-slate-600"> · max 10 MB per file</span>
                    </p>
                </div>
            </div>

            {{-- Upload progress --}}
            <template x-if="uploading">
                <div class="px-5 py-3 flex items-center gap-3 border-b border-slate-50 dark:border-slate-800 bg-violet-50/50 dark:bg-violet-900/10">
                    <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-arrow-up text-violet-500 text-sm animate-pulse"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-violet-700 dark:text-violet-300" x-text="uploadStatus"></p>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1 mt-1.5">
                            <div class="bg-violet-500 h-1 rounded-full transition-all duration-300"
                                 :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- File list --}}
            <div class="divide-y divide-slate-50 dark:divide-slate-800/60">
                <template x-if="files.length === 0 && !uploading">
                    <div class="px-5 py-6 text-center">
                        <p class="text-xs text-slate-400">No attachments yet</p>
                    </div>
                </template>
                <template x-for="att in files" :key="att.id">
                    <div class="flex items-center gap-3 px-5 py-3 group hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="att.is_image
                                 ? 'bg-sky-100 dark:bg-sky-900/30'
                                 : att.mime_type && att.mime_type.includes('pdf')
                                 ? 'bg-rose-100 dark:bg-rose-900/30'
                                 : 'bg-slate-100 dark:bg-slate-800'">
                            <i class="text-sm"
                               :class="att.is_image
                                   ? 'bi bi-image text-sky-500'
                                   : att.mime_type && att.mime_type.includes('pdf')
                                   ? 'bi bi-file-earmark-pdf text-rose-500'
                                   : att.mime_type && att.mime_type.includes('word')
                                   ? 'bi bi-file-earmark-word text-blue-500'
                                   : att.mime_type && (att.mime_type.includes('sheet') || att.mime_type.includes('excel'))
                                   ? 'bi bi-file-earmark-excel text-emerald-500'
                                   : att.mime_type && att.mime_type.includes('zip')
                                   ? 'bi bi-file-earmark-zip text-amber-500'
                                   : 'bi bi-file-earmark text-slate-400'"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate" x-text="att.original_name"></p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <span x-text="att.size_formatted"></span> ·
                                <span x-text="att.uploader_name"></span> ·
                                <span x-text="att.created_ago"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a :href="att.url" target="_blank"
                               class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-slate-500"
                               title="Download">
                                <i class="bi bi-download text-sm"></i>
                            </a>
                            <button @click="remove(att.id)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-colors text-slate-400 hover:text-rose-500"
                                    title="Delete">
                                <i class="bi bi-trash3 text-sm"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Activity Log --}}
        @if(isset($activityLogs) && $activityLogs->count())
        <div class="card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-clock-history text-slate-400 text-sm"></i> Activity
                </h3>
            </div>
            <div class="p-5 space-y-3">
                @foreach($activityLogs as $log)
                <div class="flex gap-3">
                    <div class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="bi bi-circle-fill text-[6px] text-slate-400"></i>
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

    {{-- ── SIDEBAR ── --}}
    <div class="space-y-3">

        {{-- Status + key fields --}}
        <div class="card p-4 space-y-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Properties</p>

            {{-- Status (inline dropdown) --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 block mb-1">Status</label>
                <select onchange="fetch('/tasks/{{ $task->id }}/status',{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},body:JSON.stringify({status:this.value})})"
                        class="form-select text-sm py-1.5">
                    @foreach(['todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Done','cancelled'=>'Cancelled'] as $v=>$l)
                    <option value="{{ $v }}" {{ $task->status===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Priority --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 block mb-1">Priority</label>
                <span class="badge-{{ $task->priority }} capitalize">{{ ucfirst($task->priority) }}</span>
            </div>

            {{-- Type --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 block mb-1">Type</label>
                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium capitalize">
                    <i class="bi {{ $task->type_icon }} {{ $task->type_color }}"></i>{{ ucfirst($task->type) }}
                </span>
            </div>

            {{-- Due Date --}}
            <div>
                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 block mb-1">Due Date</label>
                @if($task->due_date)
                <p class="text-sm font-medium {{ $task->is_overdue ? 'text-rose-500' : 'text-slate-900 dark:text-white' }}">
                    <i class="bi bi-calendar3 mr-1 text-slate-400"></i>{{ $task->due_date->format('M j, Y') }}
                    @if($task->is_overdue)<span class="text-xs font-semibold text-rose-500 ml-1">Overdue</span>@endif
                </p>
                @else
                <a href="{{ route('tasks.edit', $task) }}" class="text-xs text-slate-400 hover:text-violet-500 italic">Set due date</a>
                @endif
            </div>

            {{-- Estimates --}}
            @if($task->estimated_hours || $task->story_points)
            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                @if($task->estimated_hours)
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 block mb-1">Estimated</label>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white"><i class="bi bi-clock text-slate-400 mr-1"></i>{{ $task->estimated_hours }}h</p>
                </div>
                @endif
                @if($task->story_points)
                <div>
                    <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 block mb-1">Points</label>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white"><i class="bi bi-lightning text-slate-400 mr-1"></i>{{ $task->story_points }}</p>
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Assignees (interactive) --}}
        <div class="card p-4"
             x-data="{
                 assignees: {{ Js::from($task->assignees->map(fn($a) => ['id'=>$a->id,'name'=>$a->name,'avatar'=>$a->avatar_url])) }},
                 available: {{ Js::from($members->whereNotIn('id', $task->assignees->pluck('id'))->values()->map(fn($m) => ['id'=>$m->id,'name'=>$m->name,'avatar'=>$m->avatar_url])) }},
                 open: false,
                 canEdit: {{ auth()->user()->can('update', $task) ? 'true' : 'false' }},
                 addUrl: '{{ route('tasks.assignees.add', $task) }}',
                 removeBase: '{{ url('tasks/'.$task->id.'/assignees') }}',
                 csrfToken: '{{ csrf_token() }}',
                 async add(user) {
                     this.open = false;
                     const r = await fetch(this.addUrl, {
                         method: 'POST',
                         headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken},
                         body: JSON.stringify({user_id: user.id})
                     });
                     if (r.ok) {
                         this.assignees.push(user);
                         this.available = this.available.filter(u => u.id !== user.id);
                     }
                 },
                 async remove(user) {
                     const r = await fetch(this.removeBase+'/'+user.id, {
                         method: 'DELETE',
                         headers: {'X-CSRF-TOKEN':this.csrfToken}
                     });
                     if (r.ok) {
                         this.assignees = this.assignees.filter(u => u.id !== user.id);
                         this.available.push(user);
                         this.available.sort((a,b) => a.name.localeCompare(b.name));
                     }
                 }
             }">
            <div class="flex items-center justify-between mb-2.5">
                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Assignees</label>
                @can('update', $task)
                <div class="relative" x-show="available.length > 0 || assignees.length > 0">
                    <button @click="open = !open"
                            class="text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-1">
                        <i class="bi bi-person-plus text-sm"></i> Add
                    </button>
                    <div x-show="open" x-cloak @click.away="open = false"
                         class="absolute right-0 top-6 z-30 w-52 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-1.5 max-h-48 overflow-y-auto">
                        <template x-if="available.length === 0">
                            <p class="text-xs text-slate-400 text-center py-3">All members assigned</p>
                        </template>
                        <template x-for="u in available" :key="u.id">
                            <button @click="add(u)"
                                    class="flex items-center gap-2.5 w-full px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <img :src="u.avatar" class="w-6 h-6 rounded-full flex-shrink-0">
                                <span class="text-sm text-slate-700 dark:text-slate-300 truncate" x-text="u.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
                @endcan
            </div>
            <div class="space-y-2">
                <template x-if="assignees.length === 0">
                    <p class="text-xs text-slate-400 italic">No one assigned</p>
                </template>
                <template x-for="a in assignees" :key="a.id">
                    <div class="flex items-center gap-2.5 group">
                        <img :src="a.avatar" class="w-7 h-7 rounded-full flex-shrink-0">
                        <span class="text-sm text-slate-700 dark:text-slate-300 flex-1 truncate" x-text="a.name"></span>
                        @can('update', $task)
                        <button @click="remove(a)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-slate-300 hover:text-rose-500 flex-shrink-0"
                                title="Remove">
                            <i class="bi bi-x-circle text-sm"></i>
                        </button>
                        @endcan
                    </div>
                </template>
            </div>
        </div>

        {{-- Labels --}}
        @if($task->labels->count())
        <div class="card p-4">
            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-2.5">Labels</label>
            <div class="flex flex-wrap gap-1.5">
                @foreach($task->labels as $label)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium text-white"
                      style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Time Tracker --}}
        <div class="card p-4"
             x-data="{ running: false, entryId: null, elapsed: '00:00:00', startedAt: null, _tick: null }"
             x-init="
                 fetch('/time-entries/running', {headers:{'Accept':'application/json'}})
                     .then(r=>r.ok?r.json():null)
                     .then(d => {
                         if (d?.entry?.task_id == {{ $task->id }}) {
                             running=true; entryId=d.entry.id;
                             startedAt=new Date(d.entry.started_at);
                             _tick=setInterval(()=>{
                                 const s=Math.floor((Date.now()-startedAt)/1000);
                                 elapsed=[Math.floor(s/3600),Math.floor((s%3600)/60),s%60].map(n=>String(n).padStart(2,'0')).join(':');
                             },1000);
                         }
                     }).catch(()=>{});">
            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-3">Time Tracker</label>
            <div class="flex items-center gap-3">
                <button @click="
                    const tok=document.querySelector('meta[name=csrf-token]').content;
                    if (running) {
                        fetch('/time-entries/'+entryId+'/stop',{method:'POST',headers:{'X-CSRF-TOKEN':tok,'Accept':'application/json'}})
                            .then(()=>{running=false;clearInterval(_tick);elapsed='00:00:00';entryId=null;});
                    } else {
                        fetch('/time-entries/start',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok,'Accept':'application/json'},body:JSON.stringify({task_id:{{ $task->id }}})})
                            .then(r=>r.json()).then(d=>{
                                running=true;entryId=d.entry.id;startedAt=new Date(d.entry.started_at);
                                _tick=setInterval(()=>{const s=Math.floor((Date.now()-startedAt)/1000);elapsed=[Math.floor(s/3600),Math.floor((s%3600)/60),s%60].map(n=>String(n).padStart(2,'0')).join(':');},1000);
                            });
                    }"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                    :class="running
                        ? 'bg-rose-500 hover:bg-rose-600 text-white'
                        : 'bg-violet-600 hover:bg-violet-700 text-white'">
                    <i class="bi text-xs" :class="running ? 'bi-stop-fill' : 'bi-play-fill'"></i>
                    <span x-text="running ? 'Stop' : 'Start'"></span>
                </button>
                <span x-show="running" x-text="elapsed" x-cloak
                      class="font-mono text-base font-bold text-slate-900 dark:text-white tabular-nums tracking-widest"></span>
                <span x-show="!running" class="text-xs text-slate-400">Not tracking</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card p-4">
            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-2.5">Quick Actions</label>
            <div class="space-y-0.5">
                <a href="{{ route('tasks.edit', $task) }}" class="flex items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 px-2 py-2 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                    <i class="bi bi-pencil w-4 text-center text-slate-400 text-xs"></i> Edit task
                </a>
                <a href="{{ route('tasks.create', ['project_id' => $task->project->id]) }}?parent_id={{ $task->id }}"
                   class="flex items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 px-2 py-2 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                    <i class="bi bi-plus-circle w-4 text-center text-slate-400 text-xs"></i> Add subtask
                </a>
                @php $activeSprint = $task->project->activeSprint(); @endphp
                @if($activeSprint)
                <button onclick="fetch('/projects/{{ $task->project->id }}/sprints/{{ $activeSprint->id }}/add-task',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},body:JSON.stringify({task_id:{{ $task->id }}})}).then(()=>window.location.reload())"
                        class="flex items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 px-2 py-2 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors w-full text-left">
                    <i class="bi bi-speedometer2 w-4 text-center text-slate-400 text-xs"></i> Add to active sprint
                </button>
                @endif
                <a href="#comments" class="flex items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 px-2 py-2 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                    <i class="bi bi-chat w-4 text-center text-slate-400 text-xs"></i> Jump to comments
                </a>
                @can('delete', $task)
                <button onclick="if(confirm('Delete this task?')) document.getElementById('delete-form').submit()"
                        class="flex items-center gap-2.5 text-sm text-rose-500 hover:text-rose-600 px-2 py-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors w-full text-left">
                    <i class="bi bi-trash w-4 text-center text-xs"></i> Delete task
                </button>
                <form id="delete-form" method="POST" action="{{ route('tasks.destroy', $task) }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
                @endcan
            </div>
        </div>

        {{-- Project link --}}
        <a href="{{ route('projects.show', $task->project) }}"
           class="flex items-center gap-2.5 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700
                  hover:border-violet-300 dark:hover:border-violet-700 hover:shadow-sm transition-all group">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background-color: {{ $task->project->color ?? '#3B82F6' }}20">
                <i class="bi bi-folder2-open text-sm" style="color: {{ $task->project->color ?? '#3B82F6' }}"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">{{ $task->project->name }}</p>
                <p class="text-[10px] text-slate-400">View project</p>
            </div>
            <i class="bi bi-chevron-right text-xs text-slate-400 group-hover:text-violet-500 transition-colors"></i>
        </a>
    </div>
</div>
@push('scripts')
@php
$attachmentsJson = $task->attachments->map(function($a) {
    return [
        'id'             => $a->id,
        'original_name'  => $a->original_name,
        'mime_type'      => $a->mime_type,
        'size_formatted' => $a->size_formatted,
        'is_image'       => $a->is_image,
        'url'            => $a->url,
        'uploader_name'  => $a->user->name,
        'created_ago'    => $a->created_at->diffForHumans(),
    ];
});
@endphp
<script>
function attachmentManager(uploadUrl, destroyUrlTemplate) {
    return {
        files: @json($attachmentsJson),
        uploading: false,
        uploadProgress: 0,
        uploadStatus: '',
        dragging: false,

        async upload(fileList) {
            if (!fileList || !fileList.length) return;
            this.uploading = true;
            this.uploadProgress = 0;

            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                this.uploadStatus = `Uploading ${file.name}… (${i + 1}/${fileList.length})`;

                const form = new FormData();
                form.append('file', file);
                form.append('_token', document.querySelector('meta[name=csrf-token]').content);

                try {
                    const res = await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', uploadUrl);
                        xhr.upload.onprogress = e => {
                            if (e.lengthComputable) {
                                this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                            }
                        };
                        xhr.onload = () => {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                resolve(JSON.parse(xhr.responseText));
                            } else {
                                reject(new Error(xhr.responseText));
                            }
                        };
                        xhr.onerror = () => reject(new Error('Network error'));
                        xhr.send(form);
                    });

                    const att = res.attachment;
                    this.files.push({
                        id:             att.id,
                        original_name:  att.original_name,
                        mime_type:      att.mime_type,
                        size_formatted: att.size_formatted,
                        is_image:       att.is_image,
                        url:            res.url,
                        uploader_name:  att.user?.name ?? 'You',
                        created_ago:    'just now',
                    });
                    window.toast(`${file.name} uploaded`, 'success');
                } catch (err) {
                    window.toast(`Failed to upload ${file.name}`, 'error');
                }
            }

            this.uploading = false;
            this.uploadProgress = 0;
        },

        async remove(id) {
            if (!confirm('Delete this attachment?')) return;
            const url = destroyUrlTemplate.replace(':id', id);
            const token = document.querySelector('meta[name=csrf-token]').content;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                });
                if (res.ok) {
                    this.files = this.files.filter(f => f.id !== id);
                    window.toast('Attachment deleted', 'success');
                }
            } catch {
                window.toast('Could not delete attachment', 'error');
            }
        }
    };
}
</script>
@endpush
@endsection
