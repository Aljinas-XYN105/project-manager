@extends('layouts.app')
@section('title', $user->name . ' — Standup')

@section('content')
<div class="page-header">
    <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('standups.index') }}"
           class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center
                  hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors flex-shrink-0">
            <i class="bi bi-arrow-left text-sm text-slate-500"></i>
        </a>
        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
             class="w-10 h-10 rounded-full object-cover ring-2 ring-white dark:ring-slate-800 flex-shrink-0">
        <div class="min-w-0">
            <h1 class="page-title truncate">{{ $user->name }}</h1>
            <p class="text-xs text-slate-400">{{ $user->job_title }} · {{ now()->format('l, F j') }}</p>
        </div>
    </div>
    <p class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400 flex-shrink-0">
        <i class="bi bi-info-circle"></i>
        Drag tasks between lanes — changes sync to each project's board instantly.
    </p>
</div>

@php
$laneColors = [
    'To Do'       => '#94a3b8',
    'In Progress' => '#3b82f6',
    'In Review'   => '#f59e0b',
    'QA Testing'  => '#8b5cf6',
    'Done'        => '#10b981',
];
@endphp

{{-- Drag-and-drop standup board --}}
<div x-data="standupBoard()"
     class="flex gap-3 overflow-x-auto pb-6 -mx-4 lg:-mx-6 px-4 lg:px-6 min-h-[calc(100vh-14rem)] items-start">

    @foreach($laneOrder as $lane)
    @php
        $tasks  = $lanes[$lane];
        $color  = $laneColors[$lane];
    @endphp

    <div class="flex flex-col flex-shrink-0 w-[290px] rounded-2xl
                bg-slate-100/70 dark:bg-slate-800/50 backdrop-blur-sm
                border border-slate-200/60 dark:border-slate-700/50 overflow-hidden"
         @dragover.prevent="dragEnter('{{ $lane }}')"
         @drop.prevent="drop('{{ $lane }}')"
         :class="{ 'ring-2 ring-inset ring-violet-400': dragOver === '{{ $lane }}' }">

        {{-- Lane header --}}
        <div class="flex items-center justify-between px-3 py-3 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $color }}"></div>
                <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ $lane }}</span>
                <span class="min-w-[20px] h-5 bg-white dark:bg-slate-700 rounded-full text-xs font-semibold
                             text-slate-500 flex items-center justify-center px-1.5 shadow-sm"
                      x-text="counts['{{ $lane }}']">
                    {{ $tasks->count() }}
                </span>
            </div>
        </div>

        {{-- Task cards --}}
        <div class="flex-1 overflow-y-auto px-2.5 pb-2.5 space-y-2 min-h-[60px]"
             id="lane-{{ Str::slug($lane) }}">

            @foreach($tasks as $task)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700/80
                        shadow-sm cursor-pointer p-3
                        hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700/60
                        hover:-translate-y-px transition-all duration-150"
                 draggable="true"
                 data-task-id="{{ $task->id }}"
                 data-lane="{{ $lane }}"
                 @click="$dispatch('open-task-panel', { taskId: {{ $task->id }} })"
                 @dragstart="dragStart({{ $task->id }}, '{{ $lane }}'); $el.classList.add('opacity-40','rotate-1')"
                 @dragend="$el.classList.remove('opacity-40','rotate-1')">

                {{-- Project badge + type --}}
                <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium
                                 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400
                                 px-1.5 py-px rounded-md truncate max-w-[120px]">
                        <i class="bi bi-folder2 text-[9px]"></i>
                        {{ $task->project->name }}
                    </span>
                    @if($task->ticket_key)
                    <span class="font-mono text-[9px] text-slate-400">{{ $task->ticket_key }}</span>
                    @endif
                    <i class="bi {{ $task->type_icon }} text-xs {{ $task->type_color }}"></i>
                </div>

                <p class="font-medium text-sm text-slate-900 dark:text-white leading-snug mb-2">
                    {{ $task->title }}
                </p>

                {{-- Labels --}}
                @if($task->labels->isNotEmpty())
                <div class="flex flex-wrap gap-1 mb-2">
                    @foreach($task->labels as $label)
                    <span class="px-1.5 py-px rounded-full text-[9px] font-semibold text-white"
                          style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                </div>
                @endif

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="badge-{{ $task->priority }} py-0 text-[9px]">{{ ucfirst($task->priority) }}</span>
                        @if($task->due_date)
                        <span class="text-[10px] {{ $task->is_overdue ? 'text-rose-500 font-medium' : 'text-slate-400' }}">
                            <i class="bi bi-calendar3 mr-0.5"></i>{{ $task->due_date->format('M j') }}
                        </span>
                        @endif
                    </div>
                    <div class="flex -space-x-1.5">
                        @foreach($task->assignees->take(3) as $a)
                        <img src="{{ $a->avatar_url }}" title="{{ $a->name }}"
                             class="w-5 h-5 rounded-full border border-white dark:border-slate-900 object-cover">
                        @endforeach
                        @if($task->assignees->count() > 3)
                        <div class="w-5 h-5 rounded-full border border-white dark:border-slate-900
                                    bg-slate-200 dark:bg-slate-700 text-[8px] font-bold
                                    text-slate-500 flex items-center justify-center">
                            +{{ $task->assignees->count() - 3 }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Drop placeholder --}}
            <div x-show="dragOver === '{{ $lane }}'" x-cloak
                 class="h-14 rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-700
                        bg-violet-50 dark:bg-violet-900/10 flex items-center justify-center">
                <span class="text-xs text-violet-400">Move here</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Toast for move confirmations --}}
