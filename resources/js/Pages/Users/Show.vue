<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user: Object,
    stats: Object,
    recent_tasks: Array,
    can_manage: Boolean,
})

const statusColors = {
    todo:        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    in_review:   'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    done:        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    blocked:     'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
}

const priorityDot = {
    critical: 'bg-rose-500',
    high:     'bg-orange-400',
    medium:   'bg-amber-400',
    low:      'bg-slate-400',
}

function statusLabel(s) {
    return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : ''
}
</script>

<template>
    <AppLayout :title="user.name">
        <div class="page-header">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <img :src="user.avatar_url" class="w-16 h-16 rounded-full ring-2 ring-slate-200 dark:ring-slate-700" alt="">
                    <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 border-white dark:border-slate-900"
                          :class="user.is_active ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                </div>
                <div>
                    <h1 class="page-title mb-0.5">{{ user.name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ user.job_title ?? 'No title' }}
                        <template v-if="user.location"> · <i class="bi bi-geo-alt"></i> {{ user.location }}</template>
                    </p>
                    <div class="flex flex-wrap gap-1 mt-1.5">
                        <span v-for="r in user.roles" :key="r"
                              class="text-[11px] px-2 py-0.5 rounded-full font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                            {{ r }}
                        </span>
                    </div>
                </div>
            </div>
            <Link v-if="can_manage" :href="user.edit_url" class="btn-ghost">
                <i class="bi bi-pencil"></i> Edit profile
            </Link>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="card p-5 text-center">
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.assigned_tasks }}</p>
                <p class="text-sm text-slate-500 mt-1">Assigned tasks</p>
            </div>
            <div class="card p-5 text-center">
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.completed_tasks }}</p>
                <p class="text-sm text-slate-500 mt-1">Completed</p>
            </div>
            <div class="card p-5 text-center">
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.hours_logged }}</p>
                <p class="text-sm text-slate-500 mt-1">Hours logged</p>
            </div>
            <div class="card p-5 text-center">
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.projects_count }}</p>
                <p class="text-sm text-slate-500 mt-1">Projects</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Recent tasks -->
            <div class="xl:col-span-2 card overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Recent tasks</h2>
                </div>
                <div v-if="recent_tasks.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
                    No tasks assigned yet.
                </div>
                <div v-for="task in recent_tasks" :key="task.id"
                     class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <span v-if="priorityDot[task.priority]" class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot[task.priority]"></span>
                    <div class="flex-1 min-w-0">
                        <Link :href="task.show_url" class="text-sm font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">
                            {{ task.title }}
                        </Link>
                        <p v-if="task.project" class="text-xs text-slate-400 mt-0.5">{{ task.project.name }}</p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full font-medium flex-shrink-0"
                          :class="statusColors[task.status] ?? 'bg-slate-100 text-slate-500'">
                        {{ statusLabel(task.status) }}
                    </span>
                </div>
            </div>

            <!-- Profile sidebar -->
            <div class="space-y-4">
                <!-- Contact info -->
                <div class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Contact</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <i class="bi bi-envelope w-4 text-center text-slate-400"></i>
                            <a :href="`mailto:${user.email}`" class="hover:text-violet-600 truncate">{{ user.email }}</a>
                        </li>
                        <li v-if="user.phone" class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <i class="bi bi-telephone w-4 text-center text-slate-400"></i>
                            {{ user.phone }}
                        </li>
                        <li v-if="user.joined_on" class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <i class="bi bi-calendar-check w-4 text-center text-slate-400"></i>
                            Joined {{ user.joined_on }}
                        </li>
                    </ul>
                </div>

                <!-- Bio -->
                <div v-if="user.bio" class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-2 text-sm">About</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ user.bio }}</p>
                </div>

                <!-- Teams -->
                <div v-if="user.teams.length" class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm">Teams</h3>
                    <div class="space-y-1.5">
                        <Link v-for="team in user.teams" :key="team.id" :href="team.show_url"
                              class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600">
                            <i class="bi bi-people text-slate-400"></i>
                            {{ team.name }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
