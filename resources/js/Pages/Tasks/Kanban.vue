<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    project: Object,
    columns: Array,
    members: Array,
    labels: Array,
})

// Drag state
const dragTaskId = ref(null)
const dragOver   = ref(null)

function dragStart(taskId) { dragTaskId.value = taskId }
function dragEnter(colId)  { dragOver.value = colId }
function dragLeave()       {}

async function drop(colId, position) {
    dragOver.value = null
    if (!dragTaskId.value) return
    // Find the task across columns
    let task = null
    for (const col of props.columns) {
        task = col.tasks.find(t => t.id === dragTaskId.value)
        if (task) break
    }
    if (!task) return
    router.patch(task.move_url, { column_id: colId, position }, { preserveScroll: true, preserveState: false })
    dragTaskId.value = null
}

// Priority badge
const priorityBadge = {
    low:      'bg-slate-100 text-slate-500 dark:bg-slate-800',
    medium:   'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
    high:     'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    critical: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
}

// New Task panel
const panelOpen   = ref(false)
const panelColId  = ref(null)
const taskForm    = useForm({
    title: '',
    type: 'task',
    priority: 'medium',
    column_id: null,
    assignee_ids: [],
    label_ids: [],
    due_date: '',
    estimated_hours: '',
    description: '',
})

function openPanel(colId) {
    taskForm.reset()
    taskForm.column_id = colId ?? (props.columns[0]?.id ?? null)
    panelOpen.value = true
}

function toggleAssignee(id) {
    const i = taskForm.assignee_ids.indexOf(id)
    i === -1 ? taskForm.assignee_ids.push(id) : taskForm.assignee_ids.splice(i, 1)
}

function toggleLabel(id) {
    const i = taskForm.label_ids.indexOf(id)
    i === -1 ? taskForm.label_ids.push(id) : taskForm.label_ids.splice(i, 1)
}

function saveTask() {
    if (!taskForm.title.trim()) return
    taskForm.post(props.project.store_url, {
        preserveScroll: true,
        onSuccess: () => { panelOpen.value = false; taskForm.reset() },
    })
}

// Board settings
const settingsOpen = ref(false)
const boardCols    = ref(props.columns.map(c => ({ ...c })))
const newColName   = ref('')
const newColColor  = ref('#7c3aed')
const newColWip    = ref('')

async function addColumn() {
    if (!newColName.value.trim()) return
    const r = await fetch('/projects/' + props.project.id + '/columns', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
        body: JSON.stringify({ name: newColName.value, color: newColColor.value, wip_limit: newColWip.value || null }),
    })
    if (r.ok) {
        const d = await r.json()
        boardCols.value.push(d.column)
        newColName.value = ''; newColColor.value = '#7c3aed'; newColWip.value = ''
    }
}

async function updateColumn(col) {
    await fetch('/columns/' + col.id, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
        body: JSON.stringify({ name: col.name, color: col.color, wip_limit: col.wip_limit || null }),
    })
}

async function deleteColumn(col) {
    if (!confirm('Delete column? Tasks will be moved to the first column.')) return
    const r = await fetch('/columns/' + col.id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    })
    if (r.ok) boardCols.value = boardCols.value.filter(c => c.id !== col.id)
}

function memberName(id) { return props.members.find(m => m.id === id)?.name ?? '' }
</script>

