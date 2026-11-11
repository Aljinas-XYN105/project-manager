<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchSelect from '@/Components/SearchSelect.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    period: [String, Number],
    total_hours: [String, Number],
    admin_url: String,
    user: Object,
    kpi: Object,
    project_hours: Array,
    daily_breakdown: Array,
    open_tasks: Array,
    daily_reports: Array,
})

const selectedPeriod = ref(String(props.period))
function changePeriod() {
    router.get(route('admin.employee', props.user.id), { period: selectedPeriod.value })
}

const maxPH = Math.max(...props.project_hours.map(p => p.hours), 1)
const maxDH = Math.max(...props.daily_breakdown.map(d => d.hours), 1)
</script>

<template>
    <AppLayout :title="user.name + ' — Employee Profile'">
        <div>
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                        <a :href="admin_url" class="hover:text-violet-600">Admin</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>{{ user.name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <UserAvatar :src="user.avatar_url" :name="user.name" size="xl" class="rounded-2xl ring-2 ring-slate-200 dark:ring-slate-700" />
                        <div>
                            <h1 class="page-title leading-tight">{{ user.name }}</h1>
                            <p class="text-sm text-slate-500 capitalize">{{ user.role }} · {{ user.email }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <SearchSelect v-model="selectedPeriod" @update:modelValue="changePeriod" size="sm"
                        :options="[{value:'7',label:'Last 7 days'},{value:'30',label:'Last 30 days'},{value:'90',label:'Last 90 days'}]"
                        placeholder="Select period…" />
                    <a :href="admin_url" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
            </div>

            <!-- KPI strip -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ total_hours }}h</p>
                    <p class="text-xs text-slate-500 mt-0.5">Hours logged</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ kpi.completed }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Tasks completed</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-violet-600 dark:text-violet-400">{{ kpi.open }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Open tasks</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ kpi.reports }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Daily reports</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
                <!-- Hours by Project -->
                <div class="card p-5">
                    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Hours by Project</h2>
                    <div v-if="project_hours.length" class="space-y-3">
                        <div v-for="p in project_hours" :key="p.name">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ p.name }}</span>
                                <span class="text-xs font-semibold text-slate-900 dark:text-white ml-2 flex-shrink-0">{{ p.hours }}h</span>
                            </div>
                            <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full">
                                <div class="h-2 bg-violet-500 rounded-full" :style="'width:' + Math.round(p.hours/maxPH*100) + '%'"></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400 text-center py-6">No time logged this period.</p>
                </div>

                <!-- Daily Hours -->
                <div class="card p-5">
                    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Daily Hours</h2>
                    <div v-if="daily_breakdown.length" class="flex items-end gap-1.5 h-28">
                        <div v-for="d in daily_breakdown" :key="d.date"
                             class="flex-1 flex flex-col items-center gap-1 group min-w-0">
                            <div class="w-full rounded-t transition-colors group-hover:opacity-80 bg-violet-500"
                                 :style="'height:' + Math.max(4, Math.round(d.hours/maxDH*100)) + 'px'"
                                 :title="d.date + ': ' + d.hours + 'h'"></div>
                            <span class="text-[9px] text-slate-400 truncate">{{ d.day }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400 text-center py-6">No data yet.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- Open Tasks -->
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="font-semibold text-slate-900 dark:text-white">Open Tasks ({{ kpi.open }})</h2>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-80 overflow-y-auto">
                        <template v-if="open_tasks.length">
                            <div v-for="task in open_tasks" :key="task.id"
                                 class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" :class="task.status_dot"></div>
                                <div class="flex-1 min-w-0">
                                    <a :href="task.show_url"
                                       class="text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600 truncate block">{{ task.title }}</a>
                                    <p class="text-xs text-slate-400">
                                        {{ task.project }}
                                        <span v-if="task.due_label" :class="task.is_overdue ? 'text-rose-500' : ''"> · {{ task.due_label }}</span>
                                    </p>
                                </div>
                                <span class="flex-shrink-0" :class="'badge badge-' + task.priority">{{ task.priority?.charAt(0).toUpperCase() + task.priority?.slice(1) }}</span>
                            </div>
                        </template>
                        <div v-else class="py-10 text-center text-slate-400 text-sm">
                            <i class="bi bi-check2-all text-2xl block mb-2"></i>
                            All caught up!
                        </div>
                    </div>
                </div>

                <!-- Recent Daily Reports -->
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="font-semibold text-slate-900 dark:text-white">Recent Daily Reports ({{ kpi.reports }})</h2>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-80 overflow-y-auto">
                        <template v-if="daily_reports.length">
                            <a v-for="report in daily_reports" :key="report.id" :href="report.show_url"
                               class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors block">
                                <div class="flex-shrink-0 text-center w-10 mt-0.5">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ report.day }}</p>
                                    <p class="text-[10px] text-slate-400">{{ report.month }}</p>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ report.summary }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ report.task_count }} tasks · {{ report.total_hours }}h logged
                                        <span v-if="report.has_blocker" class="text-rose-500"> · Has blockers</span>
                                    </p>
                                </div>
                            </a>
                        </template>
                        <div v-else class="py-10 text-center text-slate-400 text-sm">
                            <i class="bi bi-journal-x text-2xl block mb-2"></i>
                            No reports this period.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
