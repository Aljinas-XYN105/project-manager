<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    project: Object,
    tasks: Object,
    members: Array,
    filters: Object,
})

const search   = ref(props.filters?.search ?? '')
const status   = ref(props.filters?.status ?? '')
const priority = ref(props.filters?.priority ?? '')
const assignee = ref(props.filters?.assignee ?? '')

const selected = ref([])

const statusStyles = {
    todo:        'text-slate-500',
    in_progress: 'text-blue-600 dark:text-blue-400',
    qa_testing:  'text-violet-600 dark:text-violet-400',
    in_review:   'text-amber-600 dark:text-amber-400',
    done:        'text-emerald-600 dark:text-emerald-400',
    cancelled:   'text-slate-400',
}

const priorityBadge = {
    critical: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    high:     'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    medium:   'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    low:      'bg-slate-100 dark:bg-slate-800 text-slate-500',
}

function applyFilters() {
    router.get(props.project.index_url, {
        search: search.value || undefined,
        status: status.value || undefined,
        priority: priority.value || undefined,
        assignee: assignee.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''; status.value = ''; priority.value = ''; assignee.value = ''
    router.get(props.project.index_url, {})
}

const hasFilters = () => search.value || status.value || priority.value || assignee.value

function toggleAll(checked) {
    selected.value = checked ? props.tasks.data.map(t => t.id) : []
}

function toggle(id, checked) {
    if (checked) selected.value.push(id)
    else selected.value = selected.value.filter(i => i !== id)
}

async function bulkAction(action, value = null) {
    if (!selected.value.length) return
    await axios.post('/tasks/bulk-update', { task_ids: selected.value, action, value })
    router.reload()
}

const openMenus = ref({})
function toggleMenu(id) { openMenus.value = { ...openMenus.value, [id]: !openMenus.value[id] } }
function closeMenu(id) { openMenus.value = { ...openMenus.value, [id]: false } }

async function updateStatus(task, newStatus) {
    await axios.patch(task.status_url ?? `/tasks/${task.id}/status`, { status: newStatus })
    router.reload({ preserveScroll: true })
}

function deleteTask(task) {
    if (!confirm('Delete this task?')) return
    router.delete(task.destroy_url, { preserveScroll: true })
}

const showBulkStatus = ref(false)
const statusOptions = [
    { value: 'todo', label: 'To Do' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'qa_testing', label: 'QA Testing' },
    { value: 'in_review', label: 'In Review' },
    { value: 'done', label: 'Done' },
    { value: 'cancelled', label: 'Cancelled' },
]
</script>

<template>
    <AppLayout :title="`${project.name} — Tasks`">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <Link :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</Link>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Tasks</span>
                </div>
                <h1 class="page-title">Task List</h1>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="tab-bar">
                    <Link :href="project.index_url" class="tab-item active text-xs px-2.5 py-1.5">
                        <i class="bi bi-list-task"></i> <span class="hidden sm:inline">List</span>
                    </Link>
                    <Link :href="project.kanban_url" class="tab-item text-xs px-2.5 py-1.5">
                        <i class="bi bi-kanban"></i> <span class="hidden sm:inline">Board</span>
                    </Link>
                    <Link :href="project.calendar_url" class="tab-item text-xs px-2.5 py-1.5">
                        <i class="bi bi-calendar3"></i> <span class="hidden sm:inline">Calendar</span>
                    </Link>
                    <Link :href="project.backlog_url" class="tab-item text-xs px-2.5 py-1.5">
                        <i class="bi bi-inbox"></i> <span class="hidden sm:inline">Backlog</span>
                    </Link>
                </div>
                <Link :href="project.create_url" class="btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Task
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <form @submit.prevent="applyFilters" class="flex flex-wrap gap-2 mb-5">
            <div class="relative flex-1 min-w-[160px] max-w-xs">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                <input v-model="search" type="text" placeholder="Search tasks…" class="form-input pl-9 text-sm">
            </div>
            <SearchSelect v-model="status" size="sm"
                :options="[{value:'todo',label:'To Do'},{value:'in_progress',label:'In Progress'},{value:'qa_testing',label:'QA Testing'},{value:'in_review',label:'In Review'},{value:'done',label:'Done'},{value:'cancelled',label:'Cancelled'}]"
                :clearable="true" clearLabel="All Status" placeholder="All Status" />
            <SearchSelect v-model="priority" size="sm"
                :options="[{value:'low',label:'Low'},{value:'medium',label:'Medium'},{value:'high',label:'High'},{value:'critical',label:'Critical'}]"
                :clearable="true" clearLabel="All Priority" placeholder="All Priority" />
            <SearchSelect v-model="assignee" size="sm"
                :options="members.map(m => ({value: m.id, label: m.name, avatar: m.avatar_url}))"
                :clearable="true" clearLabel="All Assignees" placeholder="All Assignees" />
            <button type="submit" class="btn-primary btn-sm">Filter</button>
            <button v-if="hasFilters()" type="button" @click="clearFilters" class="btn-ghost btn-sm">Clear</button>
        </form>

        <!-- Bulk action floating bar -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2"
                        leave-active-class="transition ease-in duration-150" leave-to-class="opacity-0 translate-y-2">
                <div v-if="selected.length > 0"
                     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 dark:bg-slate-800 text-white
                            rounded-2xl shadow-2xl px-5 py-3 flex items-center gap-4 border border-white/10">
                    <span class="text-sm font-medium">{{ selected.length }} selected</span>
                    <div class="w-px h-4 bg-white/20"></div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <button type="button" @click="showBulkStatus = !showBulkStatus"
                                    class="bg-white/10 text-white text-xs rounded-lg px-2.5 py-1.5 border border-white/20 flex items-center gap-2 hover:bg-white/20">
                                <span>Change status…</span>
                                <i class="bi bi-chevron-down" style="font-size:0.6rem"></i>
                            </button>
                            <div v-if="showBulkStatus"
                                 class="absolute bottom-full mb-1 left-0 w-40 bg-slate-800 rounded-xl border border-white/10 shadow-2xl overflow-hidden z-50">
                                <button v-for="opt in statusOptions" :key="opt.value" type="button"
                                        @click="bulkAction('status', opt.value); showBulkStatus = false"
                                        class="w-full text-left px-3.5 py-2 text-xs text-slate-200 hover:bg-white/10">
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                        <button @click="bulkAction('delete')"
                                class="flex items-center gap-1.5 text-xs text-rose-400 hover:text-rose-300 px-2.5 py-1.5 rounded-lg hover:bg-rose-900/30">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                    <button @click="selected = []" class="text-white/40 hover:text-white/80 ml-1">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            </Transition>
        </Teleport>

        <div v-if="tasks.data.length" class="card overflow-hidden" :class="selected.length > 0 ? 'mb-24' : ''">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-semibold text-slate-500">{{ tasks.total }} tasks</span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-8 pr-0">
                                <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500"
                                       @change="toggleAll($event.target.checked)">
                            </th>
                            <th>Task</th>
                            <th class="hidden sm:table-cell">Status</th>
                            <th class="hidden md:table-cell">Priority</th>
                            <th class="hidden lg:table-cell">Assignees</th>
                            <th class="hidden md:table-cell text-right">Due</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="task in tasks.data" :key="task.id" class="group" :class="task.status === 'done' ? 'opacity-70' : ''">
                            <td class="pr-0">
                                <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500"
                                       :checked="selected.includes(task.id)"
                                       @change="toggle(task.id, $event.target.checked)">
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <i v-if="task.type" :class="`bi ${task.type_icon} text-sm ${task.type_color} flex-shrink-0`"></i>
                                    <div class="min-w-0">
                                        <Link :href="task.show_url"
                                              class="font-medium text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate block max-w-xs lg:max-w-sm"
                                              :class="task.status === 'done' ? 'line-through text-slate-400 dark:text-slate-500' : ''">
                                            {{ task.title }}
                                        </Link>
                                        <span v-if="task.ticket_key" class="font-mono text-[10px] text-slate-400">{{ task.ticket_key }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell">
                                <SearchSelect :modelValue="task.status"
                                    @update:modelValue="v => updateStatus(task, v)"
                                    :options="[{value:'todo',label:'To Do'},{value:'in_progress',label:'In Progress'},{value:'qa_testing',label:'QA Testing'},{value:'in_review',label:'In Review'},{value:'done',label:'Done'},{value:'cancelled',label:'Cancelled'}]"
                                    size="sm" />
                            </td>
                            <td class="hidden md:table-cell">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium"
                                      :class="priorityBadge[task.priority]">
                                    {{ task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : '' }}
                                </span>
                            </td>
                            <td class="hidden lg:table-cell">
                                <div v-if="task.assignees.length" class="flex items-center -space-x-1.5">
                                    <img v-for="a in task.assignees.slice(0, 3)" :key="a.id"
                                         :src="a.avatar_url" :title="a.name" class="w-7 h-7 rounded-full border-2 border-white dark:border-slate-900">
                                    <div v-if="task.assignees.length > 3"
                                         class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 border-2 border-white dark:border-slate-900 flex items-center justify-center text-[10px] text-slate-500">
                                        +{{ task.assignees.length - 3 }}
                                    </div>
                                </div>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </td>
                            <td class="hidden md:table-cell text-right">
                                <span v-if="task.due_date" class="text-xs"
                                      :class="task.is_overdue && task.status !== 'done' ? 'text-rose-500 font-semibold' : 'text-slate-400'">
                                    {{ task.due_date }}
                                </span>
                                <span v-else class="text-slate-300 dark:text-slate-600">—</span>
                            </td>
                            <td>
                                <div class="relative">
                                    <button @click="toggleMenu(task.id)" @blur="closeMenu(task.id)"
                                            class="p-1.5 rounded-lg text-slate-300 dark:text-slate-600 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 opacity-0 group-hover:opacity-100 transition-all">
                                        <i class="bi bi-three-dots-vertical text-sm"></i>
                                    </button>
                                    <div v-if="openMenus[task.id]" class="dropdown-menu absolute right-0 z-20 w-40">
                                        <Link :href="task.show_url" class="dropdown-item">
                                            <i class="bi bi-eye text-slate-400"></i> View
                                        </Link>
                                        <Link :href="task.edit_url" class="dropdown-item">
                                            <i class="bi bi-pencil text-slate-400"></i> Edit
                                        </Link>
                                        <div class="dropdown-divider"></div>
                                        <button @click="deleteTask(task)" class="dropdown-item w-full text-rose-600 dark:text-rose-400">
                                            <i class="bi bi-trash3"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="tasks.last_page > 1" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1">
                <template v-for="link in tasks.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                          class="px-3 py-1 rounded text-xs"
                          :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                          v-html="link.label" />
                    <span v-else class="px-3 py-1 text-xs text-slate-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <div v-else class="empty-state">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                <i class="bi bi-check2-square text-2xl text-slate-400"></i>
            </div>
            <p class="font-semibold text-slate-600 dark:text-slate-400 text-lg">No tasks yet</p>
            <p class="text-sm text-slate-400 mt-1">Create your first task to start tracking work</p>
            <Link :href="project.create_url" class="btn-primary mt-4">
                <i class="bi bi-plus-lg"></i> Create Task
            </Link>
        </div>
    </AppLayout>
</template>
