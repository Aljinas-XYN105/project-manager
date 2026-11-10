<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    month_label: String,
    month_param: String,
    cal_days: Array,
    leave_types: Array,
    departments: Array,
    filters: Object,
    prev_url: String,
    next_url: String,
    this_month_url: String,
    leave_index_url: String,
})

const selectedDept = ref(props.filters?.department ?? '')

function changeDept() {
    router.get(route('leave.calendar', { month: props.month_param, department: selectedDept.value || undefined }))
}

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

// Hardcoded so Tailwind scanner picks up all these classes
const colorMap = {
    blue:    { bg: 'bg-blue-100 dark:bg-blue-900/30',    text: 'text-blue-600 dark:text-blue-400',    dot: 'bg-blue-500' },
    green:   { bg: 'bg-green-100 dark:bg-green-900/30',   text: 'text-green-600 dark:text-green-400',   dot: 'bg-green-500' },
    amber:   { bg: 'bg-amber-100 dark:bg-amber-900/30',   text: 'text-amber-600 dark:text-amber-400',   dot: 'bg-amber-500' },
    rose:    { bg: 'bg-rose-100 dark:bg-rose-900/30',     text: 'text-rose-600 dark:text-rose-400',     dot: 'bg-rose-500' },
    violet:  { bg: 'bg-violet-100 dark:bg-violet-900/30', text: 'text-violet-600 dark:text-violet-400', dot: 'bg-violet-500' },
    cyan:    { bg: 'bg-cyan-100 dark:bg-cyan-900/30',     text: 'text-cyan-600 dark:text-cyan-400',     dot: 'bg-cyan-500' },
    orange:  { bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-600 dark:text-orange-400', dot: 'bg-orange-500' },
    teal:    { bg: 'bg-teal-100 dark:bg-teal-900/30',     text: 'text-teal-600 dark:text-teal-400',     dot: 'bg-teal-500' },
    indigo:  { bg: 'bg-indigo-100 dark:bg-indigo-900/30', text: 'text-indigo-600 dark:text-indigo-400', dot: 'bg-indigo-500' },
    purple:  { bg: 'bg-purple-100 dark:bg-purple-900/30', text: 'text-purple-600 dark:text-purple-400', dot: 'bg-purple-500' },
    pink:    { bg: 'bg-pink-100 dark:bg-pink-900/30',     text: 'text-pink-600 dark:text-pink-400',     dot: 'bg-pink-500' },
    slate:   { bg: 'bg-slate-100 dark:bg-slate-800',      text: 'text-slate-600 dark:text-slate-400',   dot: 'bg-slate-500' },
    red:     { bg: 'bg-red-100 dark:bg-red-900/30',       text: 'text-red-600 dark:text-red-400',       dot: 'bg-red-500' },
    yellow:  { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-600 dark:text-yellow-400', dot: 'bg-yellow-500' },
    lime:    { bg: 'bg-lime-100 dark:bg-lime-900/30',     text: 'text-lime-600 dark:text-lime-400',     dot: 'bg-lime-500' },
    emerald: { bg: 'bg-emerald-100 dark:bg-emerald-900/30', text: 'text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
}

function leaveChipClass(color) {
    const c = colorMap[color] ?? colorMap.slate
    return `${c.bg} ${c.text}`
}
function legendDot(color) {
    return (colorMap[color] ?? colorMap.slate).dot
}
</script>

<template>
    <AppLayout title="Team Leave Calendar">
        <div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">{{ month_label }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Who's off — approved leave, pending requests (striped) and company holidays.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select v-model="selectedDept" @change="changeDept" class="form-input text-sm w-auto">
                        <option value="">All departments</option>
                        <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                    </select>
                    <Link :href="prev_url" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></Link>
                    <Link :href="this_month_url" class="btn-secondary btn-sm">This month</Link>
                    <Link :href="next_url" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></Link>
                    <Link :href="leave_index_url" class="btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Request leave
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <!-- Week day headers -->
                <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
                    <div v-for="d in weekDays" :key="d"
                         class="px-2 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        {{ d }}
                    </div>
                </div>

                <!-- Calendar grid -->
                <div class="grid grid-cols-7">
                    <div v-for="(day, i) in cal_days" :key="day.date"
                         class="min-h-[108px] border-b border-r border-slate-100 dark:border-slate-800 p-1.5"
                         :class="[
                             !day.in_month ? 'bg-slate-50/60 dark:bg-slate-900/40' : (day.is_weekend ? 'bg-slate-50/40 dark:bg-slate-800/20' : ''),
                             day.holiday ? '!bg-rose-50/60 dark:!bg-rose-900/10' : '',
                             (i + 1) % 7 === 0 ? 'border-r-0' : '',
                         ]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-medium"
                                  :class="day.is_today
                                      ? 'bg-violet-600 text-white'
                                      : day.in_month ? 'text-slate-700 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600'">
                                {{ day.day }}
                            </span>
                            <span v-if="day.holiday" class="text-[10px] text-rose-600 font-medium truncate ml-1" :title="day.holiday.name">
                                {{ day.holiday.name }}
                            </span>
                        </div>

                        <template v-if="!day.is_weekend && !day.holiday">
                            <div class="space-y-0.5">
                                <div v-for="(leave, li) in day.leaves.slice(0, 3)" :key="li"
                                     class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] truncate"
                                     :class="[leaveChipClass(leave.type_color), leave.status === 'pending' ? 'opacity-70 border border-dashed border-current' : '']"
                                     :title="`${leave.name} — ${leave.type_name}${leave.status === 'pending' ? ' (pending)' : ''}${leave.is_half_day ? ', half day' : ''}`">
                                    <span class="truncate">{{ leave.name.split(' ')[0] }}{{ leave.is_half_day ? ' ½' : '' }}</span>
                                </div>
                                <p v-if="day.total_count > 3" class="text-[10px] text-slate-400 pl-1">
                                    +{{ day.total_count - 3 }} more
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div v-if="leave_types?.length" class="flex flex-wrap gap-3 mt-4 text-xs text-slate-500">
                <span v-for="t in leave_types" :key="t.name" class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full" :class="legendDot(t.color)"></span>
                    {{ t.name }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-2.5 rounded border border-dashed border-slate-400"></span>Pending
                </span>
            </div>
        </div>
    </AppLayout>
</template>
