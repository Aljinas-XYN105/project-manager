<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    logs: Object,
    users: Array,
    stats: Object,
    categories: Object,
    filters: Object,
    export_url: String,
    timezone: String,
})

const form = ref({
    q: props.filters?.q || '',
    user_id: props.filters?.user_id || '',
    category: props.filters?.category || '',
    from: props.filters?.from || '',
    to: props.filters?.to || '',
})

const openLogs = ref({})

function toggleLog(id) {
    openLogs.value[id] = !openLogs.value[id]
}

function applyFilters() {
    router.get(route('admin.activity.index'), { ...form.value })
}

function resetFilters() {
    router.get(route('admin.activity.index'))
}

const kpiCards = computed(() => [
    { label: 'Events today', value: props.stats.today, icon: 'bi-activity', color: 'violet' },
    { label: 'Sign-ins today', value: props.stats.logins_today, icon: 'bi-box-arrow-in-right', color: 'emerald' },
    { label: 'Failed sign-ins today', value: props.stats.failed_today, icon: 'bi-shield-exclamation', color: 'rose' },
    { label: 'Active users today', value: props.stats.active_users, icon: 'bi-people', color: 'blue' },
])

const colorBg = { violet: 'bg-violet-100 dark:bg-violet-900/30', emerald: 'bg-emerald-100 dark:bg-emerald-900/30', rose: 'bg-rose-100 dark:bg-rose-900/30', blue: 'bg-blue-100 dark:bg-blue-900/30' }
const colorText = { violet: 'text-violet-600 dark:text-violet-400', emerald: 'text-emerald-600 dark:text-emerald-400', rose: 'text-rose-600 dark:text-rose-400', blue: 'text-blue-600 dark:text-blue-400' }

function logStyle(description) {
    if (description === 'failed login attempt') return ['bi-shield-exclamation', 'rose']
    if (description === 'logged in') return ['bi-box-arrow-in-right', 'emerald']
    if (description === 'logged out') return ['bi-box-arrow-right', 'slate']
    if (description?.startsWith('deleted') || description?.startsWith('removed')) return ['bi-trash3', 'rose']
    if (description?.startsWith('created') || description?.startsWith('added')) return ['bi-plus-circle', 'violet']
    return ['bi-pencil-square', 'blue']
}

// Group logs by date_label for section headers
function needsDateHeader(idx) {
    if (!props.logs?.data?.length) return false
    if (idx === 0) return true
    return props.logs.data[idx].date_label !== props.logs.data[idx - 1].date_label
}
</script>

<template>
    <AppLayout title="Activity Log">
        <div class="page-header">
            <div>
                <h1 class="page-title">Activity Log</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Every sign-in, sign-out and change made by every user. Times shown in {{ timezone }}.</p>
            </div>
            <a :href="export_url" class="btn-secondary"><i class="bi bi-download"></i> Export CSV</a>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div v-for="card in kpiCards" :key="card.label" class="card p-4 flex items-center gap-3">
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', colorBg[card.color]]">
                    <i :class="['bi', card.icon, colorText[card.color]]"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ card.value }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <form @submit.prevent="applyFilters" class="card p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div class="lg:col-span-2">
                <label class="form-label">Search</label>
                <input v-model="form.q" type="search" class="form-input" placeholder="e.g. deleted, logged in, project name…">
            </div>
            <div>
                <label class="form-label">User</label>
                <select v-model="form.user_id" class="form-input">
                    <option value="">All users</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">Type</label>
                <select v-model="form.category" class="form-input">
                    <option value="">All types</option>
                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">From</label>
                <input v-model="form.from" type="date" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input v-model="form.to" type="date" class="form-input">
            </div>
            <div class="sm:col-span-2 lg:col-span-6 flex justify-end gap-2">
                <button v-if="filters?.q || filters?.user_id || filters?.category || filters?.from || filters?.to"
                        type="button" @click="resetFilters" class="btn-ghost">Clear</button>
                <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>

        <!-- Log -->
        <div class="card overflow-hidden">
            <template v-if="logs?.data?.length">
                <template v-for="(log, idx) in logs.data" :key="log.id">
                    <div v-if="needsDateHeader(idx)"
                         class="px-5 py-2 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        {{ log.date_label }}
                    </div>
                    <div class="border-b border-slate-50 dark:border-slate-800 last:border-b-0">
                        <button type="button" @click="toggleLog(log.id)"
                                class="w-full flex items-center gap-3 px-5 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                                          'bg-' + logStyle(log.description)[1] + '-100 dark:bg-' + logStyle(log.description)[1] + '-900/30']">
                                <i :class="['bi text-sm', logStyle(log.description)[0],
                                            'text-' + logStyle(log.description)[1] + '-600 dark:text-' + logStyle(log.description)[1] + '-400']"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-700 dark:text-slate-300 truncate">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ log.user_name }}</span>
                                    {{ log.description }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-2 flex-wrap">
                                    <span>{{ log.time_local }}</span>
                                    <span v-if="log.properties?.ip" class="font-mono">{{ log.properties.ip }}</span>
                                    <span class="px-1.5 py-px rounded bg-slate-100 dark:bg-slate-800 text-[10px]">{{ log.category }}</span>
                                    <span v-if="(log.properties?.status || 200) >= 400" class="text-rose-500">failed ({{ log.properties.status }})</span>
                                </p>
                            </div>
                            <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform" :class="openLogs[log.id] ? 'rotate-180' : ''"></i>
                        </button>
                        <div v-show="openLogs[log.id]" class="px-5 pb-4 pl-16">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
                                <div v-if="log.user_email"><dt class="inline text-slate-400">Email:</dt> <dd class="inline text-slate-700 dark:text-slate-300">{{ log.user_email }}</dd></div>
                                <template v-for="(label, key) in {method:'Method', url:'URL', route:'Route', user_agent:'Browser'}" :key="key">
                                    <div v-if="log.properties?.[key]" class="truncate">
                                        <dt class="inline text-slate-400">{{ label }}:</dt>
                                        <dd class="inline text-slate-700 dark:text-slate-300 font-mono">{{ log.properties[key] }}</dd>
                                    </div>
                                </template>
                            </dl>
                            <pre v-if="log.properties?.input"
                                 class="mt-3 text-[11px] bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg p-3 overflow-x-auto text-slate-600 dark:text-slate-400">{{ JSON.stringify(log.properties.input, null, 2) }}</pre>
                        </div>
                    </div>
                </template>
            </template>
            <div v-else class="empty-state py-16">
                <i class="bi bi-activity text-4xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 mt-3">No activity matches these filters.</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="logs?.last_page > 1" class="mt-4 flex gap-2 flex-wrap">
            <template v-for="link in logs.links" :key="link.label">
                <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300" v-html="link.label"></span>
                <button v-else @click="router.get(link.url)"
                        class="px-2 py-1 text-sm rounded"
                        :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        v-html="link.label"></button>
            </template>
        </div>
    </AppLayout>
</template>
