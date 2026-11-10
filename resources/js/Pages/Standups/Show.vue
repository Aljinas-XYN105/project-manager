<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProjectSelect from '@/Components/ProjectSelect.vue'

const props = defineProps({
    date: String,
    date_label: String,
    is_today: Boolean,
    user: Object,
    standup: Object,
    seed_items: Array,
    projects: Array,
    members: Array,
    store_url: String,
    index_url: String,
})

let nextId = 0
const items = ref((props.seed_items ?? []).map((item, i) => ({ ...item, _id: nextId++ })))
const blockers = ref(props.standup?.blockers ?? '')
const saving = ref(false)
const saved = ref(false)
const submitted = ref(props.standup !== null)
const justSubmitted = ref(false)
const dragging = ref(null)
const dragOverStatus = ref(null)

// Add Task Modal
const addTaskModal = ref(false)
const addTaskModalTab = ref('existing')
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
let searchTimer = null

// Create new task form
const newTaskTitle = ref('')
const newTaskProject = ref('')
const newTaskType = ref('task')
const newTaskPriority = ref('medium')
const newTaskAssignees = ref([props.user?.id ?? 0])
const taskCreating = ref(false)

const columns = [
    { status: 'todo',        label: 'To Do',       icon: 'bi-circle-fill',       color: 'text-slate-400',  dotBg: 'bg-slate-400',  activeTxt: 'text-slate-600 dark:text-slate-300',  activeBg: 'bg-slate-50 dark:bg-slate-800/60',   border: 'border-slate-200 dark:border-slate-700',   ring: 'ring-slate-300' },
    { status: 'in_progress', label: 'In Progress',  icon: 'bi-arrow-repeat',       color: 'text-blue-400',   dotBg: 'bg-blue-500',   activeTxt: 'text-blue-600 dark:text-blue-400',    activeBg: 'bg-blue-50/50 dark:bg-blue-900/10',    border: 'border-blue-200 dark:border-blue-800/50',   ring: 'ring-blue-300' },
    { status: 'in_review',   label: 'In Review',    icon: 'bi-eye-fill',           color: 'text-amber-400',  dotBg: 'bg-amber-400',  activeTxt: 'text-amber-600 dark:text-amber-400',  activeBg: 'bg-amber-50/50 dark:bg-amber-900/10',  border: 'border-amber-200 dark:border-amber-800/50', ring: 'ring-amber-300' },
    { status: 'qa_testing',  label: 'QA Testing',   icon: 'bi-bug-fill',           color: 'text-cyan-500',   dotBg: 'bg-cyan-500',   activeTxt: 'text-cyan-600 dark:text-cyan-400',    activeBg: 'bg-cyan-50/50 dark:bg-cyan-900/10',    border: 'border-cyan-200 dark:border-cyan-800/50',   ring: 'ring-cyan-300' },
    { status: 'done',        label: 'Done',         icon: 'bi-check-circle-fill',  color: 'text-green-400',  dotBg: 'bg-green-500',  activeTxt: 'text-green-600 dark:text-green-400',  activeBg: 'bg-green-50/50 dark:bg-green-900/10',  border: 'border-green-200 dark:border-green-800/50', ring: 'ring-green-300' },
]

function itemsFor(status) { return items.value.filter(i => i.status === status) }

function statusDot(status) {
    return { todo: 'bg-slate-400', in_progress: 'bg-blue-500', in_review: 'bg-amber-400', qa_testing: 'bg-cyan-500', done: 'bg-green-500' }[status] ?? 'bg-slate-300'
}

function ticketKey(desc) {
    const m = String(desc).match(/^\[([A-Z]+-\d+)\]/)
    return m ? m[1] : null
}

function taskTitle(desc) {
    return String(desc).replace(/^\[[A-Z]+-\d+\]\s*/, '')
}

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
        await fetch(props.store_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
            body: JSON.stringify({
                standup_date: props.date,
                blockers: blockers.value || null,
                items: items.value.filter(i => String(i.description).trim()).map(i => ({
                    description: String(i.description).trim(),
                    status: i.status,
                    task_id: i.task_id || null,
                })),
            }),
        })
        saved.value = true
        setTimeout(() => { saved.value = false }, 3000)
    } finally {
        saving.value = false
    }
}

async function submitStandup() {
    await save()
    submitted.value = true
    justSubmitted.value = true
    setTimeout(() => { justSubmitted.value = false }, 5000)
}