<div x-data="{ msg: '', show: false }"
     @standup-moved.window="msg = $event.detail; show = true; setTimeout(() => show = false, 2500)"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 dark:bg-white
            text-white dark:text-slate-900 text-sm font-medium px-4 py-2.5 rounded-xl shadow-xl pointer-events-none">
    <i class="bi bi-check-circle-fill text-emerald-400 mr-2"></i>
    <span x-text="msg"></span>
</div>

<script>
function standupBoard() {
    return {
        dragOver: null,
        dragging: { taskId: null, fromLane: null },
        counts: @js(collect($laneOrder)->mapWithKeys(fn($l) => [$l => $lanes[$l]->count()])),

        dragStart(taskId, fromLane) {
            this.dragging = { taskId, fromLane };
        },
        dragEnter(lane) {
            this.dragOver = lane;
        },
        drop(toLane) {
            const { taskId, fromLane } = this.dragging;
            this.dragOver = null;

            if (!taskId || fromLane === toLane) return;

            // Optimistically move the card in the DOM
            const card = document.querySelector(`[data-task-id="${taskId}"]`);
            const targetList = document.getElementById('lane-' + toLane.toLowerCase().replace(/\s+/g, '-'));
            if (card && targetList) {
                card.dataset.lane = toLane;
                targetList.insertBefore(card, targetList.firstChild);
                this.counts[fromLane] = Math.max(0, this.counts[fromLane] - 1);
                this.counts[toLane]   = (this.counts[toLane] || 0) + 1;
            }

            // Persist to server
            axios.patch(`/tasks/${taskId}/standup-move`, { lane: toLane })
                .then(() => {
                    window.dispatchEvent(new CustomEvent('standup-moved', {
                        detail: `Moved to ${toLane}`
                    }));
                })
                .catch(() => {
                    // Revert on failure
                    if (card) {
                        const origList = document.getElementById('lane-' + fromLane.toLowerCase().replace(/\s+/g, '-'));
                        if (origList) origList.insertBefore(card, origList.firstChild);
                        this.counts[toLane]   = Math.max(0, this.counts[toLane] - 1);
                        this.counts[fromLane] = (this.counts[fromLane] || 0) + 1;
                    }
                    window.toast?.('Failed to move task', 'error');
                });

            this.dragging = { taskId: null, fromLane: null };
        },
    };
}
</script>
@endsection
