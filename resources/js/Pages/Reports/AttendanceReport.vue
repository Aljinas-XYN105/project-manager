<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    rows:         Array,
    summary:      Object,
    working_days: Number,
    from:         String,
    to:           String,
    users:        Array,
    filters:      Object,
})

const form = ref({
    from:    props.filters?.from    || props.from,
    to:      props.filters?.to      || props.to,
    user_id: props.filters?.user_id || '',
})

function applyFilters() {
    router.get(route('reports.attendance'), { ...form.value })
}

function resetFilters() {
    router.get(route('reports.attendance'))
}

function fmtMins(m) {
    if (!m) return '0h 00m'
    return `${Math.floor(m / 60)}h ${String(m % 60).padStart(2, '0')}m`
}

function pctColor(pct) {
    if (pct >= 90) return 'bg-emerald-500'
    if (pct >= 70) return 'bg-amber-400'
    return 'bg-rose-400'
}

function pctTextColor(pct) {
    if (pct >= 90) return 'text-emerald-600 dark:text-emerald-400'
    if (pct >= 70) return 'text-amber-600 dark:text-amber-400'
    return 'text-rose-500 dark:text-rose-400'
}

const sortKey = ref('days_present')
const sortDir = ref(-1)

function sort(key) {
    if (sortKey.value === key) sortDir.value *= -1
    else { sortKey.value = key; sortDir.value = -1 }
}

const sorted = computed(() =>
    [...props.rows].sort((a, b) => {
        const av = a[sortKey.value] ?? 0
        const bv = b[sortKey.value] ?? 0
        return typeof av === 'string' ? av.localeCompare(bv) * sortDir.value : (av - bv) * sortDir.value
    })
)

function sortIcon(key) {
    if (sortKey.value !== key) return 'bi-chevron-expand'
    return sortDir.value === -1 ? 'bi-chevron-down' : 'bi-chevron-up'
}
</script>

<template>
    <AppLayout title="Attendance Report">
        <div class="space-y-6">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('reports.index')" class="hover:text-violet-600 transition-colors">Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Attendance</span>
                    </div>
                    <h1 class="page-title">Attendance Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ from }} — {{ to }}
                        <span class="ml-2 text-slate-400">· {{ working_days }} working day{{ working_days !== 1 ? 's' : '' }}</span>
                    </p>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Members Tracked</span>
                        <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-people-fill text-violet-600 dark:text-violet-400 text-sm"></i>
                        </span>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ summary.total_members }}</div>
                    <p class="text-xs text-slate-400 mt-1">active members</p>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Avg Attendance</span>
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <i class="bi bi-graph-up text-emerald-600 dark:text-emerald-400 text-sm"></i>
                        </span>
                    </div>
                    <div class="text-3xl font-bold" :class="pctTextColor(summary.avg_attendance_pct)">
                        {{ summary.avg_attendance_pct }}%
                    </div>
                    <p class="text-xs text-slate-400 mt-1">team average</p>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Hours</span>
                        <span class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <i class="bi bi-clock-fill text-blue-600 dark:text-blue-400 text-sm"></i>
                        </span>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ summary.total_hours }}h</div>
                    <p class="text-xs text-slate-400 mt-1">across all members</p>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Perfect Attendance</span>
                        <span class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="bi bi-award-fill text-amber-600 dark:text-amber-400 text-sm"></i>
                        </span>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ summary.perfect_attendance }}</div>
                    <p class="text-xs text-slate-400 mt-1">100% attendance</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="form-label">From</label>
                        <DatePicker v-model="form.from" />
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <DatePicker v-model="form.to" />
                    </div>
                    <div>
                        <label class="form-label">Member</label>
                        <SearchSelect v-model="form.user_id"
                            :options="users.map(u => ({value: u.id, label: u.name, avatar: u.avatar_url}))"
                            :clearable="true" clearLabel="All Members" placeholder="All Members" />
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm">
                            <i class="bi bi-funnel-fill text-xs"></i> Apply
                        </button>
                        <button type="button" @click="resetFilters" class="btn-ghost btn-sm">Reset</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    Member
                                </th>
                                <th @click="sort('days_present')"
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-700 dark:hover:text-slate-200 select-none whitespace-nowrap">
                                    Days Present <i class="bi text-[10px]" :class="sortIcon('days_present')"></i>
                                </th>
                                <th @click="sort('attendance_pct')"
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-700 dark:hover:text-slate-200 select-none">
                                    Rate <i class="bi text-[10px]" :class="sortIcon('attendance_pct')"></i>
                                </th>
                                <th @click="sort('total_hours')"
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-700 dark:hover:text-slate-200 select-none whitespace-nowrap">
                                    Total Hours <i class="bi text-[10px]" :class="sortIcon('total_hours')"></i>
                                </th>
                                <th @click="sort('avg_minutes')"
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-700 dark:hover:text-slate-200 select-none whitespace-nowrap">
                                    Avg / Day <i class="bi text-[10px]" :class="sortIcon('avg_minutes')"></i>
                                </th>
                                <th @click="sort('avg_clock_in')"
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-700 dark:hover:text-slate-200 select-none whitespace-nowrap">
                                    Avg Clock-In <i class="bi text-[10px]" :class="sortIcon('avg_clock_in')"></i>
                                </th>
                                <th @click="sort('avg_clock_out')"
                                    class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-700 dark:hover:text-slate-200 select-none whitespace-nowrap">
                                    Avg Clock-Out <i class="bi text-[10px]" :class="sortIcon('avg_clock_out')"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            <tr v-for="row in sorted" :key="row.user_id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Member -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img :src="row.avatar_url" :alt="row.user_name"
                                             class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ row.user_name }}</span>
                                    </div>
                                </td>

                                <!-- Days present -->
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ row.days_present }}</span>
                                    <span class="text-slate-400 text-xs"> / {{ row.working_days }}</span>
                                </td>

                                <!-- Rate with bar -->
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-center gap-1 min-w-[70px]">
                                        <span class="font-semibold text-sm" :class="pctTextColor(row.attendance_pct)">
                                            {{ row.attendance_pct }}%
                                        </span>
                                        <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                 :class="pctColor(row.attendance_pct)"
                                                 :style="`width:${row.attendance_pct}%`"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Total hours -->
                                <td class="px-4 py-3 text-center font-medium text-slate-700 dark:text-slate-300">
                                    {{ row.total_hours }}h
                                </td>

                                <!-- Avg per day -->
                                <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-400">
                                    {{ fmtMins(row.avg_minutes) }}
                                </td>

                                <!-- Avg clock-in -->
                                <td class="px-4 py-3 text-center">
                                    <span v-if="row.avg_clock_in !== '—'"
                                          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                                        <i class="bi bi-box-arrow-in-right text-[10px]"></i>
                                        {{ row.avg_clock_in }}
                                    </span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>

                                <!-- Avg clock-out -->
                                <td class="px-4 py-3 text-center">
                                    <span v-if="row.avg_clock_out !== '—'"
                                          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        <i class="bi bi-box-arrow-right text-[10px]"></i>
                                        {{ row.avg_clock_out }}
                                    </span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!sorted.length">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-sm">
                                    <i class="bi bi-calendar-x text-2xl block mb-2"></i>
                                    No attendance data for the selected period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
