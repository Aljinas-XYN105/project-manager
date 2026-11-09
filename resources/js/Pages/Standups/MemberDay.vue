<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    date: String,
    date_label: String,
    user: Object,
    standup: Object,
    member_items: Array,
    index_url: String,
})

let nextId = 0
const items = ref((props.member_items ?? []).map(item => ({ ...item, _id: nextId++ })))
const blockers = ref(props.standup?.blockers ?? '')
const saving = ref(false)
const saved = ref(false)
const dragging = ref(null)
const dragOverStatus = ref(null)

// Task detail panel
const panelOpen = ref(false)
const panel = ref(null)
const panelLoading = ref(false)

const columns = [
    { status: 'todo',        label: 'To Do',       dotBg: 'bg-slate-400',  color: 'text-slate-400',  activeBg: 'bg-slate-50 dark:bg-slate-800/50',    border: 'border-slate-200 dark:border-slate-700',    ring: 'ring-slate-300' },
    { status: 'in_progress', label: 'In Progress',  dotBg: 'bg-blue-500',   color: 'text-blue-500',   activeBg: 'bg-blue-50/50 dark:bg-blue-900/10',   border: 'border-blue-200 dark:border-blue-800/50',   ring: 'ring-blue-300' },
    { status: 'qa_testing',  label: 'QA Testing',   dotBg: 'bg-cyan-500',   color: 'text-cyan-500',   activeBg: 'bg-cyan-50/50 dark:bg-cyan-900/10',   border: 'border-cyan-200 dark:border-cyan-800/50',   ring: 'ring-cyan-300' },
    { status: 'in_review',   label: 'In Review',    dotBg: 'bg-amber-400',  color: 'text-amber-500',  activeBg: 'bg-amber-50/50 dark:bg-amber-900/10', border: 'border-amber-200 dark:border-amber-800/50', ring: 'ring-amber-300' },
    { status: 'done',        label: 'Done',         dotBg: 'bg-green-500',  color: 'text-green-500',  activeBg: 'bg-green-50/50 dark:bg-green-900/10', border: 'border-green-200 dark:border-green-800/50', ring: 'ring-green-300' },
]

function itemsFor(status) { return items.value.filter(i => i.status === status) }

function ticketKey(desc) {
    const m = String(desc).match(/^\[([A-Z]+-\d+)\]/)
    return m ? m[1] : null
}
function taskTitle(desc) { return String(desc).replace(/^\[[A-Z]+-\d+\]\s*/, '') }

function dragStart(event, itemId) {
    dragging.value = itemId
    event.dataTransfer.effectAllowed = 'move'
}

function drop(newStatus) {
    const id = dragging.value
    dragOverStatus.value = null
    dragging.value = null
    if (id === null || id === undefined) return
    const item = items.value.find(i => i._id === id)
    if (!item || item.status === newStatus) return
    item.status = newStatus
    if (item.task_id) syncTaskStatus(item.task_id, newStatus)
    autoSave()
}

async function syncTaskStatus(taskId, status) {
    try {
        await fetch(`/tasks/${taskId}/status`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
            body: JSON.stringify({ status }),
        })
    } catch (e) {}
}

function removeItem(id) {
    items.value = items.value.filter(i => i._id !== id)
    autoSave()
}

async function autoSave() { await save() }

async function save() {
    saving.value = true
    saved.value = false
    try {
        await fetch('/standups', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
            body: JSON.stringify({
                standup_date: props.date,
                user_id: props.user.id,
                blockers: blockers.value || null,
                items: items.value.filter(i => String(i.description).trim()).map(i => ({
                    description: String(i.description).trim(),
                    status: i.status,
                    task_id: i.task_id || null,
                })),
            }),
        })
        saved.value = true
        setTimeout(() => { saved.value = false }, 2500)
    } finally {
        saving.value = false
    }
}

// Task detail panel
async function openPanel(taskId) {
    if (!taskId) return
    panelOpen.value = true
    panelLoading.value = true
    panel.value = null
    try {
        const res = await fetch(`/tasks/${taskId}/panel-data`, { headers: { Accept: 'application/json' } })
        if (res.ok) panel.value = await res.json()
    } finally {
        panelLoading.value = false
    }
}

