<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    reports: Object,
    today_report: Object,
    can_see_team: Boolean,
    today_date: String,
})

function deleteReport(url) {
    if (!confirm('Delete this report?')) return
    router.delete(url, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Daily Reports">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Daily Reports</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your daily work progress</p>
            </div>
            <div class="flex items-center gap-2">
                <Link v-if="can_see_team" :href="route('daily-reports.team')" class="btn-ghost btn-sm">
                    <i class="bi bi-people-fill"></i> Team Reports
                </Link>
                <Link :href="route('daily-reports.create')" class="btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Submit Today's Report
                </Link>
            </div>
        </div>

        <!-- Today's report banner -->
        <div v-if="today_report" class="card p-4 mb-6 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                        <i class="bi bi-check-circle-fill text-green-600 text-base"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">Today's report submitted!</p>
                        <p class="text-xs text-gray-500">{{ today_report.total_hours }}h logged · {{ today_report.task_count }} task(s)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="today_report.show_url" class="btn-ghost btn-sm">View</Link>
                    <Link :href="today_report.edit_url" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i> Edit</Link>
                </div>
            </div>
        </div>
        <div v-else class="card p-4 mb-6 border-l-4 border-orange-400 bg-orange-50 dark:bg-orange-900/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center">
                        <i class="bi bi-journal-text text-orange-600 text-base"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">You haven't submitted today's report yet</p>
                        <p class="text-xs text-gray-500">{{ today_date }}</p>
                    </div>
                </div>
                <Link :href="route('daily-reports.create')" class="btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Submit Now
                </Link>
            </div>
        </div>

        <!-- Reports list -->
        <div v-if="reports.data.length === 0" class="card p-14 text-center">
            <i class="bi bi-journal-text text-5xl text-slate-200 dark:text-slate-700 block mb-4"></i>
            <h3 class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-1">No reports submitted yet</h3>
            <p class="text-sm text-slate-400 mb-4">Submit your first daily standup to start tracking your progress.</p>
            <Link :href="route('daily-reports.create')" class="btn-primary btn-sm mx-auto">
                <i class="bi bi-plus-lg"></i> Submit Today's Report
            </Link>
        </div>
        <div v-else class="space-y-3">
            <div v-for="report in reports.data" :key="report.id" class="card p-5 hover:shadow-card-md transition-shadow">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                            <span class="font-semibold text-slate-900 dark:text-white">{{ report.report_date }}</span>
                            <span v-if="report.is_today" class="badge badge-success text-[10px]">Today</span>
                            <span v-else-if="report.is_yesterday" class="badge badge-secondary text-[10px]">Yesterday</span>
                            <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 dark:text-slate-400 px-2 py-0.5 rounded-full">
                                {{ report.total_hours }}h logged
                            </span>
                            <span class="text-xs text-slate-400">{{ report.task_count }} task(s)</span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-2">{{ report.summary }}</p>
                        <div v-if="report.blockers" class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 mb-2">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                            <span class="truncate">{{ report.blockers }}</span>
                        </div>
                        <div v-if="report.task_snippets?.length" class="flex flex-wrap gap-1.5">
                            <span v-for="(rt, i) in report.task_snippets" :key="i"
                                  class="text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded-full">
                                {{ rt.description }} · {{ rt.hours }}h
                            </span>
                            <span v-if="report.extra_tasks > 0" class="text-[11px] text-slate-400">+{{ report.extra_tasks }} more</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <Link :href="report.show_url" class="btn-ghost btn-sm">View</Link>
                        <Link v-if="report.can_edit" :href="report.edit_url" class="btn-ghost btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </Link>
                        <span v-else class="btn-ghost btn-sm text-slate-400 cursor-not-allowed" title="Editing locked">
                            <i class="bi bi-lock"></i>
                        </span>
                        <button @click="deleteReport(report.destroy_url)"
                                class="btn-ghost btn-sm text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20" title="Delete">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="reports.last_page > 1" class="mt-6 flex items-center justify-center gap-1">
            <template v-for="link in reports.links" :key="link.label">
                <Link v-if="link.url" :href="link.url"
                      class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                      :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                      v-html="link.label" />
                <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
            </template>
        </div>
    </AppLayout>
</template>
