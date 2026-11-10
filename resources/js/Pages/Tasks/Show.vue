<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    task: Object,
    members: Array,
    activity: Array,
    can_update: Boolean,
    can_delete: Boolean,
})

const page = usePage()

// Status/Priority/Type options
const statusOptions = [
    { val: 'todo',        label: 'To Do',       icon: 'bi-circle',              chip: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' },
    { val: 'in_progress', label: 'In Progress',  icon: 'bi-arrow-right-circle-fill', chip: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    { val: 'qa_testing',  label: 'QA Testing',   icon: 'bi-bug-fill',            chip: 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300' },
    { val: 'in_review',   label: 'In Review',    icon: 'bi-eye-fill',            chip: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' },
    { val: 'done',        label: 'Done',          icon: 'bi-check-circle-fill',   chip: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' },
    { val: 'cancelled',   label: 'Cancelled',     icon: 'bi-x-circle-fill',       chip: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' },
]
const priorityOptions = [
    { val: 'low',      label: 'Low',      icon: 'bi-arrow-down-circle',       chip: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' },
    { val: 'medium',   label: 'Medium',   icon: 'bi-dash-circle',             chip: 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' },
    { val: 'high',     label: 'High',     icon: 'bi-arrow-up-circle',         chip: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' },
    { val: 'critical', label: 'Critical', icon: 'bi-exclamation-circle-fill', chip: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
]
const typeOptions = [
    { val: 'task',    label: 'Task',    icon: 'bi-check-square-fill', chip: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' },
    { val: 'bug',     label: 'Bug',     icon: 'bi-bug-fill',          chip: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
    { val: 'feature', label: 'Feature', icon: 'bi-stars',             chip: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' },
    { val: 'story',   label: 'Story',   icon: 'bi-bookmark-fill',     chip: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    { val: 'epic',    label: 'Epic',    icon: 'bi-lightning-fill',    chip: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300' },
]

// Reactive local state mirroring task
const title       = ref(props.task.title)
const description = ref(props.task.description ?? '')
const status      = ref(props.task.status)
const priority    = ref(props.task.priority)
const taskType    = ref(props.task.type)
const dueDate     = ref(props.task.due_date ?? '')
const assignees   = ref([...props.task.assignees])
const availableMembers = computed(() => props.members.filter(m => !assignees.value.find(a => a.id === m.id)))

// Editing states
const editingTitle = ref(false)
const editingDesc  = ref(false)
const statusOpen   = ref(false)
const priorityOpen = ref(false)
const typeOpen     = ref(false)
const editingDue   = ref(false)
const assigneeOpen = ref(false)
const addingSubtask = ref(false)
const newSubtaskTitle = ref('')

// Computed current option
const currentStatus   = computed(() => statusOptions.find(o => o.val === status.value) ?? statusOptions[0])
const currentPriority = computed(() => priorityOptions.find(o => o.val === priority.value) ?? priorityOptions[1])
const currentType     = computed(() => typeOptions.find(o => o.val === taskType.value) ?? typeOptions[0])

// Inline PATCH helper
async function patch(data) {
    return fetch(props.task.update_url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    })
}

async function saveTitle() {
    if (!title.value.trim()) { title.value = props.task.title; editingTitle.value = false; return }
    if (title.value === props.task.title) { editingTitle.value = false; return }
    await patch({ title: title.value })
    editingTitle.value = false
}

async function saveDesc() {
    if (description.value === (props.task.description ?? '')) { editingDesc.value = false; return }
    await patch({ description: description.value })
    editingDesc.value = false
}

async function setStatus(val) {
    status.value = val; statusOpen.value = false
    await fetch(props.task.status_url, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ status: val }),
    })
}

async function setPriority(val) {
    priority.value = val; priorityOpen.value = false
    await patch({ priority: val })
}

async function setType(val) {
    taskType.value = val; typeOpen.value = false
    await patch({ type: val })
}

async function saveDue() {
    editingDue.value = false
    await patch({ due_date: dueDate.value || null })
}

async function addAssignee(member) {
    assigneeOpen.value = false
    const r = await fetch(props.task.assignees_add_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ user_id: member.id }),
    })
    if (r.ok) assignees.value.push({ id: member.id, name: member.name, avatar_url: member.avatar_url })
}

async function removeAssignee(member) {
    const r = await fetch(props.task.assignees_remove_base + '/' + member.id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    })
    if (r.ok) assignees.value = assignees.value.filter(a => a.id !== member.id)
}

// Comments
const commentBody  = ref('')
const postingComment = ref(false)
async function postComment() {
    if (!commentBody.value.trim() || postingComment.value) return
    postingComment.value = true
    await fetch(props.task.comments_store_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ body: commentBody.value, commentable_type: 'task', commentable_id: props.task.id }),
    })
    commentBody.value = ''
    postingComment.value = false
    router.reload({ only: ['task'] })
}

// Subtasks
async function addSubtask() {
    if (!newSubtaskTitle.value.trim()) return
    const r = await fetch(props.task.project.store_subtask_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        body: JSON.stringify({
            title: newSubtaskTitle.value,
            project_id: props.task.project.id,
            column_id: props.task.column_id,
            parent_id: props.task.id,
            type: props.task.type,
            priority: props.task.priority ?? 'medium',
        }),
    })
    if (r.ok) {
        newSubtaskTitle.value = ''
        addingSubtask.value = false
        router.reload({ only: ['task'] })
    }
}

// Subtasks progress
const subtaskDone  = computed(() => props.task.subtasks.filter(s => s.status === 'done').length)
const subtaskTotal = computed(() => props.task.subtasks.length)
const subtaskPct   = computed(() => subtaskTotal.value > 0 ? Math.round(subtaskDone.value / subtaskTotal.value * 100) : 0)

// Due date display
const dueDateDisplay = computed(() => {
    if (!dueDate.value) return ''
    const d = new Date(dueDate.value + 'T00:00:00')
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
})

// Time tracker
import { onMounted, onUnmounted } from 'vue'
const timerRunning  = ref(false)
const timerElapsed  = ref('00:00:00')
const timerEntryId  = ref(null)
const timerStarted  = ref(null)
let timerInterval   = null
const totalLogged   = ref(props.task.total_logged_minutes)
const timeEntries   = ref([...props.task.time_entries])

function tickTimer() {
    const sec = Math.floor((Date.now() - timerStarted.value) / 1000)
    const h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60
    timerElapsed.value = [h, m, s].map(n => String(n).padStart(2, '0')).join(':')
}

onMounted(async () => {
    try {
        const r = await fetch('/time-entries/running', { headers: { Accept: 'application/json' } })
        if (r.ok) {
            const d = await r.json()
            if (d?.entry?.task_id === props.task.id) {
                timerRunning.value = true
                timerEntryId.value = d.entry.id
                timerStarted.value = new Date(d.entry.started_at).getTime()
                timerInterval = setInterval(tickTimer, 1000)
            }
        }
    } catch {}
})

onUnmounted(() => { if (timerInterval) clearInterval(timerInterval) })

const totalFormatted = computed(() => {
    const h = Math.floor(totalLogged.value / 60), m = totalLogged.value % 60
    return (h > 0 ? h + 'h ' : '') + (m > 0 ? m + 'm' : '0m')
})

async function toggleTimer() {
    const tok = document.querySelector('meta[name=csrf-token]').content
    if (timerRunning.value) {
        const r = await fetch('/time-entries/' + timerEntryId.value + '/stop', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': tok, Accept: 'application/json' },
        })
        if (r.ok) {
            timerRunning.value = false
            clearInterval(timerInterval); timerInterval = null
            const sec = Math.floor((Date.now() - timerStarted.value) / 1000)
            const mins = Math.round(sec / 60)
            totalLogged.value += mins
            timeEntries.value.unshift({ id: timerEntryId.value, started: 'just now', duration: (Math.floor(mins/60)>0?Math.floor(mins/60)+'h ':'')+(mins%60)+'m', user: page.props.auth?.user?.name ?? '' })
            timerElapsed.value = '00:00:00'; timerEntryId.value = null
        }
    } else {
        const r = await fetch('/time-entries/start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tok, Accept: 'application/json' },
            body: JSON.stringify({ task_id: props.task.id }),
        })
        if (r.ok) {
            const d = await r.json()
            timerRunning.value = true
            timerEntryId.value = d.entry.id
            timerStarted.value = new Date(d.entry.started_at).getTime()
            timerInterval = setInterval(tickTimer, 1000)
        }
    }
}

// Delete task
function deleteTask() {
    if (!confirm('Delete this task? This cannot be undone.')) return
    router.delete(props.task.destroy_url)
}

// Mark done
function markDone() {
    router.patch(props.task.update_url, { status: 'done' }, { preserveScroll: true })
}

// Severity styles
const sevCls = {
    blocker:  'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    critical: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    major:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    minor:    'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    trivial:  'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
}
const sevIcon = { blocker: '🔴', critical: '🟠', major: '🟡', minor: '🟢', trivial: '⚪' }

// Type background for badge
function typeBg(type) {
    const map = { bug: 'bg-rose-100 dark:bg-rose-900/30', feature: 'bg-purple-100 dark:bg-purple-900/30', story: 'bg-blue-100 dark:bg-blue-900/30', epic: 'bg-orange-100 dark:bg-orange-900/30' }
    return map[type] ?? 'bg-slate-100 dark:bg-slate-800'
}
</script>

<template>
    <AppLayout :title="task.title">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-4 flex-wrap">
            <Link :href="task.project.show_url" class="hover:text-violet-600 transition-colors font-medium">{{ task.project.name }}</Link>
            <i class="bi bi-chevron-right text-[9px]"></i>
            <Link :href="route('projects.tasks.index', task.project.id)" class="hover:text-violet-600 transition-colors">Tasks</Link>
            <i class="bi bi-chevron-right text-[9px]"></i>
            <span class="font-mono text-[11px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md text-slate-500">{{ task.ticket_key }}</span>
        </div>

        <!-- Parent task reference (subtask only) -->
        <div v-if="task.parent" class="flex items-center gap-2 mb-4 text-sm">
            <i class="bi bi-diagram-2 text-slate-400"></i>
            <span class="text-slate-500">Part of:</span>
            <Link :href="task.parent.show_url" class="inline-flex items-center gap-1.5 font-medium text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 transition-colors">
                <span class="font-mono text-xs bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 px-1.5 py-0.5 rounded">{{ task.parent.ticket_key }}</span>
                {{ task.parent.title }}
            </Link>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

            <!-- MAIN COLUMN -->
            <div class="space-y-4 min-w-0">

                <!-- Title card -->
                <div class="card p-5 pb-4">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" :class="typeBg(task.type)">
                            <i class="bi text-sm" :class="[task.type_icon, task.type_color]"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <h1 v-if="!editingTitle"
                                @click="can_update && (editingTitle = true)"
                                class="text-xl font-bold text-slate-900 dark:text-white leading-snug"
                                :class="can_update ? 'cursor-text hover:bg-slate-50 dark:hover:bg-slate-800/60 rounded-lg px-1.5 py-0.5 -mx-1.5 transition-colors' : ''">
                                {{ title }}
                            </h1>
                            <input v-else v-model="title" ref="titleInput"
                                   @blur="saveTitle" @keydown.enter.prevent="saveTitle" @keydown.escape="editingTitle=false; title=task.title"
                                   class="w-full text-xl font-bold text-slate-900 dark:text-white bg-white dark:bg-slate-900 border border-violet-400 rounded-lg px-2 py-0.5 outline-none shadow-sm leading-snug -mx-0.5">
                            <div class="flex items-center gap-2.5 mt-2 flex-wrap text-xs text-slate-400">
                                <span class="capitalize">{{ task.type }}</span>
                                <span class="text-slate-200 dark:text-slate-700">·</span>
                                <span v-if="task.creator">by <strong class="text-slate-600 dark:text-slate-300">{{ task.creator.name }}</strong></span>
                                <span class="text-slate-200 dark:text-slate-700">·</span>
                                <span>{{ task.created_at }}</span>
                                <template v-if="task.sprint">
                                    <span class="text-slate-200 dark:text-slate-700">·</span>
                                    <Link :href="task.sprint.show_url" class="inline-flex items-center gap-1 text-violet-500 hover:underline">
                                        <i class="bi bi-speedometer2"></i> {{ task.sprint.name }}
                                    </Link>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                            <button v-if="task.status !== 'done'" @click="markDone"
                                    class="btn-sm inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                                <i class="bi bi-check-lg"></i> Done
                            </button>
                            <Link v-if="can_update" :href="task.edit_url"
                                  class="btn-sm inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                <i class="bi bi-pencil"></i> Edit
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <i class="bi bi-text-paragraph"></i> Description
                        </h3>
                        <button v-if="can_update && !editingDesc" @click="editingDesc=true"
                                class="text-xs text-slate-400 hover:text-violet-600 transition-colors flex items-center gap-1">
                            <i class="bi bi-pencil text-[10px]"></i> Edit
                        </button>
                    </div>
                    <div v-if="!editingDesc">
                        <div v-if="description" class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ description }}</div>
                        <p v-else class="text-sm text-slate-400 italic" :class="can_update ? 'cursor-text' : ''" @click="can_update && (editingDesc=true)">
                            {{ can_update ? 'Click to add a description…' : 'No description.' }}
                        </p>
                    </div>
                    <div v-else class="space-y-2">
                        <textarea v-model="description" ref="descInput" rows="5"
                                  placeholder="Add a description…"
                                  class="w-full form-input resize-none text-sm leading-relaxed"></textarea>
                        <div class="flex gap-2">
                            <button @click="saveDesc" class="px-3 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors">Save</button>
                            <button @click="editingDesc=false; description=task.description??''" class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 rounded-lg transition-colors">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Bug Report -->
                <div v-if="task.type === 'bug'" class="card p-5 border-l-4 border-l-rose-400">
                    <h3 class="font-semibold text-rose-700 dark:text-rose-400 mb-4 flex items-center gap-2">
                        <i class="bi bi-bug-fill"></i> Bug Report
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Severity</span>
                            <span v-if="task.severity" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold capitalize w-fit"
                                  :class="sevCls[task.severity] ?? sevCls.trivial">
                                {{ sevIcon[task.severity] ?? '' }} {{ task.severity }}
                            </span>
                            <Link v-else :href="task.edit_url" class="text-sm text-slate-400 hover:text-violet-500 italic">Not set — add severity</Link>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Environment</span>
                            <span v-if="task.environment" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 w-fit">
                                <i class="bi bi-display text-slate-400 text-xs"></i> {{ task.environment }}
                            </span>
                            <Link v-else :href="task.edit_url" class="text-sm text-slate-400 hover:text-violet-500 italic">Not set</Link>
                        </div>
                    </div>
                    <div v-if="task.steps_to_reproduce" class="mb-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Steps to Reproduce</p>
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-4 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap border border-slate-200 dark:border-slate-700 font-mono leading-relaxed">{{ task.steps_to_reproduce }}</div>
                    </div>
                    <div v-if="task.expected_behavior || task.actual_behavior" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-1.5 flex items-center gap-1.5 uppercase tracking-wide"><i class="bi bi-check-circle-fill"></i> Expected</p>
                            <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-xl p-3.5 text-sm text-slate-700 dark:text-slate-300 border border-emerald-200 dark:border-emerald-900 leading-relaxed">{{ task.expected_behavior || '—' }}</div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mb-1.5 flex items-center gap-1.5 uppercase tracking-wide"><i class="bi bi-x-circle-fill"></i> Actual</p>
                            <div class="bg-rose-50 dark:bg-rose-900/10 rounded-xl p-3.5 text-sm text-slate-700 dark:text-slate-300 border border-rose-200 dark:border-rose-900 leading-relaxed">{{ task.actual_behavior || '—' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Subtasks -->
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                            <i class="bi bi-diagram-2 text-slate-400 text-xs"></i>
                            Subtasks
                            <span v-if="subtaskTotal" class="text-xs font-normal text-slate-400">{{ subtaskDone }}/{{ subtaskTotal }}</span>
                        </h3>
                        <button v-if="can_update" @click="addingSubtask = !addingSubtask"
                                class="text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-1 font-medium">
                            <i class="bi bi-plus-lg text-sm"></i> Add subtask
                        </button>
                    </div>
                    <div v-if="subtaskTotal" class="px-5 pt-3 pb-1">
                        <div class="h-1 bg-slate-100 dark:bg-slate-800 rounded-full">
                            <div class="h-1 bg-violet-400 rounded-full transition-all" :style="'width:' + subtaskPct + '%'"></div>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800/60">
                        <div v-for="sub in task.subtasks" :key="sub.id"
                             class="flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <div class="w-4 h-4 rounded-full border-2 flex-shrink-0 flex items-center justify-center"
                                 :class="sub.status === 'done' ? 'bg-emerald-400 border-emerald-400' : 'border-slate-300 dark:border-slate-600'">
                                <i v-if="sub.status === 'done'" class="bi bi-check text-white text-[9px]"></i>
                            </div>
                            <Link :href="sub.show_url"
                                  class="flex-1 text-sm hover:text-violet-600 transition-colors"
                                  :class="sub.status === 'done' ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300'">
                                {{ sub.title }}
                            </Link>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 capitalize">
                                {{ sub.status.replace('_', ' ') }}
                            </span>
                        </div>
                        <div v-if="!subtaskTotal" class="px-5 py-4 text-center">
                            <p class="text-sm text-slate-400">No subtasks yet</p>
                        </div>
                    </div>
                    <div v-if="can_update && addingSubtask" class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                        <div class="flex items-center gap-2">
                            <input v-model="newSubtaskTitle" ref="subInput" placeholder="Subtask title…"
                                   @keydown.enter.prevent="addSubtask" @keydown.escape="addingSubtask=false; newSubtaskTitle=''"
                                   class="flex-1 form-input text-sm py-1.5">
                            <button @click="addSubtask" :disabled="!newSubtaskTitle.trim()"
                                    class="px-3 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors disabled:opacity-40">Add</button>
                            <button @click="addingSubtask=false; newSubtaskTitle=''"
                                    class="px-2 py-1.5 text-xs text-slate-400 hover:text-slate-600 rounded-lg transition-colors">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Comments -->
                <div class="card overflow-hidden" id="comments">
                    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                            <i class="bi bi-chat text-slate-400 text-xs"></i>
                            Comments
                            <span v-if="task.comments.length" class="text-xs font-normal text-slate-400">({{ task.comments.length }})</span>
                        </h3>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800">
                        <div v-for="c in task.comments" :key="c.id" class="p-5 flex gap-3">
                            <img :src="c.user?.avatar_url ?? '/images/default-avatar.png'" class="w-8 h-8 rounded-full flex-shrink-0 object-cover">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="font-semibold text-sm text-slate-900 dark:text-white">{{ c.user?.name ?? '(deleted user)' }}</span>
                                    <span class="text-xs text-slate-400">{{ c.created_at }}</span>
                                </div>
                                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{{ c.body }}</p>
                            </div>
                        </div>
                        <div v-if="!task.comments.length" class="py-10 text-center">
                            <i class="bi bi-chat text-3xl text-slate-200 dark:text-slate-700 block mb-2"></i>
                            <p class="text-sm text-slate-400">No comments yet — start the conversation</p>
                        </div>
                    </div>
                    <div class="p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                        <div class="flex gap-3">
                            <img :src="$page.props.auth?.user?.avatar_url" class="w-8 h-8 rounded-full flex-shrink-0 object-cover">
                            <div class="flex-1">
                                <textarea v-model="commentBody" rows="2" placeholder="Add a comment… (Ctrl+Enter to post)"
                                          class="form-input resize-none w-full text-sm"
                                          @keydown.ctrl.enter.prevent="postComment"></textarea>
                                <div class="flex justify-end mt-2">
                                    <button @click="postComment" :disabled="!commentBody.trim() || postingComment" class="btn-primary btn-sm disabled:opacity-40">
                                        <i class="bi" :class="postingComment ? 'bi-hourglass-split' : 'bi-send'"></i>
                                        <span>{{ postingComment ? 'Posting…' : 'Post' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attachments -->
                <div class="card overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                            <i class="bi bi-paperclip text-slate-400 text-xs"></i>
                            Attachments
                            <span class="text-xs font-normal text-slate-400">({{ task.attachments.length }})</span>
                        </h3>
                        <label class="btn-ghost btn-sm cursor-pointer text-xs">
                            <i class="bi bi-plus-lg text-sm"></i> Add
                            <input type="file" class="sr-only" multiple @change="uploadFiles($event.target.files)">
                        </label>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800/60">
                        <div v-for="att in task.attachments" :key="att.id"
                             class="flex items-center gap-3 px-5 py-3 group hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                 :class="att.is_image ? 'bg-sky-100 dark:bg-sky-900/30' : att.mime_type?.includes('pdf') ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-slate-100 dark:bg-slate-800'">
                                <i class="text-sm" :class="att.is_image ? 'bi bi-image text-sky-500' : att.is_video ? 'bi bi-play-circle-fill text-violet-500' : att.mime_type?.includes('pdf') ? 'bi bi-file-earmark-pdf text-rose-500' : 'bi bi-file-earmark text-slate-400'"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ att.original_name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ att.size_formatted }} · {{ att.uploader_name }} · {{ att.created_ago }}</p>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a :href="att.url" target="_blank" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500" title="Download"><i class="bi bi-download text-sm"></i></a>
                                <button @click="deleteAttachment(att)" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 text-slate-400 hover:text-rose-500" title="Delete"><i class="bi bi-trash3 text-sm"></i></button>
                            </div>
                        </div>
                        <div v-if="!task.attachments.length" class="px-5 py-6 text-center">
                            <p class="text-xs text-slate-400">No attachments yet</p>
                        </div>
                    </div>
                </div>

                <!-- Activity -->
                <div v-if="activity.length" class="card overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                            <i class="bi bi-clock-history text-slate-400 text-xs"></i> Activity
                        </h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="log in activity" :key="log.id" class="flex gap-3">
                            <div class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="bi bi-circle-fill text-[5px] text-slate-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-700 dark:text-slate-300">
                                    <span v-if="log.causer" class="font-semibold">{{ log.causer.name }}</span> {{ log.description }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ log.created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="space-y-3">

                <!-- Details card -->
                <div class="card divide-y divide-slate-100 dark:divide-slate-800" style="overflow:visible">
                    <div class="px-4 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Details</p>
                    </div>

                    <!-- STATUS -->
                    <div class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-circle-half text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Status</span>
                        <div class="relative flex-1">
                            <button @click="can_update && (statusOpen = !statusOpen)"
                                    @click.outside="statusOpen = false"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors max-w-full"
                                    :class="[currentStatus.chip, can_update ? 'hover:opacity-90 cursor-pointer' : 'cursor-default']">
                                <i class="bi text-[10px] flex-shrink-0" :class="currentStatus.icon"></i>
                                <span class="truncate">{{ currentStatus.label }}</span>
                                <i v-if="can_update" class="bi bi-chevron-down text-[9px] opacity-50 flex-shrink-0"></i>
                            </button>
                            <div v-if="can_update && statusOpen"
                                 class="absolute left-0 top-full mt-1 z-50 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5 overflow-hidden">
                                <button v-for="opt in statusOptions" :key="opt.val" @click="setStatus(opt.val)"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="opt.chip">
                                        <i class="bi text-[10px]" :class="opt.icon"></i>
                                    </span>
                                    <span class="flex-1">{{ opt.label }}</span>
                                    <i v-if="opt.val === status" class="bi bi-check text-violet-500 text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PRIORITY -->
                    <div class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-flag text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Priority</span>
                        <div class="relative flex-1">
                            <button @click="can_update && (priorityOpen = !priorityOpen)"
                                    @click.outside="priorityOpen = false"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                                    :class="[currentPriority.chip, can_update ? 'hover:opacity-90 cursor-pointer' : 'cursor-default']">
                                <i class="bi text-[10px]" :class="currentPriority.icon"></i>
                                <span>{{ currentPriority.label }}</span>
                                <i v-if="can_update" class="bi bi-chevron-down text-[9px] opacity-50 ml-0.5"></i>
                            </button>
                            <div v-if="can_update && priorityOpen"
                                 class="absolute left-0 top-full mt-1 z-50 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                                <button v-for="opt in priorityOptions" :key="opt.val" @click="setPriority(opt.val)"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="opt.chip">
                                        <i class="bi text-[10px]" :class="opt.icon"></i>
                                    </span>
                                    <span>{{ opt.label }}</span>
                                    <i v-if="opt.val === priority" class="bi bi-check text-violet-500 text-xs ml-auto"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TYPE -->
                    <div class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-tag text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Type</span>
                        <div class="relative flex-1">
                            <button @click="can_update && (typeOpen = !typeOpen)"
                                    @click.outside="typeOpen = false"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                                    :class="[currentType.chip, can_update ? 'hover:opacity-90 cursor-pointer' : 'cursor-default']">
                                <i class="bi text-[10px]" :class="currentType.icon"></i>
                                <span>{{ currentType.label }}</span>
                                <i v-if="can_update" class="bi bi-chevron-down text-[9px] opacity-50 ml-0.5"></i>
                            </button>
                            <div v-if="can_update && typeOpen"
                                 class="absolute left-0 top-full mt-1 z-50 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                                <button v-for="opt in typeOptions" :key="opt.val" @click="setType(opt.val)"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="opt.chip">
                                        <i class="bi text-[10px]" :class="opt.icon"></i>
                                    </span>
                                    <span>{{ opt.label }}</span>
                                    <i v-if="opt.val === taskType" class="bi bi-check text-violet-500 text-xs ml-auto"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DUE DATE -->
                    <div class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-calendar3 text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Due</span>
                        <div class="flex-1">
                            <div v-if="!editingDue">
                                <button v-if="dueDateDisplay" @click="can_update && (editingDue = true)"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium transition-colors"
                                        :class="[task.is_overdue ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200', can_update ? 'hover:text-violet-600 cursor-pointer' : 'cursor-default']">
                                    <span>{{ dueDateDisplay }}</span>
                                    <span v-if="task.is_overdue" class="text-[10px] font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-600 px-1.5 py-0.5 rounded">Overdue</span>
                                </button>
                                <button v-else @click="can_update && (editingDue = true)"
                                        class="text-xs text-slate-400 italic"
                                        :class="can_update ? 'hover:text-violet-500 cursor-pointer' : 'cursor-default'">
                                    {{ can_update ? 'Set date' : '—' }}
                                </button>
                            </div>
                            <div v-else class="flex items-center gap-1.5">
                                <DatePicker v-model="dueDate" @update:modelValue="saveDue" />
                                <button v-if="dueDate" @click="dueDate=''; saveDue()" class="text-slate-400 hover:text-rose-500 transition-colors flex-shrink-0">
                                    <i class="bi bi-x-circle text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ASSIGNEES -->
                    <div class="px-4 py-2.5 flex items-start gap-3">
                        <i class="bi bi-person text-[10px] text-slate-400 w-3 text-center flex-shrink-0 mt-0.5"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0 mt-0.5">Assigned</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap gap-1.5 mb-1">
                                <span v-if="!assignees.length" class="text-xs text-slate-400 italic">Unassigned</span>
                                <div v-for="a in assignees" :key="a.id" class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 rounded-full px-2 py-0.5 group">
                                    <img :src="a.avatar_url" class="w-4 h-4 rounded-full object-cover flex-shrink-0">
                                    <span class="text-xs text-slate-700 dark:text-slate-300 max-w-[80px] truncate">{{ a.name }}</span>
                                    <button v-if="can_update" @click="removeAssignee(a)" class="opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 hover:text-rose-500 leading-none">
                                        <i class="bi bi-x text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-if="can_update" class="relative inline-block">
                                <button @click="assigneeOpen = !assigneeOpen" @click.outside="assigneeOpen = false"
                                        class="text-[10px] font-medium text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-0.5">
                                    <i class="bi bi-plus-circle text-xs"></i> Add
                                </button>
                                <div v-if="assigneeOpen"
                                     class="absolute left-0 top-5 z-30 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-1.5 max-h-48 overflow-y-auto">
                                    <p v-if="!availableMembers.length" class="text-xs text-slate-400 text-center py-3">All members assigned</p>
                                    <button v-for="u in availableMembers" :key="u.id" @click="addAssignee(u)"
                                            class="flex items-center gap-2.5 w-full px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <img :src="u.avatar_url" class="w-5 h-5 rounded-full flex-shrink-0 object-cover">
                                        <span class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ u.name }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LABELS -->
                    <div v-if="task.labels.length" class="px-4 py-2.5 flex items-start gap-3">
                        <i class="bi bi-bookmark text-[10px] text-slate-400 w-3 text-center flex-shrink-0 mt-0.5"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0 mt-0.5">Labels</span>
                        <div class="flex-1 flex flex-wrap gap-1.5">
                            <span v-for="l in task.labels" :key="l.id"
                                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold text-white"
                                  :style="{ backgroundColor: l.color }">{{ l.name }}</span>
                        </div>
                    </div>

                    <!-- SPRINT -->
                    <div v-if="task.sprint" class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-speedometer2 text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Sprint</span>
                        <Link :href="task.sprint.show_url" class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline truncate flex-1">
                            {{ task.sprint.name }}
                        </Link>
                    </div>

                    <!-- ESTIMATES -->
                    <div v-if="task.estimated_hours || task.story_points" class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-clock text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Estimates</span>
                        <div class="flex gap-3">
                            <span v-if="task.estimated_hours" class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ task.estimated_hours }}h</span>
                            <span v-if="task.story_points" class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ task.story_points }} pts</span>
                        </div>
                    </div>

                    <!-- PROJECT -->
                    <div class="px-4 py-2.5 flex items-center gap-3">
                        <i class="bi bi-folder2-open text-[10px] text-slate-400 w-3 text-center flex-shrink-0"></i>
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide w-20 flex-shrink-0">Project</span>
                        <Link :href="task.project.show_url" class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline truncate flex-1">
                            {{ task.project.name }}
                        </Link>
                    </div>
                </div>

                <!-- TIME TRACKER -->
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                            <i class="bi bi-stopwatch text-violet-500"></i> Time Tracker
                        </span>
                        <div class="flex items-center gap-2">
                            <span v-if="timerRunning" class="font-mono text-base font-bold tabular-nums tracking-wider text-rose-500">{{ timerElapsed }}</span>
                            <span v-else class="font-mono text-base font-bold tabular-nums tracking-wider text-slate-400">00:00:00</span>
                            <span v-if="timerRunning" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse flex-shrink-0"></span>
                        </div>
                    </div>
                    <div class="p-4">
                        <button @click="toggleTimer"
                                class="w-full flex items-center justify-center gap-2.5 py-3 rounded-xl text-sm font-bold transition-all"
                                :class="timerRunning ? 'bg-rose-500 hover:bg-rose-600 text-white' : 'bg-violet-600 hover:bg-violet-700 text-white'">
                            <i class="bi text-base" :class="timerRunning ? 'bi-stop-circle-fill' : 'bi-play-circle-fill'"></i>
                            <span>{{ timerRunning ? 'Stop Tracking' : 'Start Tracking' }}</span>
                        </button>
                        <div class="flex items-center gap-2 mt-3 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                            <i class="bi bi-clock-history text-xs text-violet-500"></i>
                            <span class="text-xs text-slate-500">Total logged</span>
                            <span class="ml-auto text-xs font-bold text-slate-900 dark:text-white">{{ totalFormatted }}</span>
                        </div>
                    </div>
                    <div class="border-t border-slate-100 dark:border-slate-800">
                        <div class="px-4 py-2">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Sessions</span>
                        </div>
                        <div v-if="!timeEntries.length" class="px-4 pb-4 text-center">
                            <p class="text-xs text-slate-400">No sessions yet</p>
                        </div>
                        <div class="divide-y divide-slate-50 dark:divide-slate-800/60 max-h-36 overflow-y-auto">
                            <div v-for="e in timeEntries" :key="e.id" class="flex items-center gap-2.5 px-4 py-2">
                                <div class="w-5 h-5 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-play-fill text-violet-500 text-[8px]"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ e.duration }}</p>
                                    <p class="text-[10px] text-slate-400 truncate">{{ e.user ? e.user + ' · ' : '' }}{{ e.started }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete -->
                <button v-if="can_delete" @click="deleteTask"
                        class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-white bg-rose-500 hover:bg-rose-600 dark:bg-rose-600 dark:hover:bg-rose-700 transition-all">
                    <i class="bi bi-trash3 text-sm"></i> Delete task
                </button>
            </div>
        </div>
    </AppLayout>
</template>
