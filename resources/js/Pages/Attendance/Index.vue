<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    byDay: Object,
    month: String,
    month_label: String,
    prev_month: String,
    next_month: String,
    summary: Object,
    open: Object,
    clock_in_url: String,
    clock_out_url: String,
    timezone: String,
})

function navigate(month) {
    router.get(route('attendance.index'), { month }, { preserveState: false })
}

function clockAction() {
    router.post(props.open ? props.clock_out_url : props.clock_in_url, {}, { preserveScroll: false })
}

function fmtMins(m) {
    return `${Math.floor(m / 60)}h ${String(m % 60).padStart(2, '0')}m`
}

const days = Object.entries(props.byDay ?? {}).map(([date, sessions]) => ({ date, sessions }))
</script>

<template>
    <AppLayout title="My Attendance">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Attendance</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Times shown in {{ timezone }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                    <button @click="navigate(prev_month)" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></button>
                    <input type="month" :value="month" @change="navigate($event.target.value)" class="form-input py-1.5 text-sm w-40">
                    <button @click="navigate(next_month)" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></button>
                </div>
                <button @click="clockAction" :class="open ? 'btn-secondary' : 'btn-primary'">
                    <i :class="`bi ${open ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right'}`"></i>
                    {{ open ? 'Clock out' : 'Clock in' }}
                </button>
            </div>
        </div>

        <!-- Active session banner -->
        <div v-if="open" class="card p-4 mb-6 flex items-center gap-3 border-l-4 border-l-emerald-400">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <p class="text-sm text-slate-700 dark:text-slate-300">
                You're clocked in since <strong>{{ open.clock_in_at }}</strong>
                ({{ open.duration_formatted }} so far).
            </p>
        </div>

        <!-- Summary stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div v-for="item in [
                { label: 'Days present', value: summary.days, icon: 'bi-calendar-check', color: 'violet' },
                { label: 'Total time', value: summary.total_formatted, icon: 'bi-hourglass-split', color: 'emerald' },
                { label: 'Average per day', value: summary.avg_formatted, icon: 'bi-graph-up', color: 'blue' },
                { label: 'Avg. first sign-in', value: summary.first_in_avg, icon: 'bi-sunrise', color: 'amber' },
            ]" :key="item.label" class="card p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     :class="`bg-${item.color}-100 dark:bg-${item.color}-900/30`">
                    <i :class="`bi ${item.icon} text-${item.color}-600 dark:text-${item.color}-400`"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white leading-none">{{ item.value }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ item.label }}</p>
                </div>
            </div>
        </div>

        <!-- Records -->
        <div class="card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-white">{{ month_label }}</h2>
            </div>
            <div v-if="days.length === 0" class="py-14 text-center">
                <i class="bi bi-calendar-x text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                <p class="text-sm text-slate-500">No attendance recorded in {{ month_label }}.</p>
                <p class="text-xs text-slate-400 mt-1">Signing in records your start time automatically; signing out records your end time.</p>
            </div>
            <div v-for="{ date, sessions } in days" :key="date"
                 class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5 border-b border-slate-50 dark:border-slate-800 last:border-b-0">
                <div class="w-32 flex-shrink-0">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ new Date(date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                    </p>
                    <p class="text-xs text-slate-400">{{ sessions.length }} {{ sessions.length === 1 ? 'session' : 'sessions' }}</p>
                </div>
                <div class="flex-1 flex flex-wrap gap-2">
                    <span v-for="s in sessions" :key="s.id"
                          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300">
                        <i class="bi bi-box-arrow-in-right text-emerald-500"></i>{{ s.clock_in_at }}
                        <i class="bi bi-arrow-right text-slate-300"></i>
                        <template v-if="s.clock_out_at">
                            <i class="bi bi-box-arrow-right text-rose-400"></i>{{ s.clock_out_at }}
                        </template>
                        <span v-else class="text-emerald-600 font-medium">now</span>
                        <span v-if="s.source !== 'login'" class="text-[10px] text-slate-400">
                            ({{ s.source === 'admin' ? 'added by admin' : 'manual' }})
                        </span>
                    </span>
                </div>
                <span class="font-mono text-sm font-semibold text-slate-900 dark:text-white tabular-nums">
                    {{ fmtMins(sessions.reduce((sum, s) => sum + s.duration_minutes, 0)) }}
                </span>
            </div>
        </div>
    </AppLayout>
</template>
