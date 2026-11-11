<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    rows: Array,
    from: String,
    to: String,
    users: Array,
    export_url: String,
})

const dateFrom = ref(props.from)
const dateTo   = ref(props.to)

function applyFilter() {
    router.get(route('admin.attendance.summary'), { from: dateFrom.value, to: dateTo.value })
}

function exportCsv() {
    window.location.href = props.export_url + '?from=' + dateFrom.value + '&to=' + dateTo.value
}

// Schedule modal
const scheduleModal  = ref(false)
const scheduleRow    = ref(null)
const scheduleForm   = ref({})
const scheduleErrors = ref({})
const allDays = [
    { key: 'mon', label: 'Mon' }, { key: 'tue', label: 'Tue' }, { key: 'wed', label: 'Wed' },
    { key: 'thu', label: 'Thu' }, { key: 'fri', label: 'Fri' },
    { key: 'sat', label: 'Sat' }, { key: 'sun', label: 'Sun' },
]

function openSchedule(row) {
    scheduleRow.value  = row
    scheduleForm.value = {
        expected_hours_per_day: row.expected_hours,
        expected_start_time:    row.expected_start || '',
        work_days:              [...(row.work_days || ['mon','tue','wed','thu','fri'])],
    }
    scheduleErrors.value = {}
    scheduleModal.value  = true
}

function toggleDay(day) {
    const days = scheduleForm.value.work_days
    const idx  = days.indexOf(day)
    if (idx === -1) days.push(day)
    else days.splice(idx, 1)
}

function saveSchedule() {
    router.put(scheduleRow.value.schedule_url, scheduleForm.value, {
        onSuccess:    () => { scheduleModal.value = false },
        onError:      (e) => { scheduleErrors.value = e },
        preserveScroll: true,
    })
}
</script>