<template>
    <AppLayout :title="project.name + ' — Board'">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <Link :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</Link>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Board</span>
                </div>
                <h1 class="page-title">Kanban Board</h1>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="tab-bar">
                    <Link :href="project.index_url" class="tab-item text-xs px-2.5 py-1.5">
                        <i class="bi bi-list-task"></i> <span class="hidden sm:inline">List</span>
                    </Link>
                    <Link :href="route('projects.tasks.kanban', project.id)" class="tab-item active text-xs px-2.5 py-1.5">
                        <i class="bi bi-kanban"></i> <span class="hidden sm:inline">Board</span>
                    </Link>
                    <Link :href="project.calendar_url" class="tab-item text-xs px-2.5 py-1.5">
                        <i class="bi bi-calendar3"></i> <span class="hidden sm:inline">Calendar</span>
                    </Link>
                    <Link :href="project.backlog_url" class="tab-item text-xs px-2.5 py-1.5">
                        <i class="bi bi-inbox"></i> <span class="hidden sm:inline">Backlog</span>
                    </Link>
                </div>
                <button @click="settingsOpen = true" class="btn-ghost btn-sm p-2">
                    <i class="bi bi-gear text-slate-500"></i>
                </button>
                <button @click="openPanel(null)" class="btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Task
                </button>
            </div>
        </div>

        <!-- Board -->
        <div class="flex gap-3 overflow-x-auto pb-6 -mx-4 lg:-mx-6 px-4 lg:px-6 min-h-[calc(100vh-13rem)] items-stretch">
            <div v-for="col in columns" :key="col.id"
                 class="flex flex-col flex-shrink-0 w-[280px] rounded-2xl bg-slate-100/70 dark:bg-slate-800/50 backdrop-blur-sm border border-slate-200/60 dark:border-slate-700/50 overflow-hidden"
                 @dragover.prevent="dragEnter(col.id)"
                 @drop.prevent="drop(col.id, col.tasks.length)"
                 :class="{ 'ring-2 ring-inset ring-violet-400 dark:ring-violet-600': dragOver === col.id }">

                <!-- Column Header -->
                <div class="flex items-center justify-between px-3 py-3 flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: col.color }"></div>
                        <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ col.name }}</span>
                        <span class="min-w-[20px] h-5 bg-white dark:bg-slate-700 rounded-full text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center justify-center px-1.5 shadow-sm">
                            {{ col.tasks.length }}
                        </span>
                    </div>
                    <span v-if="col.wip_limit"
                          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md"
                          :class="col.tasks.length >= col.wip_limit ? 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30' : 'text-slate-400 bg-slate-200 dark:bg-slate-700'">
                        {{ col.tasks.length }}/{{ col.wip_limit }}
                    </span>
                </div>

                <!-- Task Cards -->
                <div class="flex-1 overflow-y-auto px-2.5 pb-2.5 space-y-2">
                    <div v-for="task in col.tasks" :key="task.id"
                         class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm cursor-pointer p-3 hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700/60 hover:-translate-y-px transition-all duration-150 group"
                         draggable="true"
                         @click="router.visit(task.show_url)"
                         @dragstart="dragStart(task.id); $event.target.classList.add('opacity-50','rotate-1')"
                         @dragend="$event.target.classList.remove('opacity-50','rotate-1')">

                        <!-- Type + ticket key + labels -->
                        <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                            <i v-if="task.type" class="bi text-xs" :class="[task.type_icon, task.type_color]"></i>
                            <span v-if="task.ticket_key" class="font-mono text-[9px] text-slate-400">{{ task.ticket_key }}</span>
                        </div>

                        <p class="font-medium text-sm text-slate-900 dark:text-white leading-snug mb-2.5">{{ task.title }}</p>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[9px] px-1.5 py-0.5 rounded-full font-semibold capitalize" :class="priorityBadge[task.priority] ?? priorityBadge.medium">
                                    {{ task.priority }}
                                </span>
                                <span v-if="task.due_date" class="text-[10px]" :class="task.is_overdue ? 'text-rose-500 font-medium' : 'text-slate-400'">
                                    <i class="bi bi-calendar3 mr-0.5"></i>{{ task.due_date }}
                                </span>
                            </div>
                            <div class="flex -space-x-1">
                                <img v-for="a in task.assignees.slice(0,3)" :key="a.id" :src="a.avatar_url" :title="a.name" class="w-5 h-5 rounded-full ring-1 ring-white dark:ring-slate-900">
                                <div v-if="task.assignees.length > 3" class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-[8px] font-semibold text-slate-500 flex items-center justify-center ring-1 ring-white dark:ring-slate-900">
                                    +{{ task.assignees.length - 3 }}
                                </div>
                                <div v-if="!task.assignees.length" class="w-5 h-5 rounded-full border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center">
                                    <i class="bi bi-person text-[8px] text-slate-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty column hint -->
                    <div v-if="col.tasks.length === 0 && dragOver !== col.id"
                         class="flex flex-col items-center justify-center gap-1.5 py-8 opacity-40">
                        <i class="bi bi-arrow-left-right text-lg text-slate-400"></i>
                        <span class="text-[11px] font-medium text-slate-400">Drag tasks here</span>
                    </div>

                    <!-- Drop zone -->
                    <div v-if="dragOver === col.id"
                         class="h-16 rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-900/10 flex items-center justify-center">
                        <span class="text-xs text-violet-400">Drop here</span>
                    </div>
                </div>

                <!-- Quick Add -->
                <div class="px-2.5 pb-2.5 flex-shrink-0">
                    <button @click="openPanel(col.id)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-colors">
                        <i class="bi bi-plus-lg text-sm"></i> Add task
                    </button>
                </div>
            </div>
        </div>

        <!-- New Task Panel -->
        <Teleport to="body">
            <div v-if="panelOpen" class="fixed inset-0 z-40" @keydown.escape.window="panelOpen = false">
                <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm" @click="panelOpen = false"></div>
                <div class="absolute inset-y-0 right-0 z-50 w-full max-w-md flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center">
                                <i class="bi bi-plus-lg text-violet-600 dark:text-violet-400 text-sm"></i>
                            </div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">New Task</h2>
                            <span class="text-xs text-slate-400 font-mono">{{ project.name }}</span>
                        </div>
                        <button @click="panelOpen = false" class="btn-icon btn-ghost p-1.5">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                        <div>
                            <label class="form-label">Title <span class="text-rose-500">*</span></label>
                            <input v-model="taskForm.title" ref="titleInput" type="text" class="form-input" placeholder="What needs to be done?" @keydown.enter="saveTask">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Type</label>
                                <select v-model="taskForm.type" class="form-input">
                                    <option value="task">Task</option>
                                    <option value="bug">Bug</option>
                                    <option value="feature">Feature</option>
                                    <option value="story">Story</option>
                                    <option value="epic">Epic</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Priority</label>
                                <select v-model="taskForm.priority" class="form-input">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Column</label>
                            <select v-model="taskForm.column_id" class="form-input">
                                <option v-for="c in columns" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Assignees</label>
                            <div class="flex flex-wrap gap-2 mb-2" v-if="taskForm.assignee_ids.length">
                                <span v-for="id in taskForm.assignee_ids" :key="id"
                                      class="inline-flex items-center gap-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs px-2 py-0.5 rounded-full">
                                    {{ memberName(id) }}
                                    <button type="button" @click="toggleAssignee(id)" class="hover:text-rose-500">
                                        <i class="bi bi-x text-xs"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5 max-h-36 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                                <button v-for="m in members" :key="m.id" type="button" @click="toggleAssignee(m.id)"
                                        class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-sm transition-colors"
                                        :class="taskForm.assignee_ids.includes(m.id) ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' : 'hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
                                    <img :src="m.avatar_url" class="w-5 h-5 rounded-full flex-shrink-0">
                                    <span class="truncate text-xs">{{ m.name }}</span>
                                    <i v-if="taskForm.assignee_ids.includes(m.id)" class="bi bi-check-lg text-violet-500 ml-auto text-xs flex-shrink-0"></i>
                                </button>
                            </div>
                        </div>
                        <div v-if="labels.length">
                            <label class="form-label">Labels</label>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="lbl in labels" :key="lbl.id" type="button" @click="toggleLabel(lbl.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-white transition-all"
                                        :style="taskForm.label_ids.includes(lbl.id) ? { backgroundColor: lbl.color, boxShadow: '0 0 0 2px ' + lbl.color } : { backgroundColor: lbl.color + '66' }">
                                    {{ lbl.name }}
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Due date</label>
                                <DatePicker v-model="taskForm.due_date" />
                            </div>
                            <div>
                                <label class="form-label">Estimate (hrs)</label>
                                <input v-model="taskForm.estimated_hours" type="number" min="0" step="0.5" class="form-input" placeholder="0">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                            <textarea v-model="taskForm.description" rows="3" class="form-input resize-none" placeholder="Add more context…"></textarea>
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 flex-shrink-0">
                        <button @click="panelOpen = false" class="btn-ghost btn-sm">Cancel</button>
                        <button @click="saveTask" :disabled="!taskForm.title.trim() || taskForm.processing"
                                class="btn-primary btn-sm gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="bi" :class="taskForm.processing ? 'bi-arrow-repeat animate-spin' : 'bi-plus-lg'"></i>
                            {{ taskForm.processing ? 'Creating…' : 'Create Task' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Board Settings Modal -->
        <Teleport to="body">
            <div v-if="settingsOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="settingsOpen = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                        <h2 class="font-semibold text-slate-900 dark:text-white">Board Settings</h2>
                        <button @click="settingsOpen = false" class="btn-icon btn-ghost p-1.5"><i class="bi bi-x-lg text-sm"></i></button>
                    </div>
                    <div class="overflow-y-auto p-5 space-y-2.5 flex-1">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Columns</p>
                        <div v-for="col in boardCols" :key="col.id" class="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl">
                            <input v-model="col.color" type="color" class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0.5">
                            <input v-model="col.name" class="form-input flex-1 text-sm py-1.5" placeholder="Column name">
                            <input v-model="col.wip_limit" type="number" min="1" class="form-input w-16 text-sm py-1.5 text-center" placeholder="WIP">
                            <button @click="updateColumn(col)" class="btn-secondary btn-sm px-2.5 py-1.5" title="Save"><i class="bi bi-check-lg text-emerald-600"></i></button>
                            <button @click="deleteColumn(col)" class="btn-ghost btn-sm px-2 py-1.5 text-rose-400" title="Delete"><i class="bi bi-trash text-sm"></i></button>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                            <p class="text-xs font-semibold text-slate-500 mb-2.5">Add New Column</p>
                            <div class="flex items-center gap-2">
                                <input v-model="newColColor" type="color" class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0.5">
                                <input v-model="newColName" class="form-input flex-1 text-sm py-1.5" placeholder="Column name" @keydown.enter="addColumn">
                                <input v-model="newColWip" type="number" min="1" class="form-input w-16 text-sm py-1.5 text-center" placeholder="WIP">
                                <button @click="addColumn" class="btn-primary btn-sm px-2.5 py-1.5"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button @click="settingsOpen = false" class="btn-ghost btn-sm">Cancel</button>
                        <button @click="settingsOpen = false; router.reload()" class="btn-primary btn-sm">Done</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
