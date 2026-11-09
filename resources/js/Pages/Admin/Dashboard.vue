<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    period: [String, Number],
    total_users: Number,
    active_users: Number,
    total_projects: Number,
    active_projects: Number,
    total_tasks: Number,
    done_tasks: Number,
    overdue_tasks: Number,
    total_hours: [String, Number],
    daily_hours: Array,
    task_types: Array,
    employees: Array,
    projects: Array,
    recent_reports: Array,
    users_url: String,
    reports_url: String,
})

const selectedPeriod = ref(String(props.period))
function changePeriod() {
    router.get(route('admin.dashboard'), { period: selectedPeriod.value })
}

const maxDailyHours = Math.max(...props.daily_hours.map(d => d.hours), 1)

function burnClass(rate) {
    if (rate === null) return ''
    if (rate > 100) return 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400'
    if (rate > 80)  return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'
    return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
}
</script>

<template>
    <AppLayout title="Admin Overview">
        <div>
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 text-xs font-semibold ring-1 ring-rose-200 dark:ring-rose-800">
                            <i class="bi bi-shield-fill-check text-xs"></i> Super Admin
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Admin</span>
                    </div>
                    <h1 class="page-title">Admin Overview</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Organisation-wide performance dashboard</p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="selectedPeriod" @change="changePeriod" class="form-input text-sm w-auto">
                        <option value="7">Last 7 days</option>
                        <option value="14">Last 14 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                    <a :href="users_url" class="btn-primary">
                        <i class="bi bi-people-fill"></i> Manage Members
                    </a>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="section-heading mb-2">Total Users</p>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ total_users }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ active_users }} active this period</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-people-fill text-violet-600 dark:text-violet-400"></i>
                        </div>
                    </div>
                    <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1.5 bg-violet-500 rounded-full" :style="'width:' + (total_users > 0 ? Math.round(active_users/total_users*100) : 0) + '%'"></div>
                    </div>
                </div>
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="section-heading mb-2">Active Projects</p>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ active_projects }}</p>
                            <p class="text-xs text-slate-500 mt-1">of {{ total_projects }} total</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-folder2-open text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1.5 bg-blue-500 rounded-full" :style="'width:' + (total_projects > 0 ? Math.round(active_projects/total_projects*100) : 0) + '%'"></div>
                    </div>
                </div>
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="section-heading mb-2">Tasks Done</p>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ done_tasks }}</p>
                            <p class="text-xs mt-1" :class="overdue_tasks > 0 ? 'text-rose-500' : 'text-slate-500'">{{ overdue_tasks }} overdue</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-check-circle-fill text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                    </div>
                    <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1.5 bg-emerald-500 rounded-full" :style="'width:' + (total_tasks > 0 ? Math.round(done_tasks/total_tasks*100) : 0) + '%'"></div>
                    </div>
                </div>
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="section-heading mb-2">Hours Logged</p>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ total_hours }}</p>
                            <p class="text-xs text-slate-500 mt-1">in the last {{ period }} days</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-clock-history text-amber-600 dark:text-amber-400"></i>
                        </div>
                    </div>
                    <div class="mt-3 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1.5 bg-amber-500 rounded-full w-3/4"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
                <!-- Daily Hours Chart -->
                <div class="xl:col-span-2 card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-slate-900 dark:text-white">Daily Hours Logged</h2>
                        <span class="text-xs text-slate-400">Last 14 days</span>
                    </div>
                    <div class="flex items-end gap-1.5 h-36">
                        <div v-for="day in daily_hours" :key="day.date" class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="relative w-full">
                                <div class="w-full bg-violet-500/10 dark:bg-violet-500/20 rounded-t-sm group-hover:bg-violet-500/20 transition-colors cursor-default"
                                     :style="'height:' + Math.max(4, Math.round(day.hours/maxDailyHours*120)) + 'px'"
                                     :title="day.label + ': ' + day.hours + 'h'">
                                    <div v-if="day.hours > 0" class="w-full h-full bg-violet-500 rounded-t-sm"></div>
                                </div>
                            </div>
                            <span class="text-[9px] text-slate-400 truncate">{{ day.label.slice(4) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Task Types -->
                <div class="card p-5">
                    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Task Types</h2>
                    <div class="space-y-3">
                        <div v-for="tt in task_types" :key="tt.key" class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :class="tt.color.replace('bg-','bg-').replace('500','100').replace('400','100')">
                                <i class="bi text-xs" :class="[tt.icon, tt.color.replace('bg-','text-')]"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ tt.label }}</span>
                                    <span class="text-xs text-slate-500">{{ tt.count }}</span>
                                </div>
                                <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                                    <div class="h-1.5 rounded-full" :class="tt.color" :style="'width:' + tt.pct + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
                <!-- Employee Performance -->
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="font-semibold text-slate-900 dark:text-white">Employee Performance</h2>
                        <span class="text-xs text-slate-400">Last {{ period }} days</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-right">Hours</th>
                                    <th class="text-right">Done</th>
                                    <th class="text-right">Open</th>
                                    <th class="text-right hidden sm:table-cell">Reports</th>
                                    <th class="text-right hidden md:table-cell">Score</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="employees.length">
                                    <tr v-for="emp in employees" :key="emp.name">
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                <img :src="emp.avatar_url" class="w-7 h-7 rounded-full flex-shrink-0">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ emp.name }}</p>
                                                    <p class="text-xs text-slate-400 capitalize">{{ emp.role }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right"><span class="text-sm font-semibold text-slate-900 dark:text-white">{{ emp.hours_logged }}h</span></td>
                                        <td class="text-right"><span class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ emp.completed_tasks }}</span></td>
                                        <td class="text-right">
                                            <span class="text-sm" :class="emp.overdue_tasks > 0 ? 'text-rose-500 font-medium' : 'text-slate-500'">
                                                {{ emp.open_tasks }}
                                                <span v-if="emp.overdue_tasks > 0" class="text-xs text-rose-400">({{ emp.overdue_tasks }}⚠)</span>
                                            </span>
                                        </td>
                                        <td class="text-right hidden sm:table-cell"><span class="text-sm text-slate-500">{{ emp.daily_reports }}</span></td>
                                        <td class="text-right hidden md:table-cell">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                                                    <div class="h-1.5 rounded-full" :style="'width:' + emp.performance_pct + '%'"
                                                         :class="emp.performance_pct >= 70 ? 'bg-emerald-500' : (emp.performance_pct >= 40 ? 'bg-amber-500' : 'bg-rose-500')"></div>
                                                </div>
                                                <span class="text-xs text-slate-500 w-8 text-right">{{ emp.performance_pct }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <a v-if="emp.employee_url" :href="emp.employee_url" class="btn-xs btn-ghost">
                                                <i class="bi bi-arrow-right text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else><td colspan="7" class="text-center py-8 text-slate-400 text-sm">No employee data found.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Project Burn Rates -->
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="font-semibold text-slate-900 dark:text-white">Project Burn Rates</h2>
                        <span class="text-xs text-slate-400">Active projects</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th class="text-right">Progress</th>
                                    <th class="text-right hidden sm:table-cell">Est. hrs</th>
                                    <th class="text-right hidden sm:table-cell">Logged</th>
                                    <th class="text-right">Burn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="projects.length">
                                    <tr v-for="p in projects" :key="p.name">
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-6 h-6 rounded-md flex-shrink-0 flex items-center justify-center"
                                                     :style="'background-color:' + p.color + '25'">
                                                    <div class="w-2 h-2 rounded-full" :style="'background-color:' + p.color"></div>
                                                </div>
                                                <div class="min-w-0">
                                                    <a :href="p.show_url || '#'" class="text-sm font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">{{ p.name }}</a>
                                                    <p class="text-xs text-slate-400">{{ p.done_tasks }}/{{ p.total_tasks }} tasks
                                                        <span v-if="p.overdue_tasks > 0" class="text-rose-500"> · {{ p.overdue_tasks }} overdue</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="w-14 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full hidden sm:block">
                                                    <div class="h-1.5 rounded-full bg-violet-500" :style="'width:' + p.progress + '%'"></div>
                                                </div>
                                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ p.progress }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-right hidden sm:table-cell"><span class="text-sm text-slate-500">{{ p.estimated_hrs > 0 ? p.estimated_hrs + 'h' : '—' }}</span></td>
                                        <td class="text-right hidden sm:table-cell"><span class="text-sm text-slate-700 dark:text-slate-300 font-medium">{{ p.logged_hrs }}h</span></td>
                                        <td class="text-right">
                                            <span v-if="p.burn_rate !== null" class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold" :class="burnClass(p.burn_rate)">
                                                {{ p.burn_rate }}%
                                            </span>
                                            <span v-else class="text-xs text-slate-400">—</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else><td colspan="5" class="text-center py-8 text-slate-400 text-sm">No active projects.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Standups -->
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Recent Daily Reports</h2>
                    <a :href="reports_url" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all →</a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template v-if="recent_reports.length">
                        <div v-for="report in recent_reports" :key="report.id"
                             class="flex items-start gap-4 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <img :src="report.avatar_url" class="w-8 h-8 rounded-full flex-shrink-0 mt-0.5">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ report.user_name }}</span>
                                    <span class="text-xs text-slate-400">{{ report.report_date }}</span>
                                    <span v-if="report.has_blocker"
                                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">
                                        <i class="bi bi-exclamation-triangle-fill text-[9px]"></i> Blocker
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ report.summary }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ report.total_hours }}h</p>
                                <a :href="report.show_url" class="text-[10px] text-violet-600 hover:underline">View</a>
                            </div>
                        </div>
                    </template>
                    <div v-else class="py-10 text-center text-slate-400 text-sm">
                        <i class="bi bi-journal-x text-2xl block mb-2"></i>
                        No daily reports yet.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
