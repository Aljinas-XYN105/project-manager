<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import ProjectSelect from '@/Components/ProjectSelect.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    groups: Object,
    view: String,
    scope: String,
    counts: Object,
    isManager: Boolean,
    assigneeId: Number,
    projects: Array,
    members: Array,
    filters: Object,
})

const projectId = ref(props.filters?.project_id ?? '')
const priority  = ref(props.filters?.priority ?? '')
const type      = ref(props.filters?.type ?? '')
const assigneeId = ref(props.filters?.assignee_id ?? '')

const groupStyle = {
    'Overdue':     ['bi-exclamation-circle-fill', 'text-rose-500'],
    'Today':       ['bi-sun-fill', 'text-amber-500'],
    'This week':   ['bi-calendar-week', 'text-violet-500'],
    'Later':       ['bi-calendar3', 'text-blue-500'],
    'No due date': ['bi-dash-circle', 'text-slate-400'],
    'Completed':   ['bi-check-circle-fill', 'text-emerald-500'],
}

const openGroups = ref(Object.fromEntries(Object.keys(props.groups).map(k => [k, true])))

function navigate(params) {
    router.get(route('my-tasks'), { ...params }, { preserveState: true, replace: true })
}

function changeFilter() {
    navigate({
        view: props.view, scope: props.scope,
        project_id: projectId.value || undefined,
        priority: priority.value || undefined,
        type: type.value || undefined,
        assignee_id: assigneeId.value || undefined,
    })
}

function hasFilters() {
    return projectId.value || priority.value || type.value || assigneeId.value
}

const taskStatuses = ref({})

async function toggleTaskDone(task) {
    const current = taskStatuses.value[task.id] ?? task.status
    const next = current === 'done' ? 'todo' : 'done'
    taskStatuses.value[task.id] = next
    try {
        await axios.patch(task.status_url ?? `/tasks/${task.id}/status`, { status: next })
    } catch {
        taskStatuses.value[task.id] = current
    }
}

const totalTasks = Object.values(props.groups).reduce((sum, g) => sum + g.length, 0)

const priorityBadge = {
    critical: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    high:     'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    medium:   'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    low:      'bg-slate-100 dark:bg-slate-800 text-slate-500',
}
</script>

