<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ daily_report: Object })

const statusColors = {
    in_progress: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    in_review:   'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    done:        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    blocked:     'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    todo:        'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
}
const statusLabels = {
    in_progress: '🔵 In Progress',
    in_review:   '🟡 In Review',
    done:        '✅ Done',
    blocked:     '🔴 Blocked',
    todo:        '⚪ To Do',
}

function deleteReport() {
    if (!confirm('Delete this report?')) return
    router.delete(props.daily_report.destroy_url)
}
</script>

<template>
    <AppLayout :title="`Report — ${daily_report.report_date}`">
        <div class="max-w-3xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="bi bi-journal-text text-amber-600 dark:text-amber-400"></i>
                        </span>
                        Daily Report
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">{{ daily_report.report_date }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="daily_report.pdf_url" target="_blank" class="btn-secondary btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                    <Link v-if="daily_report.can_edit" :href="daily_report.edit_url" class="btn-secondary btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </Link>
                    <Link :href="daily_report.index_url" class="btn-ghost">
                        <i class="bi bi-arrow-left"></i> Back
                    </Link>
                </div>
            </div>

            <!-- Author strip -->
            <div class="card p-4 mb-5 flex items-center gap-3">
                <img :src="daily_report.user.avatar_url" :alt="daily_report.user.name"
                     class="w-10 h-10 rounded-full ring-2 ring-white dark:ring-slate-800 shadow-sm">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-white text-sm">{{ daily_report.user.name }}</p>
                    <p class="text-xs text-slate-400">{{ daily_report.report_date }} · Submitted {{ daily_report.created_at_human }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ daily_report.total_hours?.toFixed(1) }}<span class="text-sm font-medium text-slate-400">h</span>
                    </p>
                    <p class="text-xs text-slate-400">total logged</p>
                </div>
            </div>

            <div class="space-y-5">
                <!-- Summary -->
                <div class="card p-5">
                    <h2 class="section-heading">Summary</h2>
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ daily_report.summary }}</p>
                </div>

                <!-- Task Breakdown -->
                <div v-if="daily_report.report_tasks?.length" class="card p-5">
                    <h2 class="section-heading">Task Breakdown</h2>
                    <div class="space-y-3">
                        <div v-for="(rt, i) in daily_report.report_tasks" :key="i"
                             class="flex items-start gap-4 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60"
                             :class="rt.is_blocked ? 'border-l-4 border-l-rose-400 dark:border-l-rose-600' : ''">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <a v-if="rt.task" :href="rt.task.show_url"
                                       class="font-mono text-xs text-violet-600 dark:text-violet-400 hover:underline">
                                        {{ rt.task.ticket_key }}
                                    </a>
                                    <span v-if="rt.is_blocked"
                                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-semibold">
                                        <i class="bi bi-exclamation-octagon-fill text-[10px]"></i> Blocked
                                    </span>
                                    <span v-if="rt.status_update"
                                          class="inline-flex items-center px-1.5 py-0.5 rounded-md text-xs font-medium"
                                          :class="statusColors[rt.status_update] ?? ''">
                                        {{ statusLabels[rt.status_update] ?? rt.status_update }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ rt.task_description }}</p>
                                <p v-if="rt.notes" class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ rt.notes }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ rt.hours_spent?.toFixed(1) }}</span>
                                <span class="text-xs text-slate-400">h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan & Blockers -->
                <div v-if="daily_report.plan_for_tomorrow || daily_report.blockers"
                     class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div v-if="daily_report.plan_for_tomorrow" class="card p-5">
                        <h2 class="section-heading flex items-center gap-1.5">
                            <i class="bi bi-calendar-event text-violet-500"></i> Plan for Tomorrow
                        </h2>
                        <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line">
                            {{ daily_report.plan_for_tomorrow }}
                        </p>
                    </div>
                    <div v-if="daily_report.blockers" class="card p-5 border-amber-200 dark:border-amber-800">
                        <h2 class="section-heading flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                            <i class="bi bi-exclamation-triangle-fill"></i> Blockers
                        </h2>
                        <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line">
                            {{ daily_report.blockers }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
