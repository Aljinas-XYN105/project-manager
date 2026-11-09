<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    projects: Object,
    filters: Object,
    can_create: Boolean,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')
const priority = ref(props.filters?.priority ?? '')

const hasFilters = computed(() => search.value || status.value || priority.value)

const statusColors = {
    planning:  'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
    active:    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    on_hold:   'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    archived:  'bg-slate-100 dark:bg-slate-800 text-slate-500',
}

const priorityColors = {
    critical: 'text-rose-600 dark:text-rose-400',
    high:     'text-amber-600 dark:text-amber-400',
    medium:   'text-blue-600 dark:text-blue-400',
    low:      'text-slate-500',
}

function statusLabel(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ') : ''
}

function applyFilters() {
    router.get(route('projects.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        priority: priority.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    status.value = ''
    priority.value = ''
    router.get(route('projects.index'), {}, { preserveState: false })
}

const openMenus = ref({})
function toggleMenu(id) {
    openMenus.value = { ...openMenus.value, [id]: !openMenus.value[id] }
}
function closeMenu(id) {
    openMenus.value = { ...openMenus.value, [id]: false }
}

function archiveProject(url) {
    router.patch(url, {}, {
        preserveScroll: true,
        onSuccess: () => {},
    })
}
</script>

<template>
    <AppLayout title="Projects">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Projects</span>
                </div>
                <h1 class="page-title">Projects</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ projects.total }} project{{ projects.total !== 1 ? 's' : '' }}
                </p>
            </div>
            <Link v-if="can_create" :href="route('projects.create')" class="btn-primary">
                <i class="bi bi-plus-lg"></i> New Project
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <form @submit.prevent="applyFilters" class="flex flex-wrap gap-2 items-center">
                <div class="relative flex-1 min-w-[180px] max-w-xs">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                    <input v-model="search" type="text" placeholder="Search projects…"
                           class="form-input pl-9 text-sm">
                </div>
                <select v-model="status" class="form-input w-auto text-sm">
                    <option value="">All Status</option>
                    <option value="planning">Planning</option>
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="archived">Archived</option>
                </select>
                <select v-model="priority" class="form-input w-auto text-sm">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
                <button type="submit" class="btn-primary btn-sm">Filter</button>
                <button v-if="hasFilters" type="button" @click="clearFilters" class="btn-ghost btn-sm">Clear</button>
            </form>
        </div>

        <!-- Project Grid -->
        <div v-if="projects.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="project in projects.data" :key="project.id"
                 class="card p-5 flex flex-col gap-4 group hover:shadow-card-md transition-all duration-200">
                <!-- Header -->
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm"
                             :style="`background-color: ${project.color}20; border: 1px solid ${project.color}30;`">
                            <div class="w-3.5 h-3.5 rounded-full" :style="`background-color: ${project.color};`"></div>
                        </div>
                        <div class="min-w-0">
                            <Link :href="project.url"
                                  class="font-semibold text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 text-sm truncate block">
                                {{ project.name }}
                            </Link>
                            <p class="text-xs text-slate-400 mt-0.5">{{ project.owner?.name }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium flex-shrink-0"
                          :class="statusColors[project.status] ?? statusColors.planning">
                        {{ statusLabel(project.status) }}
                    </span>
                </div>

                <p v-if="project.description" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 -mt-1">
                    {{ project.description }}
                </p>

                <!-- Progress -->
                <div>
                    <div class="flex justify-between text-xs text-slate-500 mb-1.5">
                        <span>Progress</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ project.progress_percentage }}%</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1.5 rounded-full transition-all duration-700"
                             :style="`width: ${project.progress_percentage}%; background-color: ${project.color};`"></div>
                    </div>
                </div>

                <!-- Meta row -->
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1">
                            <i class="bi bi-people"></i> {{ project.members_count }}
                        </span>
                        <span class="flex items-center gap-1" :class="priorityColors[project.priority]">
                            <i class="bi bi-flag-fill text-[10px]"></i>
                            {{ project.priority ? project.priority.charAt(0).toUpperCase() + project.priority.slice(1) : 'Medium' }}
                        </span>
                    </div>
                    <span v-if="project.due_date" class="flex items-center gap-1"
                          :class="project.is_overdue ? 'text-rose-500 font-medium' : ''">
                        <i class="bi bi-calendar3"></i> {{ project.due_date }}
                    </span>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <Link :href="project.board_url"
                          class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                                 hover:bg-violet-100 dark:hover:bg-violet-900/30 hover:text-violet-700 dark:hover:text-violet-400 transition-colors">
                        <i class="bi bi-kanban text-sm"></i> Board
                    </Link>
                    <Link :href="project.tasks_url"
                          class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                                 hover:bg-violet-100 dark:hover:bg-violet-900/30 hover:text-violet-700 dark:hover:text-violet-400 transition-colors">
                        <i class="bi bi-list-task text-sm"></i> Tasks
                    </Link>
                    <div class="relative">
                        <button @click="toggleMenu(project.id)" @blur="closeMenu(project.id)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="bi bi-three-dots-vertical text-sm"></i>
                        </button>
                        <div v-show="openMenus[project.id]"
                             class="dropdown-menu absolute right-0 bottom-8 w-44 z-10">
                            <Link :href="project.url" class="dropdown-item">
                                <i class="bi bi-eye text-slate-400"></i> View details
                            </Link>
                            <Link :href="project.edit_url" class="dropdown-item">
                                <i class="bi bi-pencil text-slate-400"></i> Edit
                            </Link>
                            <Link :href="project.sprints_url" class="dropdown-item">
                                <i class="bi bi-lightning text-slate-400"></i> Sprints
                            </Link>
                            <template v-if="project.can_archive">
                                <div class="dropdown-divider"></div>
                                <button @click="archiveProject(project.archive_url)"
                                        class="dropdown-item w-full text-amber-600 dark:text-amber-400">
                                    <i class="bi bi-archive"></i> Archive
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="projects.data.length && projects.last_page > 1" class="mt-6 flex items-center justify-center gap-1">
            <template v-for="link in projects.links" :key="link.label">
                <Link v-if="link.url" :href="link.url"
                      class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                      :class="link.active
                          ? 'bg-violet-600 text-white'
                          : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                      v-html="link.label" />
                <span v-else class="px-3 py-1.5 rounded-lg text-sm text-slate-300 dark:text-slate-600"
                      v-html="link.label" />
            </template>
        </div>

        <!-- Empty state -->
        <div v-if="!projects.data.length" class="empty-state">
            <i class="bi bi-folder-plus text-4xl text-slate-300 dark:text-slate-600 block mb-4"></i>
            <p class="font-semibold text-slate-600 dark:text-slate-400 text-lg">
                {{ hasFilters ? 'No projects match your filters' : 'No projects yet' }}
            </p>
            <p class="text-sm text-slate-400 mt-1">
                {{ hasFilters ? 'Try adjusting your search criteria' : 'Create your first project to start tracking work' }}
            </p>
            <Link v-if="!hasFilters && can_create" :href="route('projects.create')" class="btn-primary mt-4">
                <i class="bi bi-plus-lg"></i> Create Project
            </Link>
        </div>
    </AppLayout>
</template>
