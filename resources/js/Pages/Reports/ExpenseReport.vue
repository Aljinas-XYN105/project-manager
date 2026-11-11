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
    expenses: Object,
    users: Array,
    categories: Array,
    filters: Object,
    export_url: String,
})

const form = ref({
    from: props.filters?.from || props.from,
    to: props.filters?.to || props.to,
    user_id: props.filters?.user_id || '',
    status: props.filters?.status || '',
    category: props.filters?.category || '',
})

function applyFilters() {
    router.get(route('reports.expenses'), { ...form.value })
}
function resetFilters() {
    router.get(route('reports.expenses'))
}
function exportPdf() {
    window.location.href = route('reports.expenses') + '?' + new URLSearchParams({ ...form.value, export: 'pdf' })
}

const statusClass = {
    pending:  'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    approved: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    rejected: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
}
</script>

<template>
    <AppLayout title="Expense Report">
        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('reports.index')" class="hover:text-violet-600 transition-colors">Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Expenses</span>
                    </div>
                    <h1 class="page-title">Expense Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Review submitted expenses by category and employee</p>
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
                        <label class="form-label">Category</label>
                        <SearchSelect v-model="form.category"
                            :options="categories.map(c => ({value: c, label: c}))"
                            :clearable="true" clearLabel="All Categories" placeholder="All Categories" />
                    </div>
                    <button type="submit" class="btn-primary">Apply</button>
                    <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
                </form>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat-card"><div class="text-2xl font-bold text-slate-900 dark:text-white">{{ summary.total_count }}</div><p class="text-xs text-slate-500">Total</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-emerald-600">{{ summary.approved }}</div><p class="text-xs text-slate-500">Approved</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-amber-600">{{ summary.pending }}</div><p class="text-xs text-slate-500">Pending</p></div>
                <div class="stat-card"><div class="text-2xl font-bold text-violet-600">{{ summary.total_amount?.toLocaleString?.() ?? summary.total_amount }}</div><p class="text-xs text-slate-500">Total approved ($)</p></div>
            </div>

            <!-- By Category -->
            <div v-if="summary.by_category?.length" class="card p-5">
                <h2 class="font-semibold text-slate-900 dark:text-white mb-4">By Category</h2>
                <div class="flex flex-wrap gap-3">
                    <div v-for="cat in summary.by_category" :key="cat.category"
                         class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ cat.category }}</span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white">${{ cat.total }}</span>
                    </div>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                                <th class="px-5 py-3 font-medium text-slate-500">Employee</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Category</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Amount</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Date</th>
                                <th class="px-5 py-3 font-medium text-slate-500">Status</th>
                                <th class="px-5 py-3 font-medium text-slate-500 hidden sm:table-cell">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-if="expenses.data?.length">
                                <tr v-for="e in expenses.data" :key="e.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <img :src="e.avatar_url" class="w-6 h-6 rounded-full">
                                            <span class="text-slate-700 dark:text-slate-300">{{ e.user_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300 capitalize">{{ e.category }}</td>
                                    <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white">${{ e.amount }}</td>
                                    <td class="px-5 py-3 text-slate-500 text-xs">{{ e.expense_date }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                              :class="statusClass[e.status] || 'bg-slate-100 text-slate-500'">
                                            {{ e.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 text-xs truncate max-w-xs hidden sm:table-cell">{{ e.description }}</td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400">No expenses found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="expenses.last_page > 1" class="p-4 border-t flex gap-2 flex-wrap">
                    <template v-for="link in expenses.links" :key="link.label">
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
