<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    project: Object,
    sprint: Object,
    kpi: Object,
    columns: Array,
    backlog_tasks: Array,
    can_update: Boolean,
})

const showBacklog = ref(false)
const csrf = () => document.querySelector('meta[name=csrf-token]')?.content

async function patchSprint(url, msg) {
    if (!confirm(msg)) return
    const fd = new FormData()
    fd.append('_method', 'PATCH')
    fd.append('_token', csrf())
    await fetch(url, { method: 'POST', body: fd })
    router.reload()
}

async function removeTask(url) {
    const fd = new FormData()
    fd.append('_method', 'DELETE')
    fd.append('_token', csrf())
    await fetch(url, { method: 'POST', body: fd })
    router.reload()
}

const selectedTasks = ref([])

async function addSelected() {
    if (!selectedTasks.value.length) { alert('Select at least one task.'); return }
    await fetch(props.sprint.add_task_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ task_ids: selectedTasks.value }),
    })
    router.reload()
}

const priorityClass = {
    critical: 'badge-critical',
    high:     'badge-high',
    medium:   'badge-medium',
    low:      'badge-low',
}
</script>

<template>
    <AppLayout :title="sprint.name">
        <div>
            <!-- Header -->
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <a :href="project.sprints_url" class="hover:text-violet-600 transition-colors">Sprints</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>{{ sprint.name }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="page-title">{{ sprint.name }}</h1>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" :class="sprint.status_class">
                            {{ sprint.status_label }}
                        </span>
                    </div>
                    <p v-if="sprint.goal" class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">{{ sprint.goal }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                        <i class="bi bi-calendar3 text-slate-400"></i> {{ sprint.date_range }}
                    </span>
                    <template v-if="can_update">
                        <button v-if="sprint.is_planning"
                                @click="patchSprint(sprint.start_url, 'Start this sprint?')"
                                class="btn-primary btn-sm">
                            <i class="bi bi-play-fill"></i> Start Sprint
                        </button>
                        <button v-else-if="sprint.is_active"
                                @click="patchSprint(sprint.complete_url, 'Complete sprint? Unfinished tasks return to backlog.')"
                                class="btn-sm px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                            <i class="bi bi-check2-all"></i> Complete Sprint
                        </button>
                    </template>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ kpi.total_tasks }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Total Tasks</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ kpi.in_progress }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">In Progress</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ kpi.done_tasks }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Completed</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold" :class="kpi.bugs > 0 ? 'text-rose-500 dark:text-rose-400' : 'text-slate-900 dark:text-white'">{{ kpi.bugs }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Bugs</p>
                </div>
            </div>

            <!-- Progress bar -->
            <div v-if="kpi.total_tasks > 0" class="card p-4 mb-5">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2">
                        <i class="bi bi-speedometer2 text-violet-500"></i> Sprint Progress
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ kpi.progress }}%</span>
                </div>
                <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-2.5 rounded-full transition-all duration-700"
                         :style="'width:' + kpi.progress + '%; background: linear-gradient(90deg, #7c3aed, #a855f7)'"></div>
                </div>
                <div class="flex justify-between text-xs text-slate-400 mt-1.5">
                    <span>{{ kpi.done_tasks }} done</span>
                    <span>{{ kpi.total_tasks - kpi.done_tasks }} remaining</span>
                </div>
            </div>

            <!-- Sprint Board -->
            <div class="flex gap-3 overflow-x-auto pb-6 -mx-4 lg:-mx-6 px-4 lg:px-6 min-h-[calc(100vh-28rem)] items-start">
                <div v-for="column in columns" :key="column.id"
                     class="flex flex-col flex-shrink-0 w-[270px] rounded-2xl bg-slate-100/70 dark:bg-slate-800/50 backdrop-blur-sm border border-slate-200/60 dark:border-slate-700/50">
                    <div class="flex items-center gap-2 px-3 py-3 flex-shrink-0">
                        <div class="w-2.5 h-2.5 rounded-full" :style="'background-color:' + column.color"></div>
                        <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ column.name }}</span>
                        <span class="min-w-[20px] h-5 bg-white dark:bg-slate-700 rounded-full text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center justify-center px-1.5 shadow-sm ml-auto">
                            {{ column.tasks.length }}
                        </span>
                    </div>
                    <div class="flex-1 overflow-y-auto px-2.5 pb-2.5 space-y-2">
                        <template v-if="column.tasks.length">
                            <div v-for="task in column.tasks" :key="task.id"
                                 class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm p-3 hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700/60 hover:-translate-y-px transition-all duration-150 group">
                                <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                    <i class="bi" :class="[task.type_icon, task.type_color, 'text-xs']"></i>
                                    <span v-if="task.ticket_key" class="font-mono text-[9px] text-slate-400">{{ task.ticket_key }}</span>
                                    <span v-for="label in task.labels" :key="label.name"
                                          class="px-1.5 py-px rounded-full text-[9px] font-semibold text-white leading-none"
                                          :style="'background-color:' + label.color">{{ label.name }}</span>
                                </div>
                                <a :href="task.show_url"
                                   class="block font-medium text-sm text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 leading-snug mb-2.5">
                                    {{ task.title }}
                                </a>
                                <div class="flex items-center justify-between">
                                    <span class="py-0 text-[9px]" :class="priorityClass[task.priority] || 'badge-medium'">{{ task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium' }}</span>
                                    <div class="flex -space-x-1">
                                        <img v-for="a in task.assignees" :key="a.name"
                                             :src="a.avatar_url" :title="a.name"
                                             class="w-5 h-5 rounded-full border-2 border-white dark:border-slate-900">
                                    </div>
                                </div>
                                <div v-if="can_update && sprint.is_active"
                                     class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="removeTask(task.remove_url)" class="text-xs text-slate-400 hover:text-rose-500 transition-colors">
                                        <i class="bi bi-x-circle mr-0.5"></i> Remove from sprint
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div v-else class="py-8 text-center">
                            <i class="bi bi-inbox text-2xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                            <p class="text-xs text-slate-400">No tasks</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add from Backlog -->
            <div v-if="can_update && backlog_tasks.length" class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-inbox text-slate-400"></i>
                        Backlog
                        <span class="text-sm font-normal text-slate-400">({{ backlog_tasks.length }} tasks)</span>
                    </h2>
                    <button @click="showBacklog = !showBacklog" class="btn-ghost btn-sm text-sm">
                        {{ showBacklog ? 'Hide' : 'Add from Backlog' }}
                        <i class="bi transition-transform duration-200" :class="showBacklog ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                </div>
                <div v-if="showBacklog" class="card overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                        <p class="text-sm text-slate-500">Select backlog tasks to add to this sprint.</p>
                        <button @click="addSelected" class="btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Selected
                        </button>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-80 overflow-y-auto">
                        <label v-for="task in backlog_tasks" :key="task.id"
                               class="flex items-center gap-3 px-5 py-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <input type="checkbox" :value="task.id" v-model="selectedTasks"
                                   class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <i class="bi text-xs" :class="[task.type_icon, task.type_color]"></i>
                                    <span class="text-xs text-slate-400 font-mono">{{ task.ticket_key }}</span>
                                    <span class="font-medium text-sm text-slate-800 dark:text-slate-200 truncate">{{ task.title }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] flex-shrink-0 py-0" :class="priorityClass[task.priority] || 'badge-medium'">
                                {{ task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Medium' }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