// Add task modal
function openAddTaskModal(tab = 'existing') {
    addTaskModal.value = true
    addTaskModalTab.value = tab
    searchQuery.value = ''
    searchResults.value = []
    newTaskTitle.value = ''
    newTaskProject.value = ''
    newTaskType.value = 'task'
    newTaskPriority.value = 'medium'
    newTaskAssignees.value = [props.user?.id ?? 0]
    if (tab === 'existing') fetchTasks('')
}

function closeAddTaskModal() {
    addTaskModal.value = false
    searchQuery.value = ''
    searchResults.value = []
    clearTimeout(searchTimer)
}

function onSearchInput() {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchTasks(searchQuery.value), 250)
}

async function fetchTasks(q) {
    searching.value = true
    try {
        const res = await fetch(`/standups/tasks/search?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
        searchResults.value = await res.json()
    } finally {
        searching.value = false
    }
}

function addExistingTask(task) {
    if (items.value.some(i => i.task_id === task.id)) return
    items.value.push({ _id: nextId++, task_id: task.id, description: task.label, status: 'todo' })
    closeAddTaskModal()
    autoSave()
}

async function createTask() {
    if (!newTaskTitle.value.trim() || !newTaskProject.value) return
    taskCreating.value = true
    try {
        const res = await fetch('/tasks/quick-store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
            body: JSON.stringify({
                title: newTaskTitle.value.trim(),
                project_id: newTaskProject.value,
                type: newTaskType.value,
                priority: newTaskPriority.value,
                assignee_ids: newTaskAssignees.value,
            }),
        })
        const data = await res.json()
        if (data.ok) {
            const label = (data.ticket_key ? `[${data.ticket_key}] ` : '') + data.title
            items.value.push({ _id: nextId++, task_id: data.task_id, description: label, status: 'todo' })
            closeAddTaskModal()
            autoSave()
        }
    } finally {
        taskCreating.value = false
    }
}

const taskTypes = [
    { val: 'task', icon: 'bi-check-square-fill', color: 'text-slate-500' },
    { val: 'bug', icon: 'bi-bug-fill', color: 'text-red-500' },
    { val: 'feature', icon: 'bi-stars', color: 'text-purple-500' },
    { val: 'story', icon: 'bi-bookmark-fill', color: 'text-blue-500' },
    { val: 'epic', icon: 'bi-lightning-fill', color: 'text-orange-500' },
]
</script>

<template>
    <AppLayout :title="`My Standup — ${date_label}`">
        <div class="flex flex-col gap-4">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <a :href="route('standups.index')" class="hover:text-violet-600 transition-colors">Standups</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>{{ date_label }}</span>
            </div>
            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <Link :href="index_url"
                          class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                        <i class="bi bi-arrow-left text-sm"></i>
                    </Link>
                    <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-sm bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">
                        {{ user.initials }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                            {{ user.name }}
                            <span class="text-slate-400 font-normal text-sm">· {{ user.job_title }}</span>
                        </h1>
                        <p class="text-xs text-slate-500">{{ date_label }}</p>
                    </div>
                </div>

                <p class="hidden lg:flex items-center gap-1.5 text-xs text-slate-400">
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
                    <button @click="submitStandup()" :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 disabled:opacity-50"
                            :class="submitted
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-300 dark:border-green-700 hover:bg-green-100 dark:hover:bg-green-900/40'
                                : 'bg-violet-600 hover:bg-violet-700 text-white border border-violet-600 hover:border-violet-700'">
                        <i class="bi text-xs" :class="saving ? 'bi-arrow-repeat animate-spin' : (submitted ? 'bi-check-circle-fill' : 'bi-send-fill')"></i>
                        {{ submitted ? 'Submitted ✓' : 'Submit Standup' }}
                    </button>
                </div>
            </div>

            <!-- Success banner -->
            <div v-if="submitted && justSubmitted"
                 class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0"></i>
                <p class="text-sm font-medium text-green-700 dark:text-green-300">
                    Standup submitted for {{ date_label }}. Nice work!
                </p>
                <button @click="justSubmitted = false" class="ml-auto text-green-400 hover:text-green-600 flex-shrink-0">
                    <i class="bi bi-x text-sm"></i>
                </button>
            </div>

            <!-- Blockers banner -->
            <div v-if="blockers && !justSubmitted"
                 class="flex items-start gap-3 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
                <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed">{{ blockers }}</p>
            </div>

            <!-- Kanban columns -->
            <div class="flex gap-3 pb-2 items-stretch">
                <div v-for="col in columns" :key="col.status"
                     class="rounded-2xl flex flex-col min-h-[200px] transition-all duration-150 flex-1 min-w-[180px] border"
                     :class="[col.activeBg, col.border, dragOverStatus === col.status ? 'ring-2 ring-inset ' + col.ring : '']"
                     @dragover.prevent="dragOverStatus = col.status"
                     @dragleave.self="dragOverStatus = null"
                     @drop.prevent="drop(col.status)">

                    <!-- Column header -->
                    <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                        <i class="bi text-xs" :class="[col.icon, col.color]"></i>
                        <span class="text-xs font-bold" :class="col.color">{{ col.label }}</span>
                        <span class="ml-auto text-xs font-bold opacity-60 tabular-nums" :class="col.color">
                            {{ itemsFor(col.status).length || '' }}
                        </span>
                    </div>

                    <!-- Task cards -->
                    <div class="flex flex-col gap-1.5 px-2 flex-1">
                        <div v-for="item in itemsFor(col.status)" :key="item._id"
                             draggable="true"
                             @dragstart="dragStart($event, item._id)"
                             @dragend="dragging = null; dragOverStatus = null"
                             class="group bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col gap-1 select-none transition-shadow hover:shadow-md"
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

                        <!-- Empty column drag hint -->
                        <div v-if="itemsFor(col.status).length === 0 && dragging === null"
                             class="flex-1 flex flex-col items-center justify-center gap-1.5 py-6 opacity-40">
                            <i class="bi bi-arrow-left-right text-lg" :class="col.color"></i>
                            <span class="text-[11px] font-medium text-slate-400">Drag tasks here</span>
                        </div>

                        <!-- Drop target placeholder -->
                        <div v-if="dragOverStatus === col.status && dragging !== null"
                             class="rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-600 h-12 flex items-center justify-center">
                            <i class="bi bi-arrow-down text-violet-400 text-sm"></i>
                        </div>
                    </div>

                    <!-- Add task button — only in To-Do column -->
                    <div v-if="col.status === 'todo'" class="px-2 pb-2 mt-1">
                        <button @click="openAddTaskModal('existing')"
                                class="flex items-center gap-1.5 w-full px-2 py-1.5 rounded-xl border border-dashed border-violet-200 dark:border-violet-800/50 text-xs text-violet-400 dark:text-violet-500 justify-center hover:border-violet-400 hover:text-violet-600 dark:hover:border-violet-600 dark:hover:text-violet-300 transition-all">
                            <i class="bi bi-plus text-sm"></i> Add Task
                        </button>
                    </div>
                </div>
            </div>

            <!-- Blockers input -->
            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                    <i class="bi bi-exclamation-triangle text-amber-500"></i>
                    Blockers
                    <span class="font-normal normal-case tracking-normal text-slate-400">(optional)</span>
                </label>
                <textarea v-model="blockers" @blur="autoSave" rows="2"
                          placeholder="e.g. Waiting for API access, blocked on design review…"
                          class="form-input w-full resize-none text-sm bg-white dark:bg-slate-800"></textarea>
            </div>

            <!-- Bottom hint -->
            <p class="text-xs text-slate-400 pt-1">
                Changes auto-save · drag tasks between columns to update status.
            </p>
        </div>

        <!-- Add Task Modal -->
        <Teleport to="body">
            <div v-if="addTaskModal" class="fixed inset-0 bg-black/40 backdrop-blur-[2px] z-[200]" @click="closeAddTaskModal()"></div>
            <div v-if="addTaskModal" class="fixed inset-0 flex items-center justify-center p-4 z-[201]" @keydown.escape.window="closeAddTaskModal()">
                <div @click.stop class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden" style="max-height: min(92vh, 680px)">

                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 flex-shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Task to Standup</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ date_label }}</p>
                        </div>
                        <button @click="closeAddTaskModal()" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="flex items-center border-b border-slate-100 dark:border-slate-700/60 px-5 flex-shrink-0">
                        <button @click="addTaskModalTab = 'existing'; fetchTasks(searchQuery)" class="px-1 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <i class="bi bi-search text-xs mr-1.5"></i>Find Existing
                        </button>
                        <div class="flex-1"></div>
                        <button v-if="projects?.length" @click="addTaskModalTab = 'create'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors mb-px"
                                :class="addTaskModalTab === 'create'
                                    ? 'bg-violet-600 text-white border-violet-600'
                                    : 'text-violet-600 dark:text-violet-400 border-violet-200 dark:border-violet-700 hover:bg-violet-50 dark:hover:bg-violet-900/20'">
                            <i class="bi bi-plus-lg text-xs"></i>Create New
                        </button>
                    </div>

                    <!-- Find existing tab -->
                    <div v-if="addTaskModalTab === 'existing'" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-50 dark:border-slate-700/40 flex-shrink-0">
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" v-model="searchQuery" @input="onSearchInput()" @keydown.escape="closeAddTaskModal()"
                                       placeholder="Search by task title…"
                                       class="w-full text-sm bg-slate-50 dark:bg-slate-800 rounded-xl pl-8 pr-9 py-2 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 placeholder-slate-400 outline-none focus:border-violet-400 transition-colors">
                                <span v-if="searching" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <i class="bi bi-arrow-repeat animate-spin text-slate-400 text-xs"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto px-2 py-2">
                            <div v-if="!searchResults.length && !searching" class="flex flex-col items-center justify-center py-10 text-center">
                                <i class="bi bi-clipboard text-3xl text-slate-200 dark:text-slate-700 mb-2"></i>
                                <p class="text-sm text-slate-500">{{ searchQuery.length >= 2 ? 'No tasks found' : 'Your open assigned tasks' }}</p>
                            </div>
                            <button v-for="task in searchResults" :key="task.id"
                                    @click="addExistingTask(task)"
                                    :disabled="items.some(i => i.task_id === task.id)"
                                    class="flex items-center gap-3 w-full px-3 py-3 rounded-xl text-left mb-0.5 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" :class="statusDot(task.status)"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ task.title }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span v-if="task.ticket_key" class="text-[10px] font-mono font-semibold text-violet-500 dark:text-violet-400">{{ task.ticket_key }}</span>
                                        <span v-if="task.ticket_key" class="text-slate-300 text-[10px]">·</span>
                                        <span class="text-xs text-slate-400">{{ task.project }}</span>
                                        <span class="text-slate-300 text-[10px]">·</span>
                                        <span class="text-xs text-slate-400 capitalize">{{ task.status.replace('_', ' ') }}</span>
                                    </div>
                                </div>
                                <div v-if="items.some(i => i.task_id === task.id)" class="text-xs text-violet-500 font-medium flex items-center gap-1">
                                    <i class="bi bi-check2 text-sm"></i> Added
                                </div>
                                <div v-else class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-500 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition-colors">
                                    <i class="bi bi-plus text-sm"></i>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Create new tab -->
                    <div v-if="addTaskModalTab === 'create' && projects?.length" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                            <input type="text" v-model="newTaskTitle" placeholder="Task title…"
                                   class="w-full text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 placeholder-slate-400 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 dark:focus:ring-violet-900/30 transition-all">

                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Type</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="t in taskTypes" :key="t.val" type="button"
                                            @click="newTaskType = t.val"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all"
                                            :class="newTaskType === t.val ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-violet-300'">
                                        <i class="bi text-xs" :class="[t.icon, newTaskType !== t.val ? t.color : '']"></i>
                                        {{ t.val.charAt(0).toUpperCase() + t.val.slice(1) }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Project *</p>
                                    <ProjectSelect v-model="newTaskProject" :projects="projects" placeholder="Select…" size="sm" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Priority</p>
                                    <select v-model="newTaskPriority" class="form-input text-sm">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Assignees</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="m in members" :key="m.id" type="button"
                                            @click="newTaskAssignees.includes(m.id) ? newTaskAssignees = newTaskAssignees.filter(id => id !== m.id) : newTaskAssignees.push(m.id)"
                                            class="flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-full border transition-all text-xs font-medium"
                                            :class="newTaskAssignees.includes(m.id)
                                                ? 'bg-violet-50 dark:bg-violet-900/30 border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300'
                                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 hover:border-violet-300'">
                                        <div class="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold"
                                             :style="newTaskAssignees.includes(m.id) ? 'background:#ede9fe;color:#7c3aed' : 'background:#f1f5f9;color:#94a3b8'">
                                            {{ m.name.substring(0,2).toUpperCase() }}
                                        </div>
                                        <span>{{ m.name.split(' ')[0] }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-3.5 border-t border-slate-100 dark:border-slate-700/60 flex flex-col gap-2.5 flex-shrink-0">
                            <button @click="createTask()" :disabled="!newTaskTitle.trim() || !newTaskProject || taskCreating"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                <i class="bi" :class="taskCreating ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'"></i>
                                {{ taskCreating ? 'Creating…' : 'Create & Add' }}
                            </button>
                            <button @click="closeAddTaskModal()" class="w-full py-2 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
