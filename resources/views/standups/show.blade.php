@extends('layouts.app')
@section('title', 'My Standup — ' . $day->format('F j, Y'))

@section('content')
<script>
function standupBoard(userId, dateStr, existingItems, existingBlockers, alreadySubmitted, projects) {
    let nextId = existingItems.length;

    return {
        items:          existingItems.map((item, i) => ({ ...item, _id: i })),
        blockers:       existingBlockers || '',
        creatingIn:     null,
        newTaskTitle:   '',
        newTaskProject: '',
        taskCreating:   false,
        projects:       projects || [],
        saving:         false,
        saved:          false,
        submitted:      alreadySubmitted,
        justSubmitted:  false,
        dragging:       null,
        dragOverStatus: null,

        itemsFor(status) {
            return this.items.filter(i => i.status === status);
        },

        startCreate() {
            this.creatingIn     = 'todo';
            this.newTaskTitle   = '';
            this.newTaskProject = '';
        },

        async createTask() {
            if (!this.newTaskTitle.trim() || !this.newTaskProject) return;
            this.taskCreating = true;
            try {
                const res = await fetch('/tasks/quick-store', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                    body: JSON.stringify({
                        title:        this.newTaskTitle.trim(),
                        project_id:   this.newTaskProject,
                        type:         'task',
                        priority:     'medium',
                        assignee_ids: [userId],
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    const label = (data.ticket_key ? `[${data.ticket_key}] ` : '') + data.title;
                    this.items.push({ _id: nextId++, task_id: data.task_id, description: label, status: 'todo' });
                    this.creatingIn     = null;
                    this.newTaskTitle   = '';
                    this.newTaskProject = '';
                    this.autoSave();
                }
            } finally {
                this.taskCreating = false;
            }
        },

        removeItem(id) {
            this.items = this.items.filter(i => i._id !== id);
            this.autoSave();
        },

        // ── Drag & Drop ──────────────────────────────────────────────────
        dragStart(event, itemId) {
            this.dragging = itemId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(itemId));
        },

        dragOver(status) {
            this.dragOverStatus = status;
        },

        drop(newStatus) {
            const itemId = this.dragging;
            this.dragOverStatus = null;
            this.dragging = null;
            if (itemId === null || itemId === undefined) return;
            this.moveItem(itemId, newStatus);
        },

        moveItem(itemId, newStatus) {
            const item = this.items.find(i => i._id === itemId);
            if (!item || item.status === newStatus) return;
            item.status = newStatus;
            if (item.task_id) {
                this.syncTaskStatus(item.task_id, newStatus);
            }
            this.autoSave();
        },

        async syncTaskStatus(taskId, status) {
            try {
                await fetch(`/tasks/${taskId}/status`, {
                    method:  'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                    body: JSON.stringify({ status }),
                });
            } catch (e) { /* silent */ }
        },

        async autoSave() { await this.save(); },

        // ── Task detail panel ────────────────────────────────────────────
        panelOpen:    false,
        panel:        null,
        panelLoading: false,

        ticketKey(description) {
            const m = String(description).match(/^\[([A-Z]+-\d+)\]/);
            return m ? m[1] : null;
        },

        taskTitle(description) {
            return String(description).replace(/^\[[A-Z]+-\d+\]\s*/, '');
        },

        async openPanel(taskId) {
            if (!taskId) return;
            this.panelOpen    = true;
            this.panelLoading = true;
            this.panel        = null;
            try {
                const res = await fetch(`/tasks/${taskId}/panel-data`, {
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) this.panel = await res.json();
            } finally {
                this.panelLoading = false;
            }
        },

        closePanel() {
            this.panelOpen = false;
            this.panel     = null;
        },

        async patchPanel(field, value) {
            if (!this.panel?.task?.id) return;
            const id = this.panel.task.id;
            const res = await fetch(`/tasks/${id}`, {
                method:  'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                },
                body: JSON.stringify({ [field]: value }),
            });
            if (res.ok) {
                this.panel.task[field] = value;
                // Reflect priority/status change back onto the standup card
                if (field === 'status') {
                    const item = this.items.find(i => i.task_id === id);
                    if (item) { item.status = value; this.autoSave(); }
                }
            }
        },

        async submitStandup() {
            await this.save();
            this.submitted     = true;
            this.justSubmitted = true;
            setTimeout(() => { this.justSubmitted = false; }, 5000);
        },

        async save() {
            this.saving = true;
            this.saved  = false;
            try {
                await fetch('/standups', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                    body: JSON.stringify({
                        standup_date: dateStr,
                        blockers:     this.blockers || null,
                        items: this.items
                            .filter(i => i.description.trim())
                            .map(i => ({
                                description: i.description.trim(),
                                status:      i.status,
                                task_id:     i.task_id || null,
                            })),
                    }),
                });
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 3000);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@php
$columns = [
    'todo'        => ['label' => 'To Do',       'icon' => 'bi-circle-fill',       'color' => 'text-slate-400',  'dotBg' => 'bg-slate-400',  'activeTxt' => 'text-slate-600 dark:text-slate-300',  'activeBg' => 'bg-slate-50 dark:bg-slate-800/60',   'border' => 'border-slate-200 dark:border-slate-700',   'ring' => 'ring-slate-300'],
    'in_progress' => ['label' => 'In Progress',  'icon' => 'bi-arrow-repeat',       'color' => 'text-blue-400',   'dotBg' => 'bg-blue-500',   'activeTxt' => 'text-blue-600 dark:text-blue-400',    'activeBg' => 'bg-blue-50/50 dark:bg-blue-900/10',   'border' => 'border-blue-200 dark:border-blue-800/50',  'ring' => 'ring-blue-300'],
    'in_review'   => ['label' => 'In Review',    'icon' => 'bi-eye-fill',           'color' => 'text-amber-400',  'dotBg' => 'bg-amber-400',  'activeTxt' => 'text-amber-600 dark:text-amber-400',  'activeBg' => 'bg-amber-50/50 dark:bg-amber-900/10', 'border' => 'border-amber-200 dark:border-amber-800/50','ring' => 'ring-amber-300'],
    'done'        => ['label' => 'Done',         'icon' => 'bi-check-circle-fill',  'color' => 'text-green-400',  'dotBg' => 'bg-green-500',  'activeTxt' => 'text-green-600 dark:text-green-400',  'activeBg' => 'bg-green-50/50 dark:bg-green-900/10', 'border' => 'border-green-200 dark:border-green-800/50','ring' => 'ring-green-300'],
];

$alreadySubmitted = $standup !== null;
$existingBlockers = $standup?->blockers ?? '';
@endphp

<script>
window.__standupInit = function() {
    return standupBoard(
        {{ $user->id }},
        '{{ $day->toDateString() }}',
        @json($seedItems),
        @json($existingBlockers),
        {{ $alreadySubmitted ? 'true' : 'false' }},
        @json($projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name]))
    );
};
</script>
<div x-data="__standupInit()"
     class="flex flex-col gap-4">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('standups.index') }}"
               class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center
                      text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center
                        font-bold text-sm bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <h1 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                    {{ $user->name }}
                    <span class="text-slate-400 font-normal text-sm">· {{ $user->job_title ?? $user->department ?? '' }}</span>
                </h1>
                <p class="text-xs text-slate-500">{{ $day->format('l, F j') }}</p>
            </div>
        </div>

        <p class="hidden lg:flex items-center gap-1.5 text-xs text-slate-400">
            <i class="bi bi-info-circle text-violet-400"></i>
            Drag tasks between lanes — changes sync to each project's board instantly.
        </p>

        <div class="flex items-center gap-2 flex-shrink-0">
            <span x-show="saving" x-cloak class="text-xs text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrow-repeat animate-spin text-xs"></i> Saving…
            </span>
            <span x-show="saved && !submitted" x-cloak x-transition
                  class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <i class="bi bi-check-circle-fill text-xs"></i> Saved
            </span>

            <button @click="submitStandup()"
                    :disabled="saving || submitted"
                    :class="submitted
                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-700 cursor-default'
                        : 'bg-violet-600 hover:bg-violet-700 text-white border-violet-600 hover:border-violet-700'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-semibold
                           transition-all duration-200 disabled:opacity-70">
                <i :class="submitted ? 'bi-check-circle-fill' : 'bi-send-fill'" class="bi text-xs"></i>
                <span x-text="submitted ? 'Submitted ✓' : 'Submit Standup'"></span>
            </button>
        </div>
    </div>

    {{-- ── Success banner ──────────────────────────────────────────────── --}}
    <template x-if="submitted && justSubmitted">
        <div x-transition class="flex items-center gap-3 px-4 py-3 rounded-xl
                                  bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
            <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0"></i>
            <p class="text-sm font-medium text-green-700 dark:text-green-300">
                Standup submitted for {{ $day->format('l, F j') }}. Nice work!
            </p>
            <button @click="justSubmitted = false"
                    class="ml-auto text-green-400 hover:text-green-600 flex-shrink-0">
                <i class="bi bi-x text-sm"></i>
            </button>
        </div>
    </template>

    {{-- ── Blockers banner (existing) ──────────────────────────────────── --}}
    <template x-if="blockers && !justSubmitted">
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl
                    bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
            <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed" x-text="blockers"></p>
        </div>
    </template>

    {{-- ── Kanban columns ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-start">

        @foreach($columns as $status => $col)
        <div class="rounded-2xl {{ $col['activeBg'] }} border {{ $col['border'] }} flex flex-col min-h-[180px] transition-all duration-150"
             @dragover.prevent="dragOver('{{ $status }}')"
             @dragleave.self="dragOverStatus = null"
             @drop.prevent="drop('{{ $status }}')"
             :class="dragOverStatus === '{{ $status }}' ? 'ring-2 ring-inset {{ $col['ring'] }}' : ''">

            {{-- Column header --}}
            <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                <i class="bi {{ $col['icon'] }} text-xs {{ $col['color'] }}"></i>
                <span class="text-xs font-bold {{ $col['color'] }}">{{ $col['label'] }}</span>
                <span class="ml-auto text-xs font-bold {{ $col['color'] }} opacity-60 tabular-nums"
                      x-text="itemsFor('{{ $status }}').length || ''"></span>
            </div>

            {{-- Task cards --}}
            <div class="flex flex-col gap-1.5 px-2 flex-1">
                <template x-for="item in itemsFor('{{ $status }}')" :key="item._id">
                    <div draggable="true"
                         @dragstart="dragStart($event, item._id)"
                         @dragend="dragging = null; dragOverStatus = null"
                         @click="!dragging && item.task_id && openPanel(item.task_id)"
                         :class="[
                             dragging === item._id ? 'opacity-40 cursor-grabbing' : (item.task_id ? 'cursor-pointer' : 'cursor-grab')
                         ]"
                         class="group bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm
                                border border-slate-100 dark:border-slate-700/60
                                flex flex-col gap-1 select-none transition-shadow hover:shadow-md">
                        {{-- Ticket key badge --}}
                        <template x-if="ticketKey(item.description)">
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-medium">
                                <i class="bi bi-check2-square text-violet-400 text-xs"></i>
                                <span x-text="ticketKey(item.description)"></span>
                            </div>
                        </template>
                        <div class="flex items-start gap-2">
                            <p class="flex-1 text-sm text-slate-700 dark:text-slate-200 leading-snug break-words"
                               x-text="item.task_id ? taskTitle(item.description) : item.description"></p>
                            <button @click.stop="removeItem(item._id)"
                                    class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity
                                           text-slate-300 hover:text-rose-500 mt-0.5">
                                <i class="bi bi-x text-sm"></i>
                            </button>
                        </div>
                        <template x-if="item.task_id">
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="text-[10px] text-violet-400 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                                    <i class="bi bi-box-arrow-up-right text-[9px]"></i> View details
                                </span>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Drop target placeholder when dragging over --}}
                <div x-show="dragOverStatus === '{{ $status }}' && dragging !== null"
                     x-cloak
                     class="rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-600
                            h-12 flex items-center justify-center">
                    <i class="bi bi-arrow-down text-violet-400 text-sm"></i>
                </div>
            </div>

            {{-- Create Task form — only in To-Do column --}}
            @if($status === 'todo')
            <template x-if="creatingIn === 'todo'">
                <div class="mx-2 mb-1 bg-white dark:bg-slate-800 rounded-xl px-3 py-3
                            border border-violet-300 dark:border-violet-600 shadow-sm space-y-2">
                    <p class="text-[11px] font-semibold text-violet-600 dark:text-violet-400 uppercase tracking-wide">
                        <i class="bi bi-plus-square-fill mr-1"></i>New Task
                    </p>
                    <input type="text"
                           x-model="newTaskTitle"
                           x-effect="creatingIn === 'todo' && $nextTick(() => $el.focus())"
                           @keydown.enter.prevent="createTask()"
                           @keydown.escape="creatingIn = null; newTaskTitle = ''; newTaskProject = ''"
                           placeholder="Task title…"
                           class="w-full text-sm bg-slate-50 dark:bg-slate-700/50 rounded-lg px-3 py-1.5
                                  border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200
                                  placeholder-slate-400 outline-none focus:border-violet-400 transition-colors" />
                    <select x-model="newTaskProject"
                            class="w-full text-sm bg-slate-50 dark:bg-slate-700/50 rounded-lg px-3 py-1.5
                                   border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200
                                   outline-none focus:border-violet-400 transition-colors">
                        <option value="">Select project…</option>
                        <template x-for="p in projects" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                    <div class="flex items-center justify-between pt-1">
                        <button @click="creatingIn = null; newTaskTitle = ''; newTaskProject = ''"
                                class="text-xs text-slate-400 hover:text-slate-600 transition-colors">Cancel</button>
                        <button @click="createTask()"
                                :disabled="!newTaskTitle.trim() || !newTaskProject || taskCreating"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                                       bg-violet-600 hover:bg-violet-700 text-white transition-colors
                                       disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="bi" :class="taskCreating ? 'bi-arrow-repeat animate-spin' : 'bi-plus-lg'"></i>
                            <span x-text="taskCreating ? 'Creating…' : 'Create & add'"></span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Add Task button — only in To-Do, hidden while create form is open --}}
            @if($projects->count() > 0)
            <div x-show="creatingIn !== 'todo'" class="px-2 pb-2 mt-1">
                <button @click="startCreate()"
                        class="flex items-center gap-1.5 w-full px-2.5 py-2 rounded-xl
                               border border-dashed border-violet-200 dark:border-violet-800/50
                               text-xs text-violet-400 dark:text-violet-500
                               hover:border-violet-400 hover:text-violet-600 dark:hover:border-violet-600 dark:hover:text-violet-300
                               transition-all">
                    <i class="bi bi-plus-square text-xs"></i>
                    Add task
                </button>
            </div>
            @endif
            @endif

        </div>
        @endforeach

    </div>

    {{-- ── Blockers ─────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4">
        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
            <i class="bi bi-exclamation-triangle text-amber-500"></i>
            Blockers
            <span class="font-normal normal-case tracking-normal text-slate-400">(optional — anything blocking your progress?)</span>
        </label>
        <textarea x-model="blockers"
                  rows="2"
                  placeholder="e.g. Waiting for API access, blocked on design review…"
                  class="form-input w-full resize-none text-sm bg-white dark:bg-slate-800"></textarea>
    </div>

    {{-- ── Bottom submit bar ───────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 pt-2">
        <p class="text-xs text-slate-400">
            @if($alreadySubmitted)
            Last updated today · changes auto-save
            @else
            Drag tasks between columns to update status. Hit Submit when you're ready.
            @endif
        </p>
        <button @click="submitStandup()"
                :disabled="saving || submitted"
                :class="submitted
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-700 cursor-default'
                    : 'bg-violet-600 hover:bg-violet-700 text-white border-violet-600 hover:border-violet-700'"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border
                       text-sm font-semibold transition-all duration-200 disabled:opacity-70">
            <i :class="submitted ? 'bi-check-circle-fill' : 'bi-send-fill'" class="bi text-xs"></i>
            <span x-text="submitted ? 'Submitted ✓' : 'Submit Standup'"></span>
        </button>
    </div>

    {{-- ── Backdrop (z-40, below sidebar z-50 so nav stays visible) ───── --}}
    <template x-teleport="body">
        <div x-show="panelOpen" x-cloak @click="closePanel()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/20 backdrop-blur-[2px]" style="display:none; z-index:40"></div>
    </template>

    {{-- ── Task Detail Panel ───────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="panelOpen" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="fixed right-0 top-0 h-dvh w-full max-w-sm sm:max-w-md md:max-w-[440px]
                    bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-700
                    shadow-2xl flex flex-col overflow-hidden" style="display:none; z-index:300">

            <template x-if="panelLoading">
                <div class="flex-1 flex items-center justify-center">
                    <i class="bi bi-arrow-repeat animate-spin text-slate-400 text-2xl"></i>
                </div>
            </template>

            <template x-if="panel && !panelLoading">
                <div class="flex flex-col h-full overflow-hidden">

                    {{-- Header --}}
                    <div class="flex items-center gap-2 px-5 py-3.5 border-b border-slate-100 dark:border-slate-700/60 flex-shrink-0">
                        <div class="flex-1 min-w-0 flex items-center gap-2 text-xs">
                            <i class="bi bi-check2-square text-violet-500 text-sm flex-shrink-0"></i>
                            <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="panel.task.ticket_key"></span>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span class="text-slate-500 dark:text-slate-400 truncate" x-text="panel.task.project_name"></span>
                        </div>
                        <div class="flex items-center gap-0.5 flex-shrink-0">
                            <a :href="panel.task.show_url" target="_blank" title="Open"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                                <i class="bi bi-box-arrow-up-right text-sm"></i>
                            </a>
                            <a :href="panel.task.edit_url" title="Edit"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <button @click="closePanel()" title="Close"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Scrollable body --}}
                    <div class="flex-1 overflow-y-auto">

                        {{-- Title --}}
                        <div class="px-5 pt-4 pb-3.5 border-b border-slate-100 dark:border-slate-700/60">
                            <div x-text="panel.task.title" contenteditable="true"
                                 @blur="patchPanel('title', $el.textContent.trim())" @keydown.enter.prevent="$el.blur()"
                                 class="text-lg font-semibold text-slate-900 dark:text-white leading-snug outline-none rounded-lg
                                        px-2 py-1 -mx-2 cursor-text min-h-[1.5em]
                                        hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-slate-50 dark:focus:bg-slate-800 transition-colors"></div>
                            <p class="text-xs text-slate-400 mt-1 ml-0.5">Click to edit title</p>
                        </div>

                        {{-- Fields --}}
                        <div class="px-5 py-4 space-y-3.5 border-b border-slate-100 dark:border-slate-700/60">
                            <div class="grid grid-cols-2 gap-x-3 gap-y-3.5">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Type</p>
                                    <select @change="patchPanel('type', $event.target.value)"
                                            class="w-full text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                        <template x-for="t in ['task','bug','feature','story','epic']" :key="t">
                                            <option :value="t" :selected="panel.task.type === t" x-text="t.charAt(0).toUpperCase()+t.slice(1)"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Priority</p>
                                    <select @change="patchPanel('priority', $event.target.value)"
                                            class="w-full text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                        <template x-for="p in ['low','medium','high','critical']" :key="p">
                                            <option :value="p" :selected="panel.task.priority === p" x-text="p.charAt(0).toUpperCase()+p.slice(1)"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Column / Stage</p>
                                    <select @change="patchPanel('column_id', parseInt($event.target.value))"
                                            class="w-full text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                        <template x-for="col in panel.columns" :key="col.id">
                                            <option :value="col.id" :selected="panel.task.column_id === col.id" x-text="col.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Due Date</p>
                                    <input type="date" :value="panel.task.due_date || ''"
                                           @change="patchPanel('due_date', $event.target.value || null)"
                                           class="w-full text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer" />
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Estimate (Hours)</p>
                                <input type="number" min="0" step="0.5" :value="panel.task.estimated_hours ?? 0"
                                       @change="patchPanel('estimated_hours', parseFloat($event.target.value) || 0)"
                                       class="w-28 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors" />
                            </div>
                        </div>

                        {{-- Assignees --}}
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Assignees</p>
                            <div class="flex flex-wrap gap-1.5 mb-2.5" x-show="panel.task.assignee_ids.length > 0">
                                <template x-for="m in panel.members.filter(m => panel.task.assignee_ids.includes(m.id))" :key="'chip-'+m.id">
                                    <span class="inline-flex items-center gap-1 pl-1.5 pr-1 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/40 border border-violet-200 dark:border-violet-700 text-xs font-medium text-violet-700 dark:text-violet-300">
                                        <span x-text="m.name.split(' ')[0]"></span>
                                        <button @click="const ids=panel.task.assignee_ids.filter(id=>id!==m.id); panel.task.assignee_ids=ids; patchPanel('assignee_ids',ids);"
                                                class="w-4 h-4 rounded-full flex items-center justify-center text-violet-400 hover:text-violet-700 hover:bg-violet-200 dark:hover:bg-violet-800 transition-colors leading-none">&times;</button>
                                    </span>
                                </template>
                            </div>
                            <div class="grid grid-cols-2 gap-0.5">
                                <template x-for="m in panel.members" :key="'member-'+m.id">
                                    <button @click="const ids=panel.task.assignee_ids.includes(m.id)?panel.task.assignee_ids.filter(id=>id!==m.id):[...panel.task.assignee_ids,m.id]; panel.task.assignee_ids=ids; patchPanel('assignee_ids',ids);"
                                            :class="panel.task.assignee_ids.includes(m.id)?'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-medium':'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                            class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm transition-colors text-left">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-600 flex-shrink-0 flex items-center justify-center text-[9px] font-bold text-slate-600 dark:text-slate-200"
                                             x-text="m.name.substring(0,2).toUpperCase()"></div>
                                        <span class="truncate text-sm" x-text="m.name.split(' ')[0]"></span>
                                        <i x-show="panel.task.assignee_ids.includes(m.id)" class="bi bi-check2 text-violet-500 ml-auto text-xs flex-shrink-0"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Labels --}}
                        <template x-if="panel.labels && panel.labels.length">
                            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Labels</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="lbl in panel.labels" :key="lbl.id">
                                        <button @click="const ids=panel.task.label_ids.includes(lbl.id)?panel.task.label_ids.filter(id=>id!==lbl.id):[...panel.task.label_ids,lbl.id]; panel.task.label_ids=ids; patchPanel('label_ids',ids);"
                                                :style="`border-color:${lbl.color}; color:${lbl.color}; background:${panel.task.label_ids.includes(lbl.id)?lbl.color+'22':'transparent'}`"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-xs font-medium transition-all"
                                                x-text="lbl.name"></button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Attachments --}}
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Attachments
                                    <span x-show="panel.attachments.length > 0" x-text="'('+panel.attachments.length+')'"></span>
                                </p>
                                <label class="inline-flex items-center gap-1 text-xs font-medium text-violet-600 dark:text-violet-400 hover:text-violet-800 cursor-pointer">
                                    <i class="bi bi-plus-lg text-xs"></i> Add
                                    <input type="file" class="hidden" multiple>
                                </label>
                            </div>
                            <template x-if="panel.attachments.length === 0">
                                <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl py-5 flex flex-col items-center gap-1.5 text-center">
                                    <i class="bi bi-cloud-upload text-2xl text-slate-300 dark:text-slate-600"></i>
                                    <p class="text-xs text-slate-400">Drop or <span class="text-violet-500 cursor-pointer hover:underline">browse</span></p>
                                    <p class="text-[10px] text-slate-300 dark:text-slate-600">No files attached</p>
                                </div>
                            </template>
                            <template x-if="panel.attachments.length > 0">
                                <div class="space-y-1">
                                    <template x-for="att in panel.attachments" :key="att.id">
                                        <a :href="att.url" target="_blank"
                                           class="flex items-center gap-2 text-xs text-violet-600 dark:text-violet-400 hover:underline py-0.5">
                                            <i class="bi bi-paperclip text-xs text-slate-400"></i>
                                            <span x-text="att.original_name"></span>
                                            <span class="text-slate-400 ml-auto" x-text="att.size_formatted"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Description --}}
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Description</p>
                            <textarea rows="4" placeholder="Add a description…"
                                      @blur="patchPanel('description', $el.value.trim())"
                                      x-init="$el.value = panel.task.description || ''"
                                      class="w-full text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                                             rounded-xl px-3 py-2.5 text-slate-700 dark:text-slate-200 outline-none
                                             focus:border-violet-400 transition-colors resize-none leading-relaxed
                                             placeholder-slate-300 dark:placeholder-slate-600"></textarea>
                        </div>

                    </div>{{-- /scrollable body --}}

                    {{-- Footer --}}
                    <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 dark:border-slate-700/60 flex-shrink-0 bg-slate-50/60 dark:bg-slate-800/40">
                        <a :href="panel.task.edit_url"
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-200 transition-colors">
                            <i class="bi bi-pencil-square text-xs"></i> Full edit
                        </a>
                        <button @click="closePanel()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Close
                        </button>
                    </div>

                </div>
            </template>
        </div>
    </template>

</div>

@endsection
