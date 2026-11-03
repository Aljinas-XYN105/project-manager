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
        Drag cards between tabs to sync with each project's board.
    </p>
</div>

@php
$laneColors = [
    'To Do'       => ['dot' => '#94a3b8', 'tab' => 'slate',  'badge' => 'bg-slate-500'],
    'In Progress' => ['dot' => '#3b82f6', 'tab' => 'blue',   'badge' => 'bg-blue-500'],
    'In Review'   => ['dot' => '#f59e0b', 'tab' => 'amber',  'badge' => 'bg-amber-500'],
    'QA Testing'  => ['dot' => '#8b5cf6', 'tab' => 'violet', 'badge' => 'bg-violet-500'],
    'Done'        => ['dot' => '#10b981', 'tab' => 'emerald','badge' => 'bg-emerald-500'],
];
@endphp

<div x-data="standupBoard()" class="flex flex-col gap-0">

    {{-- ─── Tab navigation ─── --}}
    <div class="flex items-center gap-1 overflow-x-auto pb-0.5 mb-5 -mx-1 px-1
                scrollbar-hide sm:gap-2">
        @foreach($laneOrder as $lane)
        @php
            $count = $lanes[$lane]->count();
            $meta  = $laneColors[$lane];
        @endphp
        <button type="button"
                @click="activeTab = '{{ $lane }}'"
                @dragover.prevent="dragOverTab('{{ $lane }}')"
                class="relative flex items-center gap-2 px-3.5 py-2.5 rounded-xl
                       text-sm font-semibold whitespace-nowrap flex-shrink-0
                       transition-all duration-150 outline-none
                       focus-visible:ring-2 focus-visible:ring-violet-400"
                :class="activeTab === '{{ $lane }}'
                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200 dark:border-slate-700'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-white/70 dark:hover:bg-slate-800/50 border border-transparent'">
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $meta['dot'] }}"></span>
            {{ $lane }}
            <span class="ml-0.5 min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold flex items-center justify-center transition-colors"
                  :class="activeTab === '{{ $lane }}'
                      ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
                  x-text="counts['{{ $lane }}']">{{ $count }}</span>
            {{-- drop indicator ring --}}
            <span x-show="tabDragOver === '{{ $lane }}'" x-cloak
                  class="absolute inset-0 rounded-xl ring-2 ring-violet-400 pointer-events-none"></span>
        </button>
        @endforeach
    </div>

    {{-- ─── Active lane panel ─── --}}
    @foreach($laneOrder as $lane)
    @php
        $tasks  = $lanes[$lane];
        $meta   = $laneColors[$lane];
    @endphp
    <div x-show="activeTab === '{{ $lane }}'"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         @dragover.prevent="dragEnterLane('{{ $lane }}')"
         @drop.prevent="drop('{{ $lane }}')"
         class="w-full">

        @if($tasks->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-slate-400
                    rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700
                    bg-slate-50 dark:bg-slate-800/30"
             :class="{ 'border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-900/10': dragOver === '{{ $lane }}' }">
            <i class="bi bi-inbox text-3xl mb-2"></i>
            <p class="text-sm font-medium">No tasks in {{ $lane }}</p>
            <p class="text-xs mt-1">Drag a card here to move it into this stage.</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
             id="lane-{{ Str::slug($lane) }}"
             :class="{ 'ring-2 ring-inset ring-violet-400 rounded-2xl': dragOver === '{{ $lane }}' }">

            @foreach($tasks as $task)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700/80
                        shadow-sm cursor-pointer p-3.5
                        hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700/60
                        hover:-translate-y-px transition-all duration-150"
                 draggable="true"
                 data-task-id="{{ $task->id }}"
                 data-lane="{{ $lane }}"
                 @click="$dispatch('open-task-panel', { taskId: {{ $task->id }} })"
                 @dragstart="dragStart({{ $task->id }}, '{{ $lane }}'); $el.classList.add('opacity-40','scale-95')"
                 @dragend="$el.classList.remove('opacity-40','scale-95')">

                {{-- Project + ticket --}}
                <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium
                                 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400
                                 px-1.5 py-px rounded-md truncate max-w-[140px]">
                        <i class="bi bi-folder2 text-[9px]"></i>
                        {{ $task->project->name }}
                    </span>
                    @if($task->ticket_key)
                    <span class="font-mono text-[9px] text-slate-400">{{ $task->ticket_key }}</span>
                    @endif
                    <i class="bi {{ $task->type_icon }} text-xs {{ $task->type_color }} ml-auto"></i>
                </div>

                {{-- Title --}}
                <p class="font-semibold text-sm text-slate-900 dark:text-white leading-snug mb-2.5">
                    {{ $task->title }}
                </p>

                {{-- Labels --}}
                @if($task->labels->isNotEmpty())
                <div class="flex flex-wrap gap-1 mb-2.5">
                    @foreach($task->labels as $label)
                    <span class="px-1.5 py-px rounded-full text-[9px] font-semibold text-white"
                          style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                </div>
                @endif

                {{-- Footer --}}
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="badge-{{ $task->priority }} py-0 text-[9px] flex-shrink-0">{{ ucfirst($task->priority) }}</span>
                        @if($task->due_date)
                        <span class="text-[10px] flex-shrink-0 {{ $task->is_overdue ? 'text-rose-500 font-medium' : 'text-slate-400' }}">
                            <i class="bi bi-calendar3 mr-0.5"></i>{{ $task->due_date->format('M j') }}
                        </span>
                        @endif
                    </div>
                    <div class="flex -space-x-1.5 flex-shrink-0">
                        @foreach($task->assignees->take(3) as $a)
                        <img src="{{ $a->avatar_url }}" title="{{ $a->name }}"
                             class="w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 object-cover">
                        @endforeach
                        @if($task->assignees->count() > 3)
                        <div class="w-5 h-5 rounded-full border-2 border-white dark:border-slate-900
                                    bg-slate-200 dark:bg-slate-700 text-[8px] font-bold
                                    text-slate-500 flex items-center justify-center">
                            +{{ $task->assignees->count() - 3 }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

        </div>
        @endif
    </div>
    @endforeach

</div>

{{-- Toast --}}
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
        activeTab:   '{{ $laneOrder[0] }}',
        dragOver:    null,
        tabDragOver: null,
        dragging:    { taskId: null, fromLane: null },
        counts: @js(collect($laneOrder)->mapWithKeys(fn($l) => [$l => $lanes[$l]->count()])),
        _tabTimer: null,

        dragStart(taskId, fromLane) {
            this.dragging = { taskId, fromLane };
        },

        dragEnterLane(lane) {
            this.dragOver = lane;
        },

        dragOverTab(lane) {
            this.tabDragOver = lane;
            clearTimeout(this._tabTimer);
            // Auto-switch tab after hovering for 600ms so user can drop
            this._tabTimer = setTimeout(() => {
                if (this.tabDragOver === lane) this.activeTab = lane;
            }, 600);
        },

        drop(toLane) {
            const { taskId, fromLane } = this.dragging;
            this.dragOver    = null;
            this.tabDragOver = null;
            clearTimeout(this._tabTimer);

            if (!taskId || fromLane === toLane) return;

            // Update counts optimistically
            this.counts[fromLane] = Math.max(0, this.counts[fromLane] - 1);
            this.counts[toLane]   = (this.counts[toLane] || 0) + 1;

            // Move card in DOM to new lane's container
            const card       = document.querySelector(`[data-task-id="${taskId}"]`);
            const targetGrid = document.getElementById('lane-' + toLane.toLowerCase().replace(/\s+/g, '-'));
            if (card && targetGrid) {
                card.dataset.lane = toLane;
                targetGrid.prepend(card);
            }

            // Switch to the destination tab
            this.activeTab = toLane;

            axios.patch(`/tasks/${taskId}/standup-move`, { lane: toLane })
                .then(() => {
                    window.dispatchEvent(new CustomEvent('standup-moved', {
                        detail: `Moved to ${toLane}`
                    }));
                })
                .catch(() => {
                    // Revert counts and move card back
                    this.counts[toLane]   = Math.max(0, this.counts[toLane] - 1);
                    this.counts[fromLane] = (this.counts[fromLane] || 0) + 1;
                    if (card) {
                        const origGrid = document.getElementById('lane-' + fromLane.toLowerCase().replace(/\s+/g, '-'));
                        if (origGrid) origGrid.prepend(card);
                        card.dataset.lane = fromLane;
                    }
                    this.activeTab = fromLane;
                    window.toast?.('Failed to move task', 'error');
                });

            this.dragging = { taskId: null, fromLane: null };
        },
    };
}
</script>
@endsection
