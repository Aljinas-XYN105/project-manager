<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import SearchSelect from '@/Components/SearchSelect.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    reports: Object,
    users: Array,
    date: String,
    user_id: [String, Number, null],
    export_excel_url: String,
})

const filters = ref({ date: props.date ?? '', user_id: props.user_id ?? '' })

function applyFilters() {
    const q = {}
    if (filters.value.date) q.date = filters.value.date
    if (filters.value.user_id) q.user_id = filters.value.user_id
    router.get(route('daily-reports.team'), q, { preserveState: false })
}

function resetFilters() {
    router.get(route('daily-reports.team'), {}, { preserveState: false })
}

const statusColors = {
    in_progress: 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
    in_review:   'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
    done:        'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
    blocked:     'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400',
    todo:        'bg-slate-50 dark:bg-slate-800 text-slate-500',
}

const totalReports = props.reports?.total ?? 0
const withBlockers = (props.reports?.data ?? []).filter(r => r.has_blockers).length
const blockedTasks = (props.reports?.data ?? []).reduce((sum, r) => sum + (r.report_tasks ?? []).filter(t => t.is_blocked).length, 0)
const totalHours   = (props.reports?.data ?? []).reduce((sum, r) => sum + (r.total_hours ?? 0), 0)

const expanded = ref({})
function toggle(id) { expanded.value[id] = !expanded.value[id] }
</script>

<template>
    <AppLayout title="Team Standups">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('daily-reports.index')" class="hover:text-violet-600 transition-colors">Daily Reports</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Team</span>
                </div>
                <h1 class="page-title flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                        <i class="bi bi-people-fill text-violet-600 dark:text-violet-400"></i>
                    </span>
                    Team Standups
                </h1>
                <p class="text-sm text-slate-500 mt-1">Daily reports across the team</p>
            </div>
            <a :href="export_excel_url + (date ? '?date=' + date : '') + (user_id ? '&user_id=' + user_id : '')"
               class="btn-secondary flex items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>

        <!-- Filters -->
        <div class="card p-4 mb-6">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="form-label">Date</label>
                    <DatePicker v-model="filters.date" />
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="form-label">Team Member</label>
                    <SearchSelect v-model="filters.user_id"
                        :options="users.map(u => ({value: u.id, label: u.name, avatar: u.avatar_url}))"
                        :clearable="true" clearLabel="All Members" placeholder="All Members" />
                </div>
                <button @click="applyFilters" class="btn-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <button @click="resetFilters" class="btn-ghost">Reset</button>
            </div>
        </div>

        <!-- Stats bar -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalReports }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Reports</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalHours.toFixed(1) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Hours Logged</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold" :class="withBlockers > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">{{ withBlockers }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Have Blockers</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold" :class="blockedTasks > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">{{ blockedTasks }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Blocked Tasks</p>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!reports.data?.length" class="empty-state">
            <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
            <h3>No reports for this date</h3>
            <p>No team members have submitted a standup for the selected date or filters.</p>
        </div>

        <!-- Reports list -->
        <div v-else class="space-y-4">
            <div v-for="report in reports.data" :key="report.id"
                 class="card overflow-hidden"
                 :class="report.has_blockers ? 'border-l-4 border-l-amber-400 dark:border-l-amber-600' : ''">
                <div class="p-4 flex items-center gap-3 cursor-pointer" @click="toggle(report.id)">
                    <UserAvatar :src="report.user?.avatar_url ?? ''" :name="report.user?.name ?? ''" size="lg" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-slate-900 dark:text-white text-sm">{{ report.user?.name ?? '—' }}</span>
                            <span class="text-xs text-slate-400">{{ report.report_date }}</span>
                            <span v-if="report.has_blockers"
                                  class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-semibold">
                                <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blocker
                            </span>
                            <span class="ml-auto text-sm font-bold text-slate-900 dark:text-white">
                                {{ report.total_hours?.toFixed(1) }}h
                            </span>
                            <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform duration-200"
                               :class="expanded[report.id] ? 'rotate-180' : ''"></i>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ report.summary }}</p>
                    </div>
                </div>

                <!-- Expanded content -->
                <div v-if="expanded[report.id]"
                     class="border-t border-slate-100 dark:border-slate-800 p-4 space-y-4">
                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ report.summary }}</p>

                    <div v-if="report.report_tasks?.length">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Tasks</p>
                        <div class="space-y-2">
                            <div v-for="(rt, i) in report.report_tasks" :key="i"
                                 class="flex items-start gap-3 text-sm">
                                <span class="flex-shrink-0 font-mono text-xs text-slate-400 w-14 pt-0.5">
                                    {{ rt.hours_spent?.toFixed(1) }}h
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-slate-800 dark:text-slate-200">{{ rt.task_description }}</span>
                                        <span v-if="rt.is_blocked"
                                              class="inline-flex items-center gap-0.5 px-1 py-px rounded text-[10px] font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                                            <i class="bi bi-exclamation-octagon-fill"></i> blocked
                                        </span>
                                        <span v-if="rt.status_update"
                                              class="px-1.5 py-px rounded text-[10px] font-medium"
                                              :class="statusColors[rt.status_update] ?? ''">
                                            {{ rt.status_update.replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <p v-if="rt.notes" class="text-xs text-slate-400 mt-0.5">{{ rt.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="report.plan_for_tomorrow || report.blockers" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-if="report.plan_for_tomorrow" class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Tomorrow</p>
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ report.plan_for_tomorrow }}</p>
                        </div>
                        <div v-if="report.blockers" class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                            <p class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-1 flex items-center gap-1">
                                <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blockers
                            </p>
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ report.blockers }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Link :href="report.show_url" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">
                            View full report →
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="reports.last_page > 1" class="mt-6 flex gap-1.5 justify-end">
            <button v-for="link in reports.links" :key="link.label"
                    @click="link.url && router.visit(link.url)"
                    :disabled="!link.url"
                    class="px-2.5 py-1 text-xs rounded-lg border transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                    :class="link.active ? 'bg-violet-600 text-white border-violet-600' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    v-html="link.label">
            </button>
        </div>
    </AppLayout>
</template>
