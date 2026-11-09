<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    bugs: Object,
    projects: Array,
    bugs_by_status: Object,
    filters: Object,
})

const statsConfig = {
    todo:        { label: 'Open',        color: 'text-slate-700 dark:text-slate-300',    bg: 'bg-slate-100 dark:bg-slate-800',       icon: 'bi-circle',             ring: 'ring-slate-200 dark:ring-slate-700' },
    in_progress: { label: 'In Progress', color: 'text-blue-700 dark:text-blue-400',      bg: 'bg-blue-50 dark:bg-blue-900/20',        icon: 'bi-arrow-right-circle', ring: 'ring-blue-200 dark:ring-blue-800' },
    qa_testing:  { label: 'QA Testing',  color: 'text-violet-700 dark:text-violet-400',  bg: 'bg-violet-50 dark:bg-violet-900/20',    icon: 'bi-bug',                ring: 'ring-violet-200 dark:ring-violet-800' },
    in_review:   { label: 'In Review',   color: 'text-amber-700 dark:text-amber-400',    bg: 'bg-amber-50 dark:bg-amber-900/20',      icon: 'bi-eye',                ring: 'ring-amber-200 dark:ring-amber-800' },
    done:        { label: 'Resolved',    color: 'text-emerald-700 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/20', icon: 'bi-check-circle',       ring: 'ring-emerald-200 dark:ring-emerald-800' },
    cancelled:   { label: 'Closed',      color: 'text-slate-500',                        bg: 'bg-slate-100 dark:bg-slate-800',        icon: 'bi-x-circle',           ring: 'ring-slate-200 dark:ring-slate-700' },
}

const severityColors = {
    blocker:  'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 ring-1 ring-rose-200 dark:ring-rose-800',
    critical: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 ring-1 ring-orange-200 dark:ring-orange-800',
    major:    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800',
    minor:    'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400 ring-1 ring-sky-200 dark:ring-sky-800',
    trivial:  'bg-slate-100 dark:bg-slate-800 text-slate-500 ring-1 ring-slate-200 dark:ring-slate-700',
}

const statusClasses = {
    todo:        'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
    in_progress: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    qa_testing:  'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400',
    in_review:   'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    done:        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    cancelled:   'bg-slate-100 dark:bg-slate-800 text-slate-500',
}

const statusLabels = { todo: 'Open', in_progress: 'In Progress', qa_testing: 'QA Testing', in_review: 'In Review', done: 'Resolved', cancelled: 'Closed' }

const f = ref({
    status:     props.filters?.status ?? '',
    severity:   props.filters?.severity ?? '',
    priority:   props.filters?.priority ?? '',
    project_id: props.filters?.project_id ?? '',
})

function applyFilters() {
    const q = {}
    if (f.value.status)     q.status = f.value.status
    if (f.value.severity)   q.severity = f.value.severity
    if (f.value.priority)   q.priority = f.value.priority
    if (f.value.project_id) q.project_id = f.value.project_id
    router.get(route('bugs.index'), q, { preserveState: false })
}

function clearFilters() {
    f.value = { status: '', severity: '', priority: '', project_id: '' }
    router.get(route('bugs.index'), {}, { preserveState: false })
}

const hasFilters = () => f.value.status || f.value.severity || f.value.priority || f.value.project_id
</script>

