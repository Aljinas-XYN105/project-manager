<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    from: String,
    to: String,
    members: Array,
    teams: Array,
    filters: Object,
})

const form = ref({
    from: props.filters?.from || props.from,
    to: props.filters?.to || props.to,
    team_id: props.filters?.team_id || '',
})

function applyFilters() {
    router.get(route('reports.teams'), { ...form.value })
}
function resetFilters() {
    router.get(route('reports.teams'))
}

function rateColor(rate) {
    if (rate >= 80) return '#22c55e'
    if (rate >= 50) return '#6366f1'
    return '#ef4444'
}
</script>

<template>
    <AppLayout title="Team Report">
        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('reports.index')" class="hover:text-violet-600 transition-colors">Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Team</span>
                    </div>
                    <h1 class="page-title">Team Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Member workload and productivity metrics</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="form-label">Team</label>
                        <select v-model="form.team_id" class="form-input">
                            <option value="">All Teams</option>
                            <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">From</label>
                        <DatePicker v-model="form.from" />
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <DatePicker v-model="form.to" />
                    </div>
                    <button type="submit" class="btn-primary">Apply</button>
                    <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
                </form>
            </div>

            <!-- Member Workload -->
            <div class="card">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Member Workload</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Member</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Assigned</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Completed</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Overdue</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Hours</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-if="members.length">
                                <tr v-for="m in members" :key="m.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <img :src="m.avatar_url" class="w-8 h-8 rounded-full" alt="">
                                            <div>
                                                <a :href="m.show_url" class="font-medium text-slate-900 dark:text-white hover:text-violet-600">{{ m.name }}</a>
                                                <p class="text-xs text-slate-500">{{ m.job_title }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300">{{ m.assigned_count }}</td>
                                    <td class="px-5 py-4 text-green-600 dark:text-green-400 font-medium">{{ m.completed_count }}</td>
                                    <td class="px-5 py-4 text-red-600 dark:text-red-400 font-medium">{{ m.overdue_count }}</td>
                                    <td class="px-5 py-4 font-mono text-slate-700 dark:text-slate-300">{{ m.hours_logged }}h</td>
                                    <td class="px-5 py-4 w-40">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full flex-1">
                                                <div class="h-1.5 rounded-full" :style="'width:' + m.completion_rate + '%; background:' + rateColor(m.completion_rate)"></div>
                                            </div>
                                            <span class="text-xs text-slate-500 w-8 text-right">{{ m.completion_rate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400">No members found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