function closePanel() {
    panelOpen.value = false
    panel.value = null
}

async function patchPanel(field, value) {
    if (!panel.value?.task?.id) return
    const id = panel.value.task.id
    await fetch(`/tasks/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
        body: JSON.stringify({ [field]: value }),
    })
    panel.value.task[field] = value
}

const typeOpts = [
    { val: 'task',    label: 'Task',    icon: 'bi-check-square',   chip: 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300' },
    { val: 'bug',     label: 'Bug',     icon: 'bi-bug-fill',       chip: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
    { val: 'feature', label: 'Feature', icon: 'bi-stars',          chip: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' },
    { val: 'story',   label: 'Story',   icon: 'bi-book-fill',      chip: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    { val: 'epic',    label: 'Epic',    icon: 'bi-lightning-fill', chip: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300' },
]
const priorityOpts = [
    { val: 'low',      label: 'Low',      icon: 'bi-arrow-down',              chip: 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' },
    { val: 'medium',   label: 'Medium',   icon: 'bi-dash',                    chip: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    { val: 'high',     label: 'High',     icon: 'bi-arrow-up',                chip: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' },
    { val: 'critical', label: 'Critical', icon: 'bi-exclamation-circle-fill', chip: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
]

const typeDdOpen = ref(false)
const priorityDdOpen = ref(false)
const columnDdOpen = ref(false)

function curType() { return typeOpts.find(o => o.val === panel.value?.task?.type) ?? typeOpts[0] }
function curPriority() { return priorityOpts.find(o => o.val === panel.value?.task?.priority) ?? priorityOpts[1] }

function pickType(val) { panel.value.task.type = val; typeDdOpen.value = false; patchPanel('type', val) }
function pickPriority(val) { panel.value.task.priority = val; priorityDdOpen.value = false; patchPanel('priority', val) }

function toggleAssignee(memberId) {
    const ids = panel.value.task.assignee_ids
    const idx = ids.indexOf(memberId)
    const newIds = idx === -1 ? [...ids, memberId] : ids.filter(id => id !== memberId)
    panel.value.task.assignee_ids = newIds
    patchPanel('assignee_ids', newIds)
}

function toggleLabel(lblId) {
    const ids = panel.value.task.label_ids ?? []
    const newIds = ids.includes(lblId) ? ids.filter(id => id !== lblId) : [...ids, lblId]
    panel.value.task.label_ids = newIds
    patchPanel('label_ids', newIds)
}
</script>

<template>
    <AppLayout :title="`${user.name} — Standup`">
        <div class="flex flex-col h-full gap-4">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <a :href="route('standups.index')" class="hover:text-violet-600 transition-colors">Standups</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <Link :href="`${index_url}?date=${date}`" class="hover:text-violet-600 transition-colors">{{ date_label }}</Link>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>{{ user.name }}</span>
            </div>
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <Link :href="`${index_url}?date=${date}`"
                          class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                        <i class="bi bi-arrow-left text-sm"></i>
                    </Link>
                    <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center font-bold text-violet-600 dark:text-violet-400 text-sm flex-shrink-0">
                        {{ user.initials }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight truncate">{{ user.name }}</h1>
                        <p class="text-sm text-slate-500">{{ date_label }}</p>
                    </div>
                </div>

                <p class="hidden lg:flex items-center gap-1.5 text-xs text-slate-400 mx-auto">
                    <i class="bi bi-info-circle text-violet-400"></i>
                    Drag tasks between lanes — changes sync to each project's board instantly.
                </p>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <span v-if="saving" class="text-xs text-slate-400 flex items-center gap-1">
                        <i class="bi bi-arrow-repeat animate-spin text-xs"></i> Saving…
                    </span>
                    <span v-if="saved" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                        <i class="bi bi-check-circle-fill text-xs"></i> Saved
                    </span>
                </div>
            </div>

            <!-- Blockers banner -->
            <div v-if="blockers"
                 class="px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40 flex items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
                <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed">{{ blockers }}</p>
            </div>

            <!-- Kanban board -->
            <div class="flex gap-3 pb-2 items-stretch flex-1">
                <div v-for="col in columns" :key="col.status"
                     class="rounded-2xl flex flex-col min-h-[100px] transition-all duration-150 flex-1 min-w-[160px] border"
                     :class="[col.activeBg, col.border, dragOverStatus === col.status ? 'ring-2 ring-inset ' + col.ring : '']"
                     @dragover.prevent="dragOverStatus = col.status"
                     @dragleave.self="dragOverStatus = null"
                     @drop.prevent="drop(col.status)">

                    <!-- Column header -->
                    <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :class="col.dotBg"></span>
                        <span class="text-xs font-bold" :class="col.color">{{ col.label }}</span>
                        <span class="ml-auto text-xs font-bold opacity-70 tabular-nums" :class="col.color">
                            {{ itemsFor(col.status).length || '' }}
                        </span>
                    </div>

                    <!-- Task cards -->
                    <div class="flex flex-col gap-1.5 px-2 flex-1">
                        <div v-for="item in itemsFor(col.status)" :key="item._id"
                             draggable="true"
                             @dragstart="dragStart($event, item._id)"
                             @dragend="dragging = null; dragOverStatus = null"
                             @click="!dragging && item.task_id && openPanel(item.task_id)"
                             class="group bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col gap-1 select-none transition-shadow hover:shadow-md"
                             :class="[dragging === item._id ? 'opacity-40 cursor-grabbing' : (item.task_id ? 'cursor-pointer' : 'cursor-grab')]">
                            <div v-if="ticketKey(item.description)" class="flex items-center gap-1.5 text-[10px] text-slate-400 font-medium">
                                <i class="bi bi-check2-square text-violet-400 text-xs"></i>
                                <span>{{ ticketKey(item.description) }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <p class="flex-1 text-sm text-slate-700 dark:text-slate-200 leading-snug break-words">
                                    {{ item.task_id ? taskTitle(item.description) : item.description }}
                                </p>
                                <button @click.stop="removeItem(item._id)"
                                        class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-slate-300 hover:text-rose-500 mt-0.5">
                                    <i class="bi bi-x text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Empty column hint -->
                        <div v-if="itemsFor(col.status).length === 0 && dragging === null"
                             class="flex-1 flex flex-col items-center justify-center gap-1.5 py-6 opacity-40">
                            <i class="bi bi-arrow-left-right text-lg" :class="col.color"></i>
                            <span class="text-[11px] font-medium text-slate-400">Drag tasks here</span>
                        </div>

                        <!-- Drop target -->
                        <div v-if="dragOverStatus === col.status && dragging !== null"
                             class="rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-600 h-12 flex items-center justify-center">
                            <i class="bi bi-arrow-down text-violet-400 text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Detail Panel Backdrop -->
        <Teleport to="body">
            <div v-if="panelOpen"
                 @click="closePanel()"
                 class="fixed inset-0 bg-black/20 backdrop-blur-[2px]"
                 style="z-index:40"></div>
        </Teleport>

        <!-- Task Detail Panel -->
        <Teleport to="body">
            <div v-if="panelOpen"
                 class="fixed right-0 top-0 bottom-0 w-full max-w-sm sm:max-w-md md:max-w-[440px] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-700 shadow-2xl flex flex-col overflow-hidden"
                 style="z-index:300"
                 @keydown.escape.window="closePanel()">

                <!-- Loading -->
                <div v-if="panelLoading" class="flex-1 flex items-center justify-center">
                    <i class="bi bi-arrow-repeat animate-spin text-slate-400 text-2xl"></i>
                </div>

                <!-- Panel content -->
                <template v-if="panel && !panelLoading">
                    <!-- Header -->
                    <div class="flex items-center gap-2 px-5 py-3.5 border-b border-slate-100 dark:border-slate-700/60 flex-shrink-0">
                        <div class="flex-1 min-w-0 flex items-center gap-2 text-xs">
                            <i class="bi bi-check2-square text-violet-500 text-sm flex-shrink-0"></i>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ panel.task.ticket_key }}</span>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span class="text-slate-500 dark:text-slate-400 truncate">{{ panel.task.project_name }}</span>
                        </div>
                        <div class="flex items-center gap-0.5 flex-shrink-0">
                            <a :href="panel.task.show_url" target="_blank"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                                <i class="bi bi-box-arrow-up-right text-sm"></i>
                            </a>
                            <a :href="panel.task.edit_url"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                                <i class="bi bi-pencil text-sm"></i>
                            </a>
                            <button @click="closePanel()"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable body -->
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">
                        <!-- Title (contenteditable) -->
                        <div>
                            <div :contenteditable="true"
                                 @blur="patchPanel('title', $event.target.textContent.trim())"
                                 @keydown.enter.prevent="$event.target.blur()"
                                 class="text-lg font-semibold text-slate-900 dark:text-white leading-snug outline-none rounded-lg px-2 py-1 -mx-2 cursor-text min-h-[1.5em] hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-slate-50 dark:focus:bg-slate-800 transition-colors"
                                 :data-value="panel.task.title">{{ panel.task.title }}</div>
                        </div>

                        <!-- Type + Priority -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Type</label>
                                <div class="relative">
                                    <button type="button" @click="typeDdOpen = !typeDdOpen; priorityDdOpen = false; columnDdOpen = false"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold w-full hover:opacity-90 transition-colors"
                                            :class="curType().chip">
                                        <i class="bi text-[11px]" :class="curType().icon"></i>
                                        <span class="flex-1 text-left">{{ curType().label }}</span>
                                        <i class="bi bi-chevron-down text-[9px] opacity-50"></i>
                                    </button>
                                    <div v-if="typeDdOpen" @click.outside="typeDdOpen = false"
                                         class="absolute left-0 top-full mt-1 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5"
                                         style="z-index:400">
                                        <button v-for="o in typeOpts" :key="o.val" type="button" @click="pickType(o.val)"
                                                class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                            <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="o.chip">
                                                <i class="bi text-[10px]" :class="o.icon"></i>
                                            </span>
                                            <span>{{ o.label }}</span>
                                            <i v-if="o.val === panel.task.type" class="bi bi-check text-violet-500 text-xs ml-auto"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Priority</label>
                                <div class="relative">
                                    <button type="button" @click="priorityDdOpen = !priorityDdOpen; typeDdOpen = false; columnDdOpen = false"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold w-full hover:opacity-90 transition-colors"
                                            :class="curPriority().chip">
                                        <i class="bi text-[11px]" :class="curPriority().icon"></i>
                                        <span class="flex-1 text-left">{{ curPriority().label }}</span>
                                        <i class="bi bi-chevron-down text-[9px] opacity-50"></i>
                                    </button>
                                    <div v-if="priorityDdOpen"
                                         class="absolute left-0 top-full mt-1 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5"
                                         style="z-index:400">
                                        <button v-for="o in priorityOpts" :key="o.val" type="button" @click="pickPriority(o.val)"
                                                class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                            <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="o.chip">
                                                <i class="bi text-[10px]" :class="o.icon"></i>
                                            </span>
                                            <span>{{ o.label }}</span>
                                            <i v-if="o.val === panel.task.priority" class="bi bi-check text-violet-500 text-xs ml-auto"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column + Due Date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Column / Stage</label>
                                <div class="relative">
                                    <button type="button" @click="columnDdOpen = !columnDdOpen; typeDdOpen = false; priorityDdOpen = false"
                                            class="form-input text-left flex items-center justify-between gap-2 w-full text-sm">
                                        <span>{{ panel.columns?.find(c => c.id === panel.task.column_id)?.name ?? '—' }}</span>
                                        <i class="bi bi-chevron-down flex-shrink-0 text-slate-400 text-[0.65rem]" :class="columnDdOpen ? 'rotate-180' : ''"></i>
                                    </button>
                                    <div v-if="columnDdOpen"
                                         class="absolute mt-1 w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden max-h-48 overflow-y-auto"
                                         style="z-index:300">
                                        <button v-for="col in panel.columns" :key="col.id" type="button"
                                                @click="patchPanel('column_id', col.id); columnDdOpen = false"
                                                class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                                :class="panel.task.column_id === col.id ? 'text-violet-600 dark:text-violet-400 bg-violet-50/50 font-medium' : 'text-slate-700 dark:text-slate-200'">
                                            <span>{{ col.name }}</span>
                                            <i v-if="panel.task.column_id === col.id" class="bi bi-check2 text-violet-500 text-xs ml-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Due Date</label>
                                <DatePicker :modelValue="panel.task.due_date ?? ''"
                                            @update:modelValue="val => patchPanel('due_date', val || null)" />
                            </div>
                        </div>

                        <!-- Estimate -->
                        <div>
                            <label class="form-label">Estimate (hours)</label>
                            <input type="number" min="0" step="0.5" class="form-input w-32 text-sm"
                                   :value="panel.task.estimated_hours ?? 0"
                                   @change="patchPanel('estimated_hours', parseFloat($event.target.value) || 0)"
                                   @keydown.enter="$event.target.blur()">
                        </div>

                        <!-- Assignees -->
                        <div>
                            <label class="form-label">Assignees</label>
                            <div v-if="panel.task.assignee_ids?.length" class="flex flex-wrap gap-1.5 mb-2">
                                <span v-for="m in panel.members?.filter(m => panel.task.assignee_ids?.includes(m.id))" :key="'chip-'+m.id"
                                      class="inline-flex items-center gap-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs px-2 py-0.5 rounded-full">
                                    <span>{{ m.name.split(' ')[0] }}</span>
                                    <button type="button" @click="toggleAssignee(m.id)" class="hover:text-rose-500 transition-colors">
                                        <i class="bi bi-x text-xs"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-1 max-h-40 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                                <button v-for="m in panel.members" :key="'pick-'+m.id" type="button"
                                        @click="toggleAssignee(m.id)"
                                        class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-left transition-colors"
                                        :class="panel.task.assignee_ids?.includes(m.id) ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' : 'hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
                                    <div class="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold"
                                         :style="panel.task.assignee_ids?.includes(m.id) ? 'background:#ede9fe;color:#7c3aed' : 'background:#e2e8f0;color:#64748b'">
                                        {{ m.name.substring(0,2).toUpperCase() }}
                                    </div>
                                    <span class="text-xs truncate flex-1">{{ m.name.split(' ')[0] }}</span>
                                    <i v-if="panel.task.assignee_ids?.includes(m.id)" class="bi bi-check-lg text-violet-500 text-xs flex-shrink-0"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Labels -->
                        <div v-if="panel.labels?.length">
                            <label class="form-label">Labels</label>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="lbl in panel.labels" :key="lbl.id"
                                        @click="toggleLabel(lbl.id)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-xs font-medium transition-all"
                                        :style="`border-color:${lbl.color}; color:${lbl.color}; background:${panel.task.label_ids?.includes(lbl.id) ? lbl.color+'22' : 'transparent'}`">
                                    {{ lbl.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div>
                            <label class="form-label mb-1.5">Attachments
                                <span v-if="panel.attachments?.length" class="font-normal text-slate-400">({{ panel.attachments.length }})</span>
                            </label>
                            <p v-if="!panel.attachments?.length" class="text-xs text-slate-400 italic">No files attached</p>
                            <div v-else class="space-y-1">
                                <a v-for="att in panel.attachments" :key="att.id" :href="att.url" target="_blank"
                                   class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <i class="bi bi-paperclip text-sm text-slate-400 flex-shrink-0"></i>
                                    <span class="text-xs text-slate-700 dark:text-slate-300 truncate flex-1">{{ att.original_name }}</span>
                                    <span class="text-[10px] text-slate-400 flex-shrink-0">{{ att.size_formatted }}</span>
                                </a>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label">Description</label>
                            <textarea rows="4" placeholder="Add a description…" class="form-input resize-none w-full text-sm"
                                      :value="panel.task.description"
                                      @blur="patchPanel('description', $event.target.value.trim())"></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-slate-50/50 dark:bg-slate-800/20">
                        <div></div>
                        <div class="flex items-center gap-2">
                            <a :href="panel.task.edit_url" class="btn-ghost btn-sm text-xs">
                                <i class="bi bi-pencil-square"></i> Full edit
                            </a>
                            <button @click="closePanel()" class="btn-secondary btn-sm text-xs">Close</button>
                        </div>
                    </div>
                </template>
            </div>
        </Teleport>
    </AppLayout>
</template>