<template>
    <AppLayout title="Bug Tracker">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Bug Tracker</span>
                </div>
                <h1 class="page-title flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                        <i class="bi bi-bug-fill text-rose-600 dark:text-rose-400"></i>
                    </span>
                    Bug Tracker
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track and resolve bugs across all your projects</p>
            </div>
            <Link :href="route('tasks.create', { type: 'bug' })" class="btn-primary">
                <i class="bi bi-bug-fill"></i> Report Bug
            </Link>
        </div>

        <!-- Stats strip -->
        <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 mb-6">
            <button v-for="(sc, sv) in statsConfig" :key="sv"
                    @click="f.status = f.status === sv ? '' : sv; applyFilters()"
                    class="card p-3.5 flex items-center gap-3 hover:shadow-card-md transition-all text-left"
                    :class="filters?.status === sv ? 'ring-2 ring-violet-500' : ''">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ring-1" :class="[sc.bg, sc.ring]">
                    <i class="bi text-sm" :class="[sc.icon, sc.color]"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold leading-none" :class="sc.color">{{ bugs_by_status[sv] ?? 0 }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5 leading-none truncate">{{ sc.label }}</p>
                </div>
            </button>
        </div>

        <!-- Filters -->
        <div class="card p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[160px]">
                    <label class="form-label">Status</label>
                    <select v-model="f.status" class="form-input text-sm">
                        <option value="">All Statuses</option>
                        <option v-for="(label, val) in statusLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                </div>
                <div class="min-w-[130px]">
                    <label class="form-label">Severity</label>
                    <select v-model="f.severity" class="form-input text-sm">
                        <option value="">All Severity</option>
                        <option value="blocker">Blocker</option>
                        <option value="critical">Critical</option>
                        <option value="major">Major</option>
                        <option value="minor">Minor</option>
                        <option value="trivial">Trivial</option>
                    </select>
                </div>
                <div class="min-w-[120px]">
                    <label class="form-label">Priority</label>
                    <select v-model="f.priority" class="form-input text-sm">
                        <option value="">All Priority</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="min-w-[160px]">
                    <label class="form-label">Project</label>
                    <select v-model="f.project_id" class="form-input text-sm">
                        <option value="">All Projects</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <button v-if="hasFilters()" @click="clearFilters" class="btn-ghost btn-sm">Clear</button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!bugs.data?.length" class="empty-state">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mb-4">
                <i class="bi bi-bug text-3xl text-rose-300 dark:text-rose-600"></i>
            </div>
            <p class="font-semibold text-slate-600 dark:text-slate-400 text-lg">No bugs found</p>
            <p class="text-sm text-slate-400 mt-1">
                <template v-if="hasFilters()">Try adjusting your filters</template>
                <template v-else>Use <strong>Report Bug</strong> above or create a task with type "Bug" inside any project.</template>
            </p>
            <Link v-if="!hasFilters()" :href="route('projects.index')" class="mt-4 btn-secondary btn-sm">
                <i class="bi bi-arrow-right"></i> Go to a project to report a bug
            </Link>
        </div>

        <!-- Bugs table -->
        <div v-else class="card overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-semibold text-slate-500">{{ bugs.total }} bugs</span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-28">Ticket</th>
                            <th>Bug Title</th>
                            <th class="hidden md:table-cell">Project</th>
                            <th class="hidden lg:table-cell">Severity</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th class="hidden md:table-cell">Assignees</th>
                            <th class="hidden lg:table-cell text-right">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bug in bugs.data" :key="bug.id">
                            <td>
                                <span class="font-mono text-xs text-slate-400 bg-slate-50 dark:bg-slate-800 px-2 py-0.5 rounded-md">{{ bug.ticket_key }}</span>
                            </td>
                            <td>
                                <Link :href="bug.show_url"
                                      class="font-medium text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors leading-snug">
                                    {{ bug.title }}
                                </Link>
                                <p v-if="bug.environment" class="text-xs text-slate-400 mt-0.5">
                                    <i class="bi bi-display text-[10px]"></i> {{ bug.environment }}
                                </p>
                            </td>
                            <td class="hidden md:table-cell">
                                <Link v-if="bug.project" :href="bug.project.show_url"
                                      class="text-sm text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400">
                                    {{ bug.project.name }}
                                </Link>
                            </td>
                            <td class="hidden lg:table-cell">
                                <span v-if="bug.severity"
                                      class="text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize"
                                      :class="severityColors[bug.severity] ?? severityColors.trivial">
                                    {{ bug.severity }}
                                </span>
                                <span v-else class="text-xs text-slate-300">—</span>
                            </td>
                            <td>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize"
                                      :class="{
                                          'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400': bug.priority === 'low',
                                          'bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400': bug.priority === 'medium',
                                          'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': bug.priority === 'high',
                                          'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400': bug.priority === 'critical',
                                      }">
                                    {{ bug.priority }}
                                </span>
                            </td>
                            <td>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="statusClasses[bug.status] ?? statusClasses.todo">
                                    {{ statusLabels[bug.status] ?? bug.status }}
                                </span>
                            </td>
                            <td class="hidden md:table-cell">
                                <div class="flex -space-x-1">
                                    <img v-for="(a, i) in bug.assignees?.slice(0, 3)" :key="a.id"
                                         :src="a.avatar_url" :title="a.name"
                                         class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800">
                                </div>
                            </td>
                            <td class="hidden lg:table-cell text-right text-xs text-slate-400">{{ bug.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="bugs.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-slate-800">
                <span class="text-xs text-slate-500">Page {{ bugs.current_page }} of {{ bugs.last_page }}</span>
                <div class="flex gap-1.5">
                    <button v-for="link in bugs.links" :key="link.label"
                            @click="link.url && router.visit(link.url)"
                            :disabled="!link.url"
                            class="px-2.5 py-1 text-xs rounded-lg border transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                            :class="link.active ? 'bg-violet-600 text-white border-violet-600' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'"
                            v-html="link.label">
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
