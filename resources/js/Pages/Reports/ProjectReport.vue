<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    projects: Object,
    teams: Array,
    filters: Object,
})

const form = ref({
    status: props.filters?.status || '',
    team_id: props.filters?.team_id || '',
})

function applyFilters() {
    router.get(route('reports.projects'), { ...form.value })
}
function resetFilters() {
    router.get(route('reports.projects'))
}
</script>

<template>
    <AppLayout title="Project Report">
        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('reports.index')" class="hover:text-violet-600 transition-colors">Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Projects</span>
                    </div>
                    <h1 class="page-title">Project Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Progress and completion metrics for all projects</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="form-label">Status</label>
                        <SearchSelect v-model="form.status"
                            :options="[{value:'planning',label:'Planning'},{value:'active',label:'Active'},{value:'on_hold',label:'On Hold'},{value:'completed',label:'Completed'},{value:'archived',label:'Archived'}]"
                            :clearable="true" clearLabel="All Statuses" placeholder="All Statuses" />
                    </div>
                    <div>
                        <label class="form-label">Team</label>
                        <SearchSelect v-model="form.team_id"
                            :options="teams.map(t => ({value: t.id, label: t.name}))"
                            :clearable="true" clearLabel="All Teams" placeholder="All Teams" />
                    </div>
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <button type="button" @click="resetFilters" class="btn-secondary">Reset</button>
                </form>
            </div>

            <!-- Projects Table -->
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Project</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Status</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Progress</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Tasks</th>
                                <th class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Due Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-if="projects.data?.length">
                                <tr v-for="p in projects.data" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center"
                                                 :style="'background:' + p.color + '20'">
                                                <span class="text-xs font-bold" :style="'color:' + p.color">{{ p.initial }}</span>
                                            </div>
                                            <div>
                                                <a :href="p.show_url" class="font-medium text-slate-900 dark:text-white hover:text-violet-600">{{ p.name }}</a>
                                                <p class="text-xs text-slate-500">{{ p.team }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="p.status_class">
                                            {{ p.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 w-40">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full flex-1">
                                                <div class="h-1.5 bg-violet-500 rounded-full" :style="'width:' + p.progress + '%'"></div>
                                            </div>
                                            <span class="text-xs text-slate-500 w-8 text-right">{{ p.progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300">{{ p.tasks_count }}</td>
                                    <td class="px-5 py-4 text-slate-500 text-xs">{{ p.due_date || '—' }}</td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-400">No projects found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="projects.last_page > 1" class="p-4 border-t flex gap-2 flex-wrap">
                    <template v-for="link in projects.links" :key="link.label">
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
