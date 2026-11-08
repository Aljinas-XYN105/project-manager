<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    stats: Object,
    snapshots: Array,
})

const reportCards = [
    { title: 'Time Report',     desc: 'Time logged by team, project and date range.',    icon: 'bi-clock-history',      color: 'violet', route: 'reports.time' },
    { title: 'Project Report',  desc: 'Project progress, task completion and velocity.', icon: 'bi-kanban',             color: 'blue',   route: 'reports.projects' },
    { title: 'Team Report',     desc: 'Team workload, activity and contribution.',        icon: 'bi-people',             color: 'emerald',route: 'reports.teams' },
    { title: 'Leave Report',    desc: 'Leave usage and balances across the team.',        icon: 'bi-calendar-minus',     color: 'amber',  route: 'reports.leave' },
    { title: 'Expense Report',  desc: 'Expenses by category, project and member.',        icon: 'bi-receipt',            color: 'rose',   route: 'reports.expenses' },
]
</script>

<template>
    <AppLayout title="Reports">
        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Reports</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Analytics and insights across your projects</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Hours Logged</span>
                        <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-stopwatch text-violet-600 dark:text-violet-400"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.total_hours }}h</div>
                    <p class="text-xs text-slate-500 mt-1">This month</p>
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Tasks Completed</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <i class="bi bi-check-circle text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.tasks_completed }}</div>
                    <p class="text-xs text-slate-500 mt-1">This month</p>
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Active Projects</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <i class="bi bi-folder text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.active_projects }}</div>
                    <p class="text-xs text-slate-500 mt-1">Currently active</p>
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Team Members</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <i class="bi bi-people text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.team_members }}</div>
                    <p class="text-xs text-slate-500 mt-1">Active contributors</p>
                </div>
            </div>

            <!-- Report Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Link v-for="card in reportCards" :key="card.route" :href="route(card.route)"
                      class="card-hover p-6 group">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                         :class="`bg-${card.color}-100 dark:bg-${card.color}-900/30`">
                        <i :class="`bi ${card.icon} text-xl text-${card.color}-600 dark:text-${card.color}-400`"></i>
                    </div>
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-1 group-hover:text-violet-600">{{ card.title }}</h3>
                    <p class="text-sm text-slate-500">{{ card.desc }}</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
