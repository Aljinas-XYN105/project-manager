<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    from: String,
    to: String,
    summary: Object,
    leaves: Object,
    users: Array,
    leave_types: Array,
    filters: Object,
    export_url: String,
})

const form = ref({
    from: props.filters?.from || props.from,
    to: props.filters?.to || props.to,
    user_id: props.filters?.user_id || '',
    status: props.filters?.status || '',
    type_id: props.filters?.type_id || '',
})

function applyFilters() {
    router.get(route('reports.leave'), { ...form.value })
}
function resetFilters() {
    router.get(route('reports.leave'))
}
function exportPdf() {
    window.location.href = route('reports.leave') + '?' + new URLSearchParams({ ...form.value, export: 'pdf' })
}

const statusClass = {
    pending:  'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    approved: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    rejected: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
}
</script>

<template>
    <AppLayout title="Leave Report">
        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('reports.index')" class="hover:text-violet-600 transition-colors">Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Leave</span>
                    </div>
                    <h1 class="page-title">Leave Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Leave requests by employee, type, and status</p>
                </div>
                <button @click="exportPdf" class="btn-secondary btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
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
                        <label class="form-label">Employee</label>
                        <SearchSelect v-model="form.user_id"
                            :options="users.map(u => ({value: u.id, label: u.name, avatar: u.avatar_url}))"
                            :clearable="true" clearLabel="All Employees" placeholder="All Employees" />
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <SearchSelect v-model="form.status"
                            :options="[{value:'pending',label:'Pending'},{value:'approved',label:'Approved'},{value:'rejected',label:'Rejected'}]"
                            :clearable="true" clearLabel="All Statuses" placeholder="All Statuses" />
                    </div>
                    <div>
                        <label class="form-label">Type</label>
                        <SearchSelect v-model="form.type_id"
                            :options="leave_types.map(t => ({value: t.id, label: t.name}))"
                            :clearable="true" clearLabel="All Types" placeholder="All Types" />
                    </div>
                    <button type="submit" class="btn-primary">Apply</button>
                    <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
                </form>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="stat-card"><div class="text-2xl font-bold text-slate-900 dark:text-white">{{ summary.total }}</div><p class="text-xs text-slate-500">Total</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-emerald-600">{{ summary.approved }}</div><p class="text-xs text-slate-500">Approved</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-amber-600">{{ summary.pending }}</div><p class="text-xs text-slate-500">Pending</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-rose-600">{{ summary.rejected }}</div><p class="text-xs text-slate-500">Rejected</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-violet-600">{{ summary.days }}</div><p class="text-xs text-slate-500">Days approved</p></div>
            </div>

            <!-- Leaves Table -->
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                                <th class="px-5 py-3 font-medium text-slate-500">Employee</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Type</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Days</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Dates</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Status</th>
                                <th class="px-5 py-3 font-medium text-slate-500 hidden sm:table-cell">Reviewed by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-if="leaves.data?.length">
                                <tr v-for="l in leaves.data" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <img :src="l.avatar_url" class="w-6 h-6 rounded-full">
                                            <span class="text-slate-700 dark:text-slate-300">{{ l.user_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ l.type }}</td>
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ l.days }}</td>
                                    <td class="px-5 py-3 text-slate-500 text-xs">{{ l.date_range }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                              :class="statusClass[l.status] || 'bg-slate-100 text-slate-500'">
                                            {{ l.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 text-xs hidden sm:table-cell">
                                        {{ l.reviewer || '—' }}
                                        <span v-if="l.reviewed_at" class="text-slate-400"> {{ l.reviewed_at }}</span>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400">No leave requests found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="leaves.last_page > 1" class="p-4 border-t flex gap-2 flex-wrap">
                    <template v-for="link in leaves.links" :key="link.label">
                        <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300" v-html="link.label"></span>
                        <button v-else @click="router.get(link.url)"
                                class="px-2 py-1 text-sm rounded"
                                :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"></button>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
