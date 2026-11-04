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
        <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border {{ $col['border'] }} flex flex-col min-h-[180px] transition-all duration-150"
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

    {{-- ── Task Detail Slide-in Panel ─────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="panelOpen" x-cloak class="fixed inset-0 z-[200] flex justify-end" style="display:none">
            <div @click="closePanel()" x-show="panelOpen"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-black/25 dark:bg-black/50 backdrop-blur-sm"></div>
            <div x-show="panelOpen"
                 x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 class="relative z-10 w-full max-w-[420px] bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden">
                <template x-if="panelLoading">
                    <div class="flex-1 flex items-center justify-center"><i class="bi bi-arrow-repeat animate-spin text-slate-400 text-2xl"></i></div>
                </template>
                <template x-if="panel && !panelLoading">
                    <div class="flex flex-col h-full overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 flex-shrink-0">
                            <div class="flex-1 min-w-0 flex items-center gap-2 text-xs text-slate-400">
                                <i class="bi bi-check2-square text-violet-500 text-sm flex-shrink-0"></i>
                                <span class="font-semibold text-slate-600 dark:text-slate-300" x-text="panel.task.ticket_key"></span>
                                <span class="text-slate-200 dark:text-slate-600">·</span>
                                <span class="truncate" x-text="panel.task.project_name"></span>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <a :href="panel.task.show_url" target="_blank" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors"><i class="bi bi-box-arrow-up-right text-sm"></i></a>
                                <a :href="panel.task.edit_url" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors"><i class="bi bi-pencil text-sm"></i></a>
                                <button @click="closePanel()" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"><i class="bi bi-x-lg text-sm"></i></button>
                            </div>
                        </div>
                        <div class="px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-700/60 flex-shrink-0">
                            <div x-text="panel.task.title" contenteditable="true"
                                 @blur="patchPanel('title', $el.textContent.trim())" @keydown.enter.prevent="$el.blur()"
                                 class="text-lg font-semibold text-slate-900 dark:text-white leading-snug outline-none rounded-lg px-2 py-1 -mx-2 cursor-text hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-slate-50 dark:focus:bg-slate-800 transition-colors"></div>
                            <p class="text-xs text-slate-400 mt-1 ml-0.5">Click to edit title</p>
                        </div>
                        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Type</p>
                                    <select @change="patchPanel('type', $event.target.value)" class="w-full text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                        <template x-for="t in ['task','bug','feature','story','epic']" :key="t"><option :value="t" :selected="panel.task.type === t" x-text="t.charAt(0).toUpperCase() + t.slice(1)"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Priority</p>
                                    <select @change="patchPanel('priority', $event.target.value)" class="w-full text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                        <template x-for="p in ['low','medium','high','critical']" :key="p"><option :value="p" :selected="panel.task.priority === p" x-text="p.charAt(0).toUpperCase() + p.slice(1)"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status</p>
                                    <select @change="patchPanel('status', $event.target.value)" class="w-full text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer">
                                        <template x-for="s in ['todo','in_progress','in_review','done','cancelled']" :key="s"><option :value="s" :selected="panel.task.status === s" x-text="s.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase())"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Due Date</p>
                                    <input type="date" :value="panel.task.due_date || ''" @change="patchPanel('due_date', $event.target.value || null)" class="w-full text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors cursor-pointer" />
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Estimate (Hours)</p>
                                <input type="number" min="0" step="0.5" :value="panel.task.estimated_hours || 0" @change="patchPanel('estimated_hours', parseFloat($event.target.value) || 0)" class="w-24 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-200 outline-none focus:border-violet-400 transition-colors" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Assignees</p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="m in panel.members" :key="m.id">
                                        <button @click="const ids = panel.task.assignee_ids.includes(m.id) ? panel.task.assignee_ids.filter(id => id !== m.id) : [...panel.task.assignee_ids, m.id]; panel.task.assignee_ids = ids; patchPanel('assignee_ids', ids);"
                                                :class="panel.task.assignee_ids.includes(m.id) ? 'bg-violet-100 dark:bg-violet-900/40 border-violet-300 dark:border-violet-600 text-violet-700 dark:text-violet-300' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500'"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-medium transition-all">
                                            <div class="w-4 h-4 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-[8px] font-bold" x-text="m.name.substring(0,2).toUpperCase()"></div>
                                            <span x-text="m.name.split(' ')[0]"></span>
                                            <i x-show="panel.task.assignee_ids.includes(m.id)" class="bi bi-check text-[10px]"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <template x-if="panel.labels && panel.labels.length">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Labels</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="lbl in panel.labels" :key="lbl.id">
                                            <button @click="const ids = panel.task.label_ids.includes(lbl.id) ? panel.task.label_ids.filter(id => id !== lbl.id) : [...panel.task.label_ids, lbl.id]; panel.task.label_ids = ids; patchPanel('label_ids', ids);"
                                                    :style="panel.task.label_ids.includes(lbl.id) ? `background:${lbl.color}22; border-color:${lbl.color}66; color:${lbl.color}` : ''"
                                                    :class="panel.task.label_ids.includes(lbl.id) ? '' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500'"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-medium transition-all"
                                                    x-text="lbl.name"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Attachments <span x-show="panel.attachments.length" x-text="'(' + panel.attachments.length + ')'"></span></p>
                                <template x-if="panel.attachments.length === 0"><p class="text-xs text-slate-400 italic">No attachments</p></template>
                                <template x-for="att in panel.attachments" :key="att.id">
                                    <a :href="att.url" target="_blank" class="flex items-center gap-2 text-xs text-violet-600 dark:text-violet-400 hover:underline py-0.5">
                                        <i class="bi bi-paperclip text-xs text-slate-400"></i><span x-text="att.original_name"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100 dark:border-slate-700/60 flex-shrink-0 bg-slate-50/60 dark:bg-slate-800/40">
                            <a :href="panel.task.edit_url" class="inline-flex items-center gap-1.5 text-sm font-semibold text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-200 transition-colors"><i class="bi bi-pencil-square text-xs"></i> Full edit</a>
                            <button @click="closePanel()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">Close</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>

@endsection
