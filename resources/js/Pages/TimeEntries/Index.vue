<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    grouped: Object,
    from: String,
    to: String,
    total_hours: Number,
    entry_count: Number,
})

const fromVal = ref(props.from)
const toVal   = ref(props.to)

function applyFilter() {
    router.get(route('time-entries.index'), { from: fromVal.value, to: toVal.value }, { preserveState: true })
}

function fmtSecs(s) {
    s = Math.max(0, s)
    return [Math.floor(s / 3600), Math.floor((s % 3600) / 60), s % 60].map(n => String(n).padStart(2, '0')).join(':')
}

// Live tickers for running entries
const tickers = ref({})
const liveElapsed = ref({})

function startTicker(entry) {
    if (!entry.is_live) return
    liveElapsed.value[entry.id] = entry.elapsed_seconds
    if (entry.status === 'running') {
        tickers.value[entry.id] = setInterval(() => {
            const now = Math.floor(Date.now() / 1000)
            liveElapsed.value[entry.id] = Math.max(0, now - entry.started_at_ts - (entry.paused_seconds ?? 0))
        }, 1000)
    }
}

onMounted(() => {
    Object.values(props.grouped).flat().forEach(e => startTicker(e))
})

onUnmounted(() => {
    Object.values(tickers.value).forEach(t => clearInterval(t))
})

function stopEntry(entry) {
    router.post(entry.stop_url, {}, { preserveScroll: true })
}

function pauseEntry(entry) {
    router.patch(entry.pause_url, {}, { preserveScroll: true })
}

function resumeEntry(entry) {
    router.patch(entry.resume_url, {}, { preserveScroll: true })
}

function deleteEntry(entry) {
    if (!confirm('Delete this time entry?')) return
    router.delete(entry.destroy_url, { preserveScroll: true })
}

function fmtDate(dateStr) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' })
}

const days = Object.entries(props.grouped ?? {})
</script>

<template>
    <AppLayout title="Time Tracking">
        <div class="page-header">
            <div>
                <h1 class="page-title">Time Tracking</h1>
                <p class="text-sm text-slate-500 mt-0.5">Start timers from any task page or the ⏱ icon on task cards.</p>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="card p-4">
                <span class="text-xs text-slate-500 font-medium uppercase tracking-wide block mb-1">Total Hours</span>
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ total_hours }}h</span>
            </div>
            <div class="card p-4">
                <span class="text-xs text-slate-500 font-medium uppercase tracking-wide block mb-1">Entries</span>
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ entry_count }}</span>
            </div>
        </div>

        <!-- Filter -->
        <form @submit.prevent="applyFilter" class="flex gap-3 mb-5">
            <input v-model="fromVal" type="date" class="form-input text-sm">
            <input v-model="toVal"   type="date" class="form-input text-sm">
            <button type="submit" class="btn-primary btn-sm">Filter</button>
        </form>

        <!-- Empty state -->
        <div v-if="days.length === 0" class="card py-14 text-center">
            <i class="bi bi-stopwatch text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
            <p class="text-sm text-slate-500">No time entries in this period.</p>
        </div>

        <!-- Grouped by day -->
        <div v-for="[date, entries] in days" :key="date" class="mb-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ fmtDate(date) }}</h3>
                <span class="text-xs text-slate-500">
                    {{ fmtSecs(entries.reduce((sum, e) => sum + Math.max(0, liveElapsed[e.id] ?? e.elapsed_seconds), 0)) }}
                </span>
            </div>
            <div class="card overflow-hidden divide-y divide-slate-50 dark:divide-slate-700">
                <div v-for="entry in entries" :key="entry.id" class="flex items-center gap-4 px-4 py-3">
                    <div class="w-2 h-8 rounded-full flex-shrink-0"
                         :class="entry.status === 'running' ? 'bg-emerald-400 dark:bg-emerald-600' : entry.status === 'paused' ? 'bg-amber-300 dark:bg-amber-600' : 'bg-slate-200 dark:bg-slate-700'"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                            {{ entry.description || entry.task_title || 'Untitled' }}
                        </p>
                        <p v-if="entry.project_name" class="text-xs text-slate-400 mt-0.5">{{ entry.project_name }}</p>
                    </div>
                    <span class="font-mono text-sm tabular-nums text-slate-900 dark:text-white">
                        {{ fmtSecs(liveElapsed[entry.id] ?? entry.elapsed_seconds) }}
                    </span>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <template v-if="entry.is_live">
                            <button v-if="entry.status === 'running'" @click="pauseEntry(entry)"
                                    class="btn-ghost btn-sm" title="Pause"><i class="bi bi-pause-fill"></i></button>
                            <button v-else @click="resumeEntry(entry)"
                                    class="btn-ghost btn-sm text-emerald-600" title="Resume"><i class="bi bi-play-fill"></i></button>
                            <button @click="stopEntry(entry)" class="btn-ghost btn-sm text-rose-500" title="Stop">
                                <i class="bi bi-stop-fill"></i>
                            </button>
                        </template>
                        <button @click="deleteEntry(entry)" class="btn-ghost btn-sm text-slate-400 hover:text-rose-500" title="Delete">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