<template>
    <AppLayout title="Working Hours Report">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('admin.dashboard')" class="hover:text-violet-600 transition-colors">Admin</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <a :href="route('admin.attendance.index')" class="hover:text-violet-600 transition-colors">Attendance</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Working Hours</span>
                </div>
                <h1 class="page-title">Working Hours Report</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Expected vs actual hours per member with overtime and late-arrival detection.</p>
            </div>
            <button @click="exportCsv" class="btn-secondary">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>

        <!-- Date filter -->
        <div class="card p-4 mb-6">
            <form @submit.prevent="applyFilter" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="form-label">From</label>
                    <input v-model="dateFrom" type="date" class="form-input">
                </div>
                <div>
                    <label class="form-label">To</label>
                    <input v-model="dateTo" type="date" class="form-input">
                </div>
                <button type="submit" class="btn-primary">Apply</button>
                <span class="text-xs text-slate-400 self-center">{{ from }} → {{ to }}</span>
            </form>
        </div>

        <!-- Summary table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                            <th class="px-5 py-3 text-left font-medium text-slate-500">Member</th>
                            <th class="px-5 py-3 text-right font-medium text-slate-500">Days present</th>
                            <th class="px-5 py-3 text-right font-medium text-slate-500">Working days</th>
                            <th class="px-5 py-3 text-right font-medium text-slate-500">Expected</th>
                            <th class="px-5 py-3 text-right font-medium text-slate-500">Actual</th>
                            <th class="px-5 py-3 text-right font-medium text-slate-500">Difference</th>
                            <th class="px-5 py-3 text-center font-medium text-slate-500">Late days</th>
                            <th class="px-5 py-3 text-center font-medium text-slate-500">Schedule</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <tr v-for="row in rows" :key="row.user_id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                            <!-- Member -->
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <img :src="row.avatar_url" class="w-7 h-7 rounded-full flex-shrink-0" alt="">
                                    <a :href="row.show_url" class="font-medium text-slate-900 dark:text-white hover:text-violet-600 transition-colors">
                                        {{ row.user_name }}
                                    </a>
                                </div>
                            </td>
                            <!-- Days present -->
                            <td class="px-5 py-3 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                {{ row.days_present }}
                            </td>
                            <!-- Working days in period -->
                            <td class="px-5 py-3 text-right tabular-nums text-slate-500">{{ row.working_days }}</td>
                            <!-- Expected -->
                            <td class="px-5 py-3 text-right tabular-nums text-slate-500 font-mono text-xs">
                                {{ row.expected_formatted }}
                            </td>
                            <!-- Actual -->
                            <td class="px-5 py-3 text-right tabular-nums font-mono text-xs font-semibold text-slate-900 dark:text-white">
                                {{ row.total_formatted }}
                            </td>
                            <!-- Diff -->
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium tabular-nums"
                                      :class="row.overtime
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : row.shortfall
                                                ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'">
                                    <i :class="row.overtime ? 'bi-arrow-up' : row.shortfall ? 'bi-arrow-down' : 'bi-dash'"
                                       class="bi text-[10px]"></i>
                                    {{ row.diff_formatted }}
                                </span>
                            </td>
                            <!-- Late days -->
                            <td class="px-5 py-3 text-center">
                                <span v-if="row.late_days > 0"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    <i class="bi bi-clock-history text-[10px]"></i> {{ row.late_days }}
                                </span>
                                <span v-else class="text-slate-300 dark:text-slate-600 text-xs">—</span>
                            </td>
                            <!-- Schedule -->
                            <td class="px-5 py-3 text-center">
                                <button @click="openSchedule(row)"
                                        class="btn-ghost btn-xs" title="Edit work schedule">
                                    <i class="bi bi-gear"></i>
                                    <span class="text-xs text-slate-400 ml-1 hidden lg:inline">{{ row.expected_hours }}h/day</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!rows?.length">
                            <td colspan="8" class="px-5 py-10 text-center text-slate-400">No data for this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-emerald-400"></span> Overtime (worked more than expected)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-400"></span> Shortfall (worked less than expected)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-amber-400"></span> Late arrival (> 10 min after expected start)
            </span>
        </div>

        <!-- Work schedule modal -->
        <Teleport to="body">
            <div v-if="scheduleModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-sm p-6 space-y-5">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Work Schedule</h2>
                        <p class="text-sm text-slate-500 mt-0.5">{{ scheduleRow?.user_name }}</p>
                    </div>

                    <!-- Hours per day -->
                    <div>
                        <label class="form-label">Expected hours per day</label>
                        <div class="relative">
                            <input v-model.number="scheduleForm.expected_hours_per_day"
                                   type="number" min="0.5" max="24" step="0.5" class="form-input pr-10">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">h</span>
                        </div>
                        <p v-if="scheduleErrors.expected_hours_per_day" class="form-error">{{ scheduleErrors.expected_hours_per_day }}</p>
                    </div>

                    <!-- Expected start time -->
                    <div>
                        <label class="form-label">Expected start time <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input v-model="scheduleForm.expected_start_time" type="time" class="form-input">
                        <p class="text-xs text-slate-400 mt-1">Used to detect late arrivals (10-min grace period).</p>
                    </div>

                    <!-- Work days -->
                    <div>
                        <label class="form-label">Working days</label>
                        <div class="flex gap-2 flex-wrap mt-1">
                            <button v-for="d in allDays" :key="d.key" type="button"
                                    @click="toggleDay(d.key)"
                                    :class="scheduleForm.work_days.includes(d.key)
                                        ? 'bg-violet-600 text-white border-violet-600'
                                        : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600 hover:border-violet-400'"
                                    class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors">
                                {{ d.label }}
                            </button>
                        </div>
                        <p v-if="scheduleErrors.work_days" class="form-error">{{ scheduleErrors.work_days }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="scheduleModal = false" class="btn-ghost">Cancel</button>
                        <button type="button" @click="saveSchedule" class="btn-primary">Save schedule</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
