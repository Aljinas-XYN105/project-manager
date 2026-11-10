<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    project: Object,
    tasks: Array,
    columns: Array,
})

const priorityOrder  = ['critical', 'high', 'medium', 'low']
const priorityLabels = { critical: 'Critical', high: 'High', medium: 'Medium', low: 'Low' }
const priorityBadge  = {
    critical: 'badge-critical',
    high:     'badge-high',
    medium:   'badge-medium',
    low:      'badge-low',
}

const selected = ref([])

const grouped = computed(() => {
    const g = {}
    for (const t of props.tasks) {
        if (!g[t.priority]) g[t.priority] = []
        g[t.priority].push(t)
    }
    return g
})

function toggleAll(e) {
    selected.value = e.target.checked ? props.tasks.map(t => t.id) : []
}

function toggle(id, checked) {
    checked ? selected.value.push(id) : (selected.value = selected.value.filter(i => i !== id))
}

const moveOpen = ref(false)

async function moveToColumn(colId) {
    if (!selected.value.length || !colId) return
    moveOpen.value = false
    const csrf = document.querySelector('meta[name=csrf-token]').content
    // Bulk update status then move each
    for (const id of selected.value) {
        await fetch('/tasks/' + id + '/move', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify({ column_id: parseInt(colId), position: 999 }),
        })
    }
    selected.value = []
    router.reload({ preserveScroll: true })
}
</script>

<template>
    <AppLayout :title="project.name + ' — Backlog'">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <Link :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</Link>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Backlog</span>
                </div>
                <h1 class="page-title">Backlog</h1>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="tab-bar">
                    <Link :href="project.index_url" class="tab-item text-xs px-2.5 py-1.5"><i class="bi bi-list-task"></i> <span class="hidden sm:inline">List</span></Link>
                    <Link :href="route('projects.tasks.kanban', project.id)" class="tab-item text-xs px-2.5 py-1.5"><i class="bi bi-kanban"></i> <span class="hidden sm:inline">Board</span></Link>
                    <Link :href="project.calendar_url" class="tab-item text-xs px-2.5 py-1.5"><i class="bi bi-calendar3"></i> <span class="hidden sm:inline">Calendar</span></Link>
                    <Link :href="project.backlog_url" class="tab-item active text-xs px-2.5 py-1.5"><i class="bi bi-inbox"></i> <span class="hidden sm:inline">Backlog</span></Link>
                </div>
                <Link :href="project.create_url" class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Task</Link>
            </div>
        </div>

        <!-- Bulk action bar -->
        <Teleport to="body">
            <div v-if="selected.length > 0"
                 class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 dark:bg-slate-700 text-white rounded-2xl shadow-2xl px-5 py-3 flex items-center gap-4">
                <span class="text-sm font-medium">{{ selected.length }} selected</span>
                <div class="h-4 w-px bg-slate-600"></div>
                <div class="relative">
                    <button @click="moveOpen = !moveOpen"
                            class="bg-slate-800 dark:bg-slate-600 text-white text-xs rounded-lg px-2.5 py-1.5 border border-slate-600 flex items-center gap-2 hover:bg-slate-700 transition-colors">
                        Move to column…
                        <i class="bi bi-chevron-down text-[10px]" :class="moveOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div v-if="moveOpen" v-click-outside="() => moveOpen = false"
                         class="absolute bottom-full mb-1 left-0 w-44 bg-slate-800 rounded-xl border border-white/10 shadow-2xl overflow-hidden z-50">
                        <button v-for="col in columns" :key="col.id" @click="moveToColumn(col.id)"
                                class="w-full text-left px-3.5 py-2 text-xs text-slate-200 hover:bg-white/10 hover:text-white transition-colors">
                            {{ col.name }}
                        </button>
                    </div>
                </div>
                <button @click="selected = []" class="ml-2 text-slate-400 hover:text-white"><i class="bi bi-x-lg text-sm"></i></button>
            </div>
        </Teleport>

        <div class="space-y-4">
            <template v-for="priority in priorityOrder" :key="priority">
                <div v-if="grouped[priority]?.length" class="card overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                        <span :class="priorityBadge[priority]">{{ priorityLabels[priority] }}</span>
                        <span class="text-sm text-slate-500">{{ grouped[priority].length }} tasks</span>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        <div v-for="task in grouped[priority]" :key="task.id"
                             class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
                            <input type="checkbox" class="rounded border-slate-300 flex-shrink-0"
                                   :checked="selected.includes(task.id)"
                                   @change="toggle(task.id, $event.target.checked)">
                            <Link :href="task.show_url"
                                  class="flex-1 font-medium text-sm text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 truncate">
                                {{ task.title }}
                            </Link>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 capitalize">
                                    {{ task.status?.replace('_', ' ') }}
                                </span>
                                <span v-if="task.story_points" class="text-[10px] text-violet-500 font-semibold bg-violet-50 dark:bg-violet-900/20 px-1.5 py-0.5 rounded-full">{{ task.story_points }}pts</span>
                                <span v-if="task.due_date" class="text-[10px]" :class="task.is_overdue ? 'text-red-500 font-medium' : 'text-slate-400'">
                                    <i class="bi bi-calendar3 mr-0.5"></i>{{ task.due_date }}
                                </span>
                                <div class="flex items-center">
                                    <img v-for="(a, ai) in task.assignees.slice(0,3)" :key="a.id"
                                         :src="a.avatar_url" :title="a.name"
                                         class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800"
                                         :class="ai > 0 ? '-ml-1' : ''">
                                </div>
                                <Link :href="task.edit_url"
                                      class="opacity-0 group-hover:opacity-100 btn-ghost btn-sm py-1 px-1.5 transition-opacity">
                                    <i class="bi bi-pencil text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="!tasks.length" class="card p-16 text-center">
                <i class="bi bi-inbox text-4xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 mt-3 mb-4">Backlog is empty — all tasks are in progress or done!</p>
                <Link :href="project.create_url" class="btn-primary"><i class="bi bi-plus-lg"></i> Add Task</Link>
            </div>
        </div>
    </AppLayout>
</template>
