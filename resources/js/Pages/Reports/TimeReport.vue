<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    from: String,
    to: String,
    summary: Object,
    by_project: Array,
    entries: Object,
    projects: Array,
    users: Array,
    filters: Object,
    export_url: String,
})

const form = ref({
    from: props.filters?.from || props.from,
    to: props.filters?.to || props.to,
    project_id: props.filters?.project_id || '',
    user_id: props.filters?.user_id || '',
})

function applyFilters() {
    router.get(route('reports.time'), { ...form.value })
}

function resetFilters() {
    router.get(route('reports.time'))
}

function exportPdf() {
    window.location.href = route('reports.time') + '?' + new URLSearchParams({ ...form.value, export: 'pdf' })
}
</script>

<template>
    <AppLayout title="Time Report">
        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('reports.index')" class="hover:text-violet-600 transition-colors">Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Time</span>
                    </div>
                    <h1 class="page-title">Time Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ from }} — {{ to }}</p>
                </div>
                <button @click="exportPdf" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Export PDF
                </button>
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
                        <label class="form-label">Project</label>
                        <select v-model="form.project_id" class="form-input">
                            <option value="">All Projects</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">User</label>
                        <select v-model="form.user_id" class="form-input">
                            <option value="">All Users</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="stat-card">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Total Hours</span>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ summary.total_hours }}h</div>
                </div>
                <div class="stat-card">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Billable Hours</span>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ summary.billable_hours }}h</div>
                    <p class="text-xs text-slate-500">{{ summary.total_hours > 0 ? Math.round(summary.billable_hours / summary.total_hours * 100) : 0 }}% of total</p>
                </div>
                <div class="stat-card">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Entries</span>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ summary.entry_count }}</div>
                </div>
            </div>

            <!-- By Project -->
            <div v-if="by_project?.length" class="card">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Hours by Project</h2>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    <div v-for="row in by_project" :key="row.name" class="p-4 flex items-center gap-4">
                        <div class="w-3 h-3 rounded-full flex-shrink-0" :style="'background:' + row.color"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ row.name }}</p>
                            <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full mt-1.5">
                                <div class="h-1.5 rounded-full" :style="'width:' + row.pct + '%; background:' + row.color"></div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ row.hours }}h</p>
                            <p class="text-xs text-slate-500">{{ row.pct }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Entries -->
            <div class="card">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Detailed Entries</h2>
                    <span class="text-sm text-slate-500">{{ entries.total }} entries</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Date</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">User</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Project / Task</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Duration</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Billable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-if="entries.data?.length">
                                <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ entry.date }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <img :src="entry.user_avatar" class="w-6 h-6 rounded-full" alt="">
                                            <span class="text-slate-700 dark:text-slate-300">{{ entry.user_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ entry.project }}</p>
                                        <p class="text-xs text-slate-500">{{ entry.task }}</p>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-slate-900 dark:text-white">{{ entry.duration }}</td>
                                    <td class="px-5 py-3">
                                        <span v-if="entry.is_billable" class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Billable</span>
                                        <span v-else class="badge bg-slate-100 text-slate-500">Non-billable</span>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-400">No time entries for this period.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="entries.last_page > 1" class="p-4 border-t border-slate-200 dark:border-slate-700 flex gap-2 flex-wrap">
                    <template v-for="link in entries.links" :key="link.label">
                        <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300 dark:text-slate-600" v-html="link.label"></span>
                        <button v-else @click="router.get(link.url)"
                                class="px-2 py-1 text-sm rounded"
                                :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                v-html="link.label"></button>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
