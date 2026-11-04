@extends('layouts.app')
@section('title', 'My Standup — ' . $day->format('F j, Y'))

@section('content')
<script>
function standupBoard(userId, dateStr, existingItems, existingBlockers, alreadySubmitted, projects, members) {
    let nextId = existingItems.length;

    return {
        items:          existingItems.map((item, i) => ({ ...item, _id: i })),
        blockers:       existingBlockers || '',
        newTaskTitle:   '',
        newTaskProject: '',
        taskCreating:   false,
        projects:       projects || [],
        members:        members || [],
        saving:         false,
        saved:          false,
        submitted:      alreadySubmitted,
        justSubmitted:  false,
        dragging:       null,
        dragOverStatus: null,

        // ── Add task modal ────────────────────────────────────────────────
        addTaskModal:       false,
        addTaskModalTab:    'existing',   // 'existing' | 'create'
        searchQuery:        '',
        searchResults:      [],
        searching:          false,
        searchTimer:        null,
        showAssigneePicker: false,

        // create-new form fields
        newTaskType:        'task',
        newTaskPriority:    'medium',
        newTaskDueDate:     '',
        newTaskEstimate:    '',
        newTaskDescription: '',
        newTaskAssignees:   [],   // array of user ids

        itemsFor(status) {
            return this.items.filter(i => i.status === status);
        },

        openAddTaskModal(tab = 'existing') {
            this.addTaskModal       = true;
            this.addTaskModalTab    = tab;
            this.searchQuery        = '';
            this.searchResults      = [];
            this.newTaskTitle       = '';
            this.newTaskProject     = '';
            this.newTaskType        = 'task';
            this.newTaskPriority    = 'medium';
            this.newTaskDueDate     = '';
            this.newTaskEstimate    = '';
            this.newTaskDescription = '';
            this.newTaskAssignees   = [userId];
            if (tab === 'existing') {
                this.$nextTick(() => this.fetchTasks(''));
            }
        },

        closeAddTaskModal() {
            this.addTaskModal  = false;
            this.searchQuery   = '';
            this.searchResults = [];
            clearTimeout(this.searchTimer);
        },

        onSearchInput() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => this.fetchTasks(this.searchQuery), 250);
        },

        async fetchTasks(q) {
            this.searching = true;
            try {
                const res = await fetch(`/standups/tasks/search?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json' },
                });
                this.searchResults = await res.json();
            } finally {
                this.searching = false;
            }
        },

        addExistingTask(task) {
            if (this.items.some(i => i.task_id === task.id)) return;
            this.items.push({ _id: nextId++, task_id: task.id, description: task.label, status: 'todo' });
            this.closeAddTaskModal();
            this.autoSave();
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
                        title:           this.newTaskTitle.trim(),
                        project_id:      this.newTaskProject,
                        type:            this.newTaskType,
                        priority:        this.newTaskPriority,
                        description:     this.newTaskDescription.trim() || null,
                        due_date:        this.newTaskDueDate || null,
                        estimated_hours: this.newTaskEstimate ? parseFloat(this.newTaskEstimate) : null,
                        assignee_ids:    this.newTaskAssignees,
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    const label = (data.ticket_key ? `[${data.ticket_key}] ` : '') + data.title;
                    this.items.push({ _id: nextId++, task_id: data.task_id, description: label, status: 'todo' });
                    this.closeAddTaskModal();
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

        statusDot(status) {
            return { todo: 'bg-slate-400', in_progress: 'bg-blue-500', in_review: 'bg-amber-400', done: 'bg-green-500' }[status] ?? 'bg-slate-300';
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
            this.panelOpen          = false;
            this.panel              = null;
            this.showAssigneePicker = false;
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
    'in_progress' => ['label' => 'In Progress',  'icon' => 'bi-arrow-repeat',       'color' => 'text-blue-400',   'dotBg' => 'bg-blue-500',   'activeTxt' => 'text-blue-600 dark:text-blue-400',    'activeBg' => 'bg-blue-50/50 dark:bg-blue-900/10',    'border' => 'border-blue-200 dark:border-blue-800/50',   'ring' => 'ring-blue-300'],
    'qa_testing'  => ['label' => 'QA Testing',   'icon' => 'bi-bug-fill',           'color' => 'text-cyan-500',   'dotBg' => 'bg-cyan-500',   'activeTxt' => 'text-cyan-600 dark:text-cyan-400',    'activeBg' => 'bg-cyan-50/50 dark:bg-cyan-900/10',    'border' => 'border-cyan-200 dark:border-cyan-800/50',   'ring' => 'ring-cyan-300'],
    'in_review'   => ['label' => 'In Review',    'icon' => 'bi-eye-fill',           'color' => 'text-amber-400',  'dotBg' => 'bg-amber-400',  'activeTxt' => 'text-amber-600 dark:text-amber-400',  'activeBg' => 'bg-amber-50/50 dark:bg-amber-900/10',  'border' => 'border-amber-200 dark:border-amber-800/50', 'ring' => 'ring-amber-300'],
    'done'        => ['label' => 'Done',         'icon' => 'bi-check-circle-fill',  'color' => 'text-green-400',  'dotBg' => 'bg-green-500',  'activeTxt' => 'text-green-600 dark:text-green-400',  'activeBg' => 'bg-green-50/50 dark:bg-green-900/10',  'border' => 'border-green-200 dark:border-green-800/50', 'ring' => 'ring-green-300'],
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
        @json($projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name])),
        @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name]))
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
            <span x-show="saved" x-cloak x-transition
                  class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <i class="bi bi-check-circle-fill text-xs"></i> Saved
            </span>

            <button @click="submitStandup()"
                    :disabled="saving"
                    :class="submitted
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-300 dark:border-green-700 hover:bg-green-100 dark:hover:bg-green-900/40'
                        : 'bg-violet-600 hover:bg-violet-700 text-white border border-violet-600 hover:border-violet-700'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                           transition-all duration-200 disabled:opacity-50">
                <i :class="saving ? 'bi-arrow-repeat animate-spin' : (submitted ? 'bi-check-circle-fill' : 'bi-send-fill')" class="bi text-xs"></i>
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
    <div class="flex gap-3 overflow-x-auto pb-2 items-start">

        @foreach($columns as $status => $col)
        <div class="rounded-2xl {{ $col['activeBg'] }} border {{ $col['border'] }} flex flex-col min-h-[180px] transition-all duration-150 flex-shrink-0 w-56"
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

            {{-- Add task button — only in To-Do column --}}
            @if($status === 'todo')
            <div class="px-2 pb-2 mt-1">
                <button @click="openAddTaskModal('existing')"
                        class="flex items-center gap-1.5 w-full px-2 py-1.5 rounded-xl
                               border border-dashed border-violet-200 dark:border-violet-800/50
                               text-xs text-violet-400 dark:text-violet-500 justify-center
                               hover:border-violet-400 hover:text-violet-600 dark:hover:border-violet-600 dark:hover:text-violet-300
                               transition-all">
                    <i class="bi bi-plus text-sm"></i> Add Task
                </button>
            </div>
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
            Changes auto-save · drag tasks between columns to update status.
        </p>
        <button @click="submitStandup()"
                :disabled="saving"
                :class="submitted
                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-300 dark:border-green-700 hover:bg-green-100 dark:hover:bg-green-900/40'
                    : 'bg-violet-600 hover:bg-violet-700 text-white border border-violet-600 hover:border-violet-700'"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                       text-sm font-semibold transition-all duration-200 disabled:opacity-50">
            <i :class="saving ? 'bi-arrow-repeat animate-spin' : (submitted ? 'bi-check-circle-fill' : 'bi-send-fill')" class="bi text-xs"></i>
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
             class="fixed right-0 top-0 w-full max-w-sm sm:max-w-md md:max-w-[440px]
                    bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-700
                    shadow-2xl flex flex-col overflow-hidden" style="display:none; z-index:300; bottom:0">

            <template x-if="panelLoading">
                <div class="flex-1 flex items-center justify-center">
                    <i class="bi bi-arrow-repeat animate-spin text-slate-400 text-2xl"></i>
                </div>
            </template>

            <template x-if="panel && !panelLoading">
                <div class="flex flex-col flex-1 min-h-0 overflow-hidden">

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
                        </div>

                        {{-- Properties --}}
                        <div class="border-b border-slate-100 dark:border-slate-700/60">
                            <div class="divide-y divide-slate-50 dark:divide-slate-700/40">
                                <div class="flex items-center px-5 py-2.5">
                                    <span class="text-xs text-slate-400 dark:text-slate-500 w-24 flex-shrink-0">Type</span>
                                    <div class="relative flex-1">
                                        <select @change="patchPanel('type', $event.target.value)"
                                                class="w-full text-sm text-slate-700 dark:text-slate-200 font-medium cursor-pointer outline-none pr-4"
                                                style="background:transparent; border:none; padding:0; appearance:none; -webkit-appearance:none">
                                            <template x-for="t in ['task','bug','feature','story','epic']" :key="t">
                                                <option :value="t" :selected="panel.task.type === t" x-text="t.charAt(0).toUpperCase()+t.slice(1)"></option>
                                            </template>
                                        </select>
                                        <i class="bi bi-chevron-expand absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-slate-300 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div class="flex items-center px-5 py-2.5">
                                    <span class="text-xs text-slate-400 dark:text-slate-500 w-24 flex-shrink-0">Priority</span>
                                    <div class="relative flex-1 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0"
                                              :class="{'bg-slate-300': panel.task.priority==='low','bg-amber-400': panel.task.priority==='medium','bg-orange-500': panel.task.priority==='high','bg-rose-500': panel.task.priority==='critical'}"></span>
                                        <select @change="patchPanel('priority', $event.target.value)"
                                                class="flex-1 text-sm text-slate-700 dark:text-slate-200 font-medium cursor-pointer outline-none pr-4"
                                                style="background:transparent; border:none; padding:0; appearance:none; -webkit-appearance:none">
                                            <template x-for="p in ['low','medium','high','critical']" :key="p">
                                                <option :value="p" :selected="panel.task.priority === p" x-text="p.charAt(0).toUpperCase()+p.slice(1)"></option>
                                            </template>
                                        </select>
                                        <i class="bi bi-chevron-expand absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-slate-300 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div class="flex items-center px-5 py-2.5">
                                    <span class="text-xs text-slate-400 dark:text-slate-500 w-24 flex-shrink-0">Column</span>
                                    <div class="relative flex-1">
                                        <select @change="patchPanel('column_id', parseInt($event.target.value))"
                                                class="w-full text-sm text-slate-700 dark:text-slate-200 font-medium cursor-pointer outline-none pr-4"
                                                style="background:transparent; border:none; padding:0; appearance:none; -webkit-appearance:none">
                                            <template x-for="col in panel.columns" :key="col.id">
                                                <option :value="col.id" :selected="panel.task.column_id === col.id" x-text="col.name"></option>
                                            </template>
                                        </select>
                                        <i class="bi bi-chevron-expand absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-slate-300 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div class="flex items-center px-5 py-2.5">
                                    <span class="text-xs text-slate-400 dark:text-slate-500 w-24 flex-shrink-0">Due date</span>
                                    <input type="date" :value="panel.task.due_date || ''"
                                           @change="patchPanel('due_date', $event.target.value || null)"
                                           class="flex-1 text-sm text-slate-700 dark:text-slate-200 font-medium cursor-pointer outline-none"
                                           style="background:transparent; border:none; padding:0; appearance:none; -webkit-appearance:none" />
                                </div>
                                <div class="flex items-center px-5 py-2.5">
                                    <span class="text-xs text-slate-400 dark:text-slate-500 w-24 flex-shrink-0">Estimate</span>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" min="0" step="0.5" :value="panel.task.estimated_hours ?? 0"
                                               @change="patchPanel('estimated_hours', parseFloat($event.target.value) || 0)"
                                               class="w-14 text-sm text-slate-700 dark:text-slate-200 font-medium outline-none"
                                               style="background:transparent; border:none; padding:0" />
                                        <span class="text-xs text-slate-400">hrs</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Assignees --}}
                        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Assignees</p>
                                <button @click="showAssigneePicker = !showAssigneePicker"
                                        :class="showAssigneePicker ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400 hover:text-violet-500'"
                                        class="text-xs font-medium transition-colors flex items-center gap-1">
                                    <i :class="showAssigneePicker ? 'bi-check-lg' : 'bi-person-plus'" class="bi text-xs"></i>
                                    <span x-text="showAssigneePicker ? 'Done' : 'Edit'"></span>
                                </button>
                            </div>
                            <div class="flex items-center flex-wrap gap-2" x-show="!showAssigneePicker">
                                <template x-if="panel.task.assignee_ids.length === 0">
                                    <p class="text-sm text-slate-400 italic">No one assigned</p>
                                </template>
                                <template x-for="m in panel.members.filter(m => panel.task.assignee_ids.includes(m.id))" :key="'av-'+m.id">
                                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-full pl-1 pr-3 py-1 border border-slate-200 dark:border-slate-700">
                                        <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold"
                                             style="background:#ede9fe; color:#7c3aed"
                                             x-text="m.name.substring(0,2).toUpperCase()"></div>
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-200" x-text="m.name.split(' ')[0]"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="showAssigneePicker" x-cloak
                                 class="grid grid-cols-2 gap-0.5 max-h-40 overflow-y-auto mt-1">
                                <template x-for="m in panel.members" :key="'pick-'+m.id">
                                    <button @click="const ids=panel.task.assignee_ids.includes(m.id)?panel.task.assignee_ids.filter(id=>id!==m.id):[...panel.task.assignee_ids,m.id]; panel.task.assignee_ids=ids; patchPanel('assignee_ids',ids);"
                                            :class="panel.task.assignee_ids.includes(m.id)?'bg-violet-50 dark:bg-violet-900/20':'hover:bg-slate-50 dark:hover:bg-slate-800'"
                                            class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-left transition-colors">
                                        <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold"
                                             :style="panel.task.assignee_ids.includes(m.id) ? 'background:#ede9fe;color:#7c3aed' : 'background:#e2e8f0;color:#64748b'"
                                             x-text="m.name.substring(0,2).toUpperCase()"></div>
                                        <span class="flex-1 text-sm truncate"
                                              :class="panel.task.assignee_ids.includes(m.id) ? 'text-violet-700 dark:text-violet-300 font-medium' : 'text-slate-600 dark:text-slate-300'"
                                              x-text="m.name.split(' ')[0]"></span>
                                        <i x-show="panel.task.assignee_ids.includes(m.id)" class="bi bi-check2 text-violet-500 text-xs flex-shrink-0"></i>
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
                    <div class="flex items-center gap-2 px-5 py-3.5 border-t border-slate-100 dark:border-slate-700/60 flex-shrink-0 bg-white dark:bg-slate-900">
                        <a :href="panel.task.show_url" target="_blank"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl
                                  bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition-colors">
                            <i class="bi bi-box-arrow-up-right text-xs"></i> Open task
                        </a>
                        <a :href="panel.task.edit_url"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl
                                  border border-slate-200 dark:border-slate-700 text-sm font-medium
                                  text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <i class="bi bi-pencil text-xs"></i> Edit
                        </a>
                        <button @click="closePanel()"
                                class="w-9 h-9 rounded-xl flex items-center justify-center border border-slate-200 dark:border-slate-700
                                       text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex-shrink-0">
                            <i class="bi bi-x text-base"></i>
                        </button>
                    </div>

                </div>
            </template>
        </div>
    </template>

    {{-- ── Add Task Modal ──────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="addTaskModal" x-cloak @click="closeAddTaskModal()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" style="display:none; z-index:200"></div>
    </template>

    <template x-teleport="body">
        <div x-show="addTaskModal" x-cloak @keydown.escape.window="closeAddTaskModal()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed inset-0 flex items-center justify-center p-4"
             style="display:none; z-index:201">

            <div @click.stop
                 class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden"
                 style="max-height: min(92vh, 680px)">

                {{-- Modal header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 flex-shrink-0">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Task to Standup</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $day->format('l, F j') }}</p>
                    </div>
                    <button @click="closeAddTaskModal()"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400
                                   hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="flex items-center border-b border-slate-100 dark:border-slate-700/60 px-5 flex-shrink-0">
                    <button @click="addTaskModalTab = 'existing'; fetchTasks(searchQuery)"
                            class="px-1 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i class="bi bi-search text-xs mr-1.5"></i>Find Existing
                    </button>
                    <div class="flex-1"></div>
                    @if($projects->count() > 0)
                    <button @click="addTaskModalTab = 'create'"
                            :class="addTaskModalTab === 'create'
                                ? 'bg-violet-600 text-white border-violet-600'
                                : 'text-violet-600 dark:text-violet-400 border-violet-200 dark:border-violet-700 hover:bg-violet-50 dark:hover:bg-violet-900/20'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors mb-px">
                        <i class="bi bi-plus-lg text-xs"></i>Create New
                    </button>
                    @endif
                </div>

                {{-- Tab: Find Existing --}}
                <div x-show="addTaskModalTab === 'existing'" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-50 dark:border-slate-700/40 flex-shrink-0">
                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text"
                                   x-model="searchQuery"
                                   @input="onSearchInput()"
                                   @keydown.escape="closeAddTaskModal()"
                                   x-effect="addTaskModal && addTaskModalTab === 'existing' && $nextTick(() => $el.focus())"
                                   placeholder="Search by task title…"
                                   class="w-full text-sm bg-slate-50 dark:bg-slate-800 rounded-xl pl-8 pr-9 py-2
                                          border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200
                                          placeholder-slate-400 outline-none focus:border-violet-400 transition-colors" />
                            <span x-show="searching" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                                <i class="bi bi-arrow-repeat animate-spin text-slate-400 text-xs"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto px-2 py-2">
                        <template x-if="searchResults.length === 0 && !searching">
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <i class="bi bi-clipboard text-3xl text-slate-200 dark:text-slate-700 mb-2"></i>
                                <p class="text-sm text-slate-500"
                                   x-text="searchQuery.length >= 2 ? 'No tasks found' : 'Your open assigned tasks'"></p>
                                <p class="text-xs text-slate-400 mt-0.5" x-show="searchQuery.length < 2">
                                    Type to search all accessible tasks
                                </p>
                            </div>
                        </template>
                        <template x-for="task in searchResults" :key="task.id">
                            <button @click="addExistingTask(task)"
                                    :disabled="items.some(i => i.task_id === task.id)"
                                    class="flex items-center gap-3 w-full px-3 py-3 rounded-xl text-left mb-0.5
                                           hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors
                                           disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" :class="statusDot(task.status)"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate" x-text="task.title"></p>
                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span x-show="task.ticket_key"
                                              class="text-[10px] font-mono font-semibold text-violet-500 dark:text-violet-400"
                                              x-text="task.ticket_key"></span>
                                        <span x-show="task.ticket_key" class="text-slate-300 text-[10px]">·</span>
                                        <span class="text-xs text-slate-400" x-text="task.project"></span>
                                        <span class="text-slate-300 text-[10px]">·</span>
                                        <span class="text-xs text-slate-400 capitalize" x-text="task.status.replace('_',' ')"></span>
                                    </div>
                                </div>
                                <div x-show="items.some(i => i.task_id === task.id)"
                                     class="flex-shrink-0 text-xs text-violet-500 font-medium flex items-center gap-1">
                                    <i class="bi bi-check2 text-sm"></i> Added
                                </div>
                                <div x-show="!items.some(i => i.task_id === task.id)"
                                     class="flex-shrink-0 w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center
                                            text-violet-500 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition-colors">
                                    <i class="bi bi-plus text-sm"></i>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Tab: Create New --}}
                @if($projects->count() > 0)
                <div x-show="addTaskModalTab === 'create'" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

                        {{-- Title --}}
                        <input type="text"
                               x-model="newTaskTitle"
                               x-effect="addTaskModal && addTaskModalTab === 'create' && $nextTick(() => $el.focus())"
                               @keydown.escape="closeAddTaskModal()"
                               placeholder="Task title…"
                               class="w-full text-sm font-medium text-slate-900 dark:text-white
                                      bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                                      rounded-xl px-3.5 py-2.5 placeholder-slate-400 outline-none
                                      focus:border-violet-400 focus:ring-2 focus:ring-violet-100 dark:focus:ring-violet-900/30 transition-all" />

                        {{-- Type pills --}}
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Type</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(['task'=>['bi-check-square-fill','text-slate-500'],'bug'=>['bi-bug-fill','text-red-500'],'feature'=>['bi-stars','text-purple-500'],'story'=>['bi-bookmark-fill','text-blue-500'],'epic'=>['bi-lightning-fill','text-orange-500']] as $t=>$meta)
                                <button type="button"
                                        @click="newTaskType = '{{ $t }}'"
                                        :class="newTaskType === '{{ $t }}'
                                            ? 'bg-violet-600 text-white border-violet-600'
                                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-violet-300'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all">
                                    <i class="bi {{ $meta[0] }} text-xs" :class="newTaskType !== '{{ $t }}' ? '{{ $meta[1] }}' : ''"></i>
                                    {{ ucfirst($t) }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Project + Priority (2-col) --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Project <span class="text-rose-400">*</span></p>
                                <select x-model="newTaskProject"
                                        class="w-full text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                                               rounded-xl px-3 py-2 outline-none focus:border-violet-400 transition-colors cursor-pointer"
                                        :class="newTaskProject ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400'">
                                    <option value="">Select…</option>
                                    <template x-for="p in projects" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Priority</p>
                                <select x-model="newTaskPriority"
                                        class="w-full text-sm text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800
                                               border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2
                                               outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>

                        {{-- Assignees --}}
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Assignees</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="m in members" :key="'na-'+m.id">
                                    <button type="button"
                                            @click="newTaskAssignees.includes(m.id) ? newTaskAssignees = newTaskAssignees.filter(id => id !== m.id) : newTaskAssignees.push(m.id)"
                                            class="flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-full border transition-all text-xs font-medium"
                                            :class="newTaskAssignees.includes(m.id)
                                                ? 'bg-violet-50 dark:bg-violet-900/30 border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300'
                                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 hover:border-violet-300'">
                                        <div class="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold"
                                             :style="newTaskAssignees.includes(m.id) ? 'background:#ede9fe;color:#7c3aed' : 'background:#f1f5f9;color:#94a3b8'"
                                             x-text="m.name.substring(0,2).toUpperCase()"></div>
                                        <span x-text="m.name.split(' ')[0]"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-5 py-3.5 border-t border-slate-100 dark:border-slate-700/60 flex flex-col gap-2.5 flex-shrink-0">
                        <button @click="createTask()"
                                :disabled="!newTaskTitle.trim() || !newTaskProject || taskCreating"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl
                                       bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold
                                       transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="bi" :class="taskCreating ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'"></i>
                            <span x-text="taskCreating ? 'Creating…' : 'Create & Add'"></span>
                        </button>
                        <button @click="closeAddTaskModal()"
                                class="w-full py-2 rounded-xl text-sm font-medium text-slate-500
                                       hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            Cancel
                        </button>
                        </button>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </template>

</div>

@endsection
