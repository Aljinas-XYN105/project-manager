<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user: Object,
    stats: Object,
    recent_tasks: Array,
    can_manage: Boolean,
})

const statusClass = {
    todo:        'badge-todo',
    in_progress: 'badge-in_progress',
    in_review:   'badge-in_review',
    done:        'badge-done',
    blocked:     'badge-blocked',
}

function statusLabel(s) {
    return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : ''
}
</script>

<template>
    <AppLayout :title="user.name">
        <div class="space-y-6">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <a :href="route('users.index')" class="hover:text-violet-600 transition-colors">Members</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>{{ user.name }}</span>
            </div>
            <!-- Header card -->
            <div class="card p-6">
                <div class="flex items-start gap-5">
                    <img :src="user.avatar_url" class="w-20 h-20 rounded-2xl object-cover flex-shrink-0" alt="">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ user.name }}</h1>
                            <span v-if="user.is_active"
                                  class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
                            <span v-else class="badge bg-slate-100 text-slate-500">Inactive</span>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5">{{ user.job_title ?? user.email }}</p>
                        <Link v-if="can_manage" :href="user.edit_url" class="btn-secondary btn-sm mt-3 inline-flex">
                            <i class="bi bi-pencil"></i> Edit member
                        </Link>
                        <div class="flex items-center gap-4 mt-3 text-sm text-slate-500 dark:text-slate-400 flex-wrap">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ user.email }}
                            </span>
                            <span v-if="user.timezone" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ user.timezone }}
                            </span>
                            <span v-if="user.joined_on">Joined {{ user.joined_on }}</span>
                        </div>
                        <div v-if="user.roles.length" class="flex items-center gap-2 mt-3 flex-wrap">
                            <span v-for="r in user.roles" :key="r"
                                  class="badge bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">{{ r }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left col: stats + employment + teams -->
                <div class="space-y-4">
                    <!-- Stats 2×2 grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="stat-card">
                            <span class="text-xs text-slate-500">Assigned Tasks</span>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.assigned_tasks }}</div>
                        </div>
                        <div class="stat-card">
                            <span class="text-xs text-slate-500">Completed</span>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.completed_tasks }}</div>
                        </div>
                        <div class="stat-card">
                            <span class="text-xs text-slate-500">Hours Logged</span>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.hours_logged }}</div>
                        </div>
                        <div class="stat-card">
                            <span class="text-xs text-slate-500">Projects</span>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.projects_count }}</div>
                        </div>
                    </div>

                    <!-- Employment card -->
                    <div class="card p-4">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Employment</h3>
                        <template v-if="user.employment_rows?.length || user.manager || user.direct_reports?.length">
                            <dl class="space-y-2 text-sm">
                                <div v-for="row in user.employment_rows" :key="row.label"
                                     class="flex justify-between gap-3">
                                    <dt class="text-slate-500">{{ row.label }}</dt>
                                    <dd class="text-slate-800 dark:text-slate-200 text-right">{{ row.value }}</dd>
                                </div>
                                <div v-if="user.manager" class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Reports to</dt>
                                    <dd>
                                        <Link :href="user.manager.show_url" class="text-violet-600 hover:underline">{{ user.manager.name }}</Link>
                                    </dd>
                                </div>
                            </dl>
                            <template v-if="user.direct_reports?.length">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mt-4 mb-2">Direct reports</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <Link v-for="r in user.direct_reports" :key="r.id" :href="r.show_url" :title="r.name">
                                        <img :src="r.avatar_url" class="w-7 h-7 rounded-full ring-2 ring-white dark:ring-slate-900" alt="">
                                    </Link>
                                </div>
                            </template>
                        </template>
                        <p v-else class="text-xs text-slate-400">No employment details yet.</p>
                    </div>

                    <!-- Teams card -->
                    <div v-if="user.teams?.length" class="card p-4">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Teams</h3>
                        <div class="space-y-2">
                            <Link v-for="team in user.teams" :key="team.id" :href="team.show_url"
                                  class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600">
                                <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-xs font-bold text-violet-600">
                                    {{ team.name.charAt(0) }}
                                </div>
                                {{ team.name }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Right col: recent tasks -->
                <div class="lg:col-span-2">
                    <div class="card overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Recent Tasks</h3>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-if="recent_tasks.length">
                                <Link v-for="task in recent_tasks" :key="task.id" :href="task.show_url"
                                      class="flex items-start gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <span class="mt-0.5 text-[11px] px-2 py-0.5 rounded-full font-medium flex-shrink-0"
                                          :class="statusClass[task.status] ?? 'bg-slate-100 text-slate-500'">
                                        {{ statusLabel(task.status) }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ task.title }}</p>
                                        <p v-if="task.project" class="text-xs text-slate-500 mt-0.5">{{ task.project.name }}</p>
                                    </div>
                                    <span v-if="task.due_date"
                                          class="text-xs flex-shrink-0"
                                          :class="task.is_overdue ? 'text-red-500' : 'text-slate-400'">
                                        {{ task.due_date }}
                                    </span>
                                </Link>
                            </template>
                            <div v-else class="p-6 text-center text-sm text-slate-400">No tasks assigned.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
