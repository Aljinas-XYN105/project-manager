@extends('layouts.app')
@section('title', $user->name . ' — Standup')

@section('content')
<script>
function standupBoard(userId, dateStr, existingItems, existingBlockers) {
    let nextId = existingItems.length;

    return {
        items:          existingItems.map((item, i) => ({ ...item, _id: i })),
        blockers:       existingBlockers || '',
        saving:         false,
        saved:          false,
        dragging:       null,
        dragOverStatus: null,

        itemsFor(status) {
            return this.items.filter(i => i.status === status);
        },

        removeItem(id) {
            this.items = this.items.filter(i => i._id !== id);
            this.save();
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
            this.save();
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

        // ── Task detail panel ────────────────────────────────────────────
        panelOpen:          false,
        panel:              null,
        panelLoading:       false,
        showAssigneePicker: false,

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
            await fetch(`/tasks/${id}`, {
                method:  'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                },
                body: JSON.stringify({ [field]: value }),
            });
            this.panel.task[field] = value;
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
                        user_id:      userId,
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
                setTimeout(() => { this.saved = false; }, 2500);
            } finally {
                this.saving = false;
            }
        },
    };
}
window.__memberDayInit = function() {
    return standupBoard(
        {{ $user->id }},
        '{{ $day->toDateString() }}',
        @json($memberItems),
        @json($standup?->blockers ?? '')
    );
};
</script>
@php
$columns = [
    'todo'        => ['label' => 'To Do',       'dot' => 'text-slate-400',  'dotBg' => 'bg-slate-400',  'border' => 'border-slate-200 dark:border-slate-700',   'ring' => 'ring-slate-300'],
    'in_progress' => ['label' => 'In Progress',  'dot' => 'text-blue-500',   'dotBg' => 'bg-blue-500',   'border' => 'border-blue-200 dark:border-blue-800/50',  'ring' => 'ring-blue-300'],
    'in_review'   => ['label' => 'In Review',    'dot' => 'text-amber-500',  'dotBg' => 'bg-amber-400',  'border' => 'border-amber-200 dark:border-amber-800/50','ring' => 'ring-amber-300'],
    'done'        => ['label' => 'Done',         'dot' => 'text-green-500',  'dotBg' => 'bg-green-500',  'border' => 'border-green-200 dark:border-green-800/50','ring' => 'ring-green-300'],
];
@endphp

<div x-data="__memberDayInit()"
     class="flex flex-col h-full">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-5 gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('standups.index', ['date' => $day->toDateString()]) }}"
               class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center
                      text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center
                        font-bold text-violet-600 dark:text-violet-400 text-sm flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight truncate">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $user->job_title ?? $user->department ?? $user->email }}
                    <span class="mx-1.5 text-slate-300">·</span>
                    {{ $day->format('l, F j') }}
                </p>
            </div>
        </div>

        <p class="hidden lg:flex items-center gap-1.5 text-xs text-slate-400 mx-auto">
            <i class="bi bi-info-circle text-violet-400"></i>
            Drag tasks between lanes — changes sync to each project's board instantly.
        </p>

        <div class="flex items-center gap-2 flex-shrink-0">
            <span x-show="saving" class="text-xs text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrow-repeat animate-spin text-xs"></i> Saving…
            </span>
            <span x-show="saved" x-transition class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <i class="bi bi-check-circle-fill text-xs"></i> Saved
            </span>
            <a href="{{ route('standups.calendar') }}?return_date={{ $day->toDateString() }}"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700
                      bg-white dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300
                      hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
                <i class="bi bi-calendar3 text-violet-500 text-xs"></i>
                {{ $day->isToday() ? 'Today' : $day->format('D, M j') }}
            </a>
        </div>
    </div>

    {{-- Blockers --}}
    <template x-if="blockers">
        <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40 flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
            <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed" x-text="blockers"></p>
        </div>
    </template>

    {{-- Kanban board --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-start flex-1">

        @foreach($columns as $status => $col)
        <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border {{ $col['border'] }} flex flex-col min-h-[100px] transition-all duration-150"
             @dragover.prevent="dragOver('{{ $status }}')"
             @dragleave.self="dragOverStatus = null"
             @drop.prevent="drop('{{ $status }}')"
             :class="dragOverStatus === '{{ $status }}' ? 'ring-2 ring-inset {{ $col['ring'] }}' : ''">

            {{-- Column header --}}
            <div class="flex items-center gap-2 px-0.5 mb-1 px-3 pt-3 pb-2">
                <span class="w-2 h-2 rounded-full {{ $col['dotBg'] }} flex-shrink-0"></span>
                <span class="text-xs font-bold {{ $col['dot'] }}">{{ $col['label'] }}</span>
                <span class="ml-auto text-xs font-bold {{ $col['dot'] }} opacity-70"
                      x-text="itemsFor('{{ $status }}').length || ''"></span>
            </div>

            {{-- Task cards --}}
            <div class="flex flex-col gap-1.5 px-2 flex-1">
                <template x-for="item in itemsFor('{{ $status }}')" :key="item._id">
                    <div draggable="true"
                         @dragstart="dragStart($event, item._id)"
                         @dragend="dragging = null; dragOverStatus = null"
                         @click="!dragging && item.task_id && openPanel(item.task_id)"
                         :class="[dragging === item._id ? 'opacity-40 cursor-grabbing' : (item.task_id ? 'cursor-pointer' : 'cursor-grab')]"
                         class="group bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm
                                border border-slate-100 dark:border-slate-700
                                flex flex-col gap-1 select-none transition-shadow hover:shadow-md">
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
                    </div>
                </template>

                {{-- Drop target placeholder --}}
                <div x-show="dragOverStatus === '{{ $status }}' && dragging !== null"
                     x-cloak
                     class="rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-600
                            h-12 flex items-center justify-center">
                    <i class="bi bi-arrow-down text-violet-400 text-sm"></i>
                </div>
            </div>

        </div>
        @endforeach

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
                            <textarea rows="4" :placeholder="'Add a description…'"
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
                                class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700
                                       flex items-center justify-center text-slate-400 hover:text-slate-600
                                       hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex-shrink-0">
                            <i class="bi bi-x text-base"></i>
                        </button>
                    </div>

                </div>
            </template>
        </div>
    </template>

</div>

@endsection