<template>
    <AppLayout title="My Tasks">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>My Tasks</span>
                </div>
                <h1 class="page-title">
                    <template v-if="isManager && assigneeId && members.find(m => m.id === assigneeId)">
                        {{ members.find(m => m.id === assigneeId)?.name }}'s Tasks
                    </template>
                    <template v-else-if="isManager && scope === 'all'">All Tasks</template>
                    <template v-else>My Tasks</template>
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    <template v-if="isManager && scope === 'all'">All tasks across every project.</template>
                    <template v-else>Everything assigned to you, across every project.</template>
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <div v-if="isManager" class="tab-bar">
                    <Link :href="route('my-tasks', { scope: 'mine', view })" class="tab-item" :class="scope === 'mine' && !assigneeId ? 'active' : ''">
                        My Tasks <span class="text-slate-400">{{ counts.open }}</span>
                    </Link>
                    <Link :href="route('my-tasks', { scope: 'all', view })" class="tab-item" :class="(scope === 'all' || assigneeId) ? 'active' : ''">
                        All Tasks <span class="text-slate-400">{{ counts.all_open }}</span>
                    </Link>
                </div>
                <div class="tab-bar">
                    <Link :href="route('my-tasks', { view: 'open', scope })" class="tab-item" :class="view === 'open' ? 'active' : ''">
                        Open <span class="text-slate-400">{{ isManager && scope === 'all' ? counts.all_open : counts.open }}</span>
                    </Link>
                    <Link :href="route('my-tasks', { view: 'done', scope })" class="tab-item" :class="view === 'done' ? 'active' : ''">
                        Done <span class="text-slate-400">{{ isManager && scope === 'all' ? counts.all_done : counts.done }}</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2 mb-5">
            <input type="hidden" :value="view">
            <input type="hidden" :value="scope">
            <ProjectSelect v-model="projectId" :projects="projects" placeholder="All projects" :clearable="true" size="sm" class="w-auto min-w-[160px]" @update:modelValue="changeFilter" />
            <SearchSelect v-model="priority" @update:modelValue="changeFilter" size="sm"
                :options="[{value:'critical',label:'Critical'},{value:'high',label:'High'},{value:'medium',label:'Medium'},{value:'low',label:'Low'}]"
                :clearable="true" clearLabel="Any priority" placeholder="Any priority" />
            <template v-if="isManager">
                <SearchSelect v-model="type" @update:modelValue="changeFilter" size="sm"
                    :options="[{value:'bug',label:'Bug'},{value:'feature',label:'Feature'},{value:'task',label:'Task'},{value:'improvement',label:'Improvement'},{value:'chore',label:'Chore'}]"
                    :clearable="true" clearLabel="Any type" placeholder="Any type" />
                <SearchSelect v-model="assigneeId" @update:modelValue="changeFilter" size="sm"
                    :options="members.map(m => ({value: m.id, label: m.name, avatar: m.avatar_url}))"
                    :clearable="true" clearLabel="Any person" placeholder="Any person" />
            </template>
            <Link v-if="hasFilters()" :href="route('my-tasks', { view, scope })" class="btn-ghost btn-sm">Clear</Link>
        </div>

        <!-- Empty state -->
        <div v-if="totalTasks === 0" class="card empty-state py-16">
            <i class="bi bi-check2-all text-4xl text-emerald-400"></i>
            <p class="text-sm text-slate-500 mt-3">{{ view === 'done' ? 'No completed tasks yet.' : 'Nothing on your plate. Nice!' }}</p>
        </div>

        <!-- Task groups -->
        <div class="space-y-5">
            <div v-for="(tasks, label) in groups" :key="label" v-show="tasks.length" class="card overflow-hidden">
                <button type="button" @click="openGroups[label] = !openGroups[label]"
                        class="w-full flex items-center gap-2 px-5 py-3 border-b border-slate-100 dark:border-slate-800 text-left">
                    <i :class="`bi ${groupStyle[label]?.[0] ?? 'bi-circle'} ${groupStyle[label]?.[1] ?? 'text-slate-400'}`"></i>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ label }}</span>
                    <span class="text-xs text-slate-400">{{ tasks.length }}</span>
                    <i class="bi bi-chevron-down text-xs text-slate-400 ml-auto transition-transform"
                       :class="openGroups[label] ? '' : '-rotate-90'"></i>
                </button>
                <div v-show="openGroups[label]">
                    <div v-for="task in tasks" :key="task.id"
                         class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <button type="button" @click="toggleTaskDone(task)"
                                :title="(taskStatuses[task.id] ?? task.status) === 'done' ? 'Reopen' : 'Mark done'"
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                                :class="(taskStatuses[task.id] ?? task.status) === 'done'
                                    ? 'bg-emerald-500 border-emerald-500 text-white'
                                    : 'border-slate-300 dark:border-slate-600 hover:border-emerald-400'">
                            <i v-if="(taskStatuses[task.id] ?? task.status) === 'done'" class="bi bi-check text-xs"></i>
                        </button>
                        <i :class="`bi ${task.type_icon} ${task.type_color} text-sm`"></i>
                        <Link :href="task.show_url" class="flex-1 min-w-0">
                            <p class="text-sm text-slate-900 dark:text-white truncate"
                               :class="(taskStatuses[task.id] ?? task.status) === 'done' ? 'line-through text-slate-400' : ''">
                                {{ task.title }}
                            </p>
                            <p class="text-xs text-slate-400 truncate">{{ task.ticket_key }} · {{ task.project?.name ?? '—' }}</p>
                        </Link>
                        <div v-if="isManager && scope === 'all' && task.assignees?.length" class="hidden sm:flex items-center -space-x-1.5 flex-shrink-0">
                            <img v-for="a in task.assignees.slice(0, 3)" :key="a.id"
                                 :src="a.avatar_url" :alt="a.name" :title="a.name"
                                 class="w-5 h-5 rounded-full border border-white dark:border-slate-900 object-cover">
                            <span v-if="task.assignees.length > 3"
                                  class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 border border-white dark:border-slate-900 flex items-center justify-center text-[9px] text-slate-500">
                                +{{ task.assignees.length - 3 }}
                            </span>
                        </div>
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-medium"
                              :class="priorityBadge[task.priority]">
                            {{ task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : '' }}
                        </span>
                        <span v-if="task.due_date" class="text-xs w-20 text-right flex-shrink-0"
                              :class="task.is_overdue ? 'text-rose-500 font-medium' : 'text-slate-500'">
                            {{ task.due_date }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
