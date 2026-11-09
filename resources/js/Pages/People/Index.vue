<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    people: Array,
    departments: Array,
    filters: Object,
    org_chart_url: String,
})

const q = ref(props.filters?.q ?? '')
const dept = ref(props.filters?.department ?? '')

function search() {
    router.get(route('people.index'), { q: q.value || undefined, department: dept.value || undefined }, { preserveState: true })
}
</script>

<template>
    <AppLayout title="People">
        <div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">People</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ people.length }} colleagues{{ filters?.department ? ' in ' + filters.department : '' }}
                    </p>
                </div>
                <div class="tab-bar">
                    <span class="tab-item active"><i class="bi bi-grid-3x3-gap"></i> Directory</span>
                    <Link :href="org_chart_url" class="tab-item"><i class="bi bi-diagram-3"></i> Org chart</Link>
                </div>
            </div>

            <!-- Search + filter -->
            <form @submit.prevent="search" class="flex flex-wrap gap-2 mb-5">
                <div class="relative flex-1 min-w-[12rem] max-w-sm">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="search" v-model="q" class="form-input pl-9 text-sm" placeholder="Name or email…">
                </div>
                <select v-model="dept" @change="search" class="form-input text-sm w-auto">
                    <option value="">All departments</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                </select>
                <button type="submit" class="btn-secondary btn-sm">Search</button>
            </form>

            <!-- Empty state -->
            <div v-if="!people.length" class="card empty-state py-16">
                <i class="bi bi-people text-4xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 mt-3">No one matches.</p>
            </div>

            <!-- People grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                <a v-for="person in people" :key="person.id" :href="person.show_url"
                   class="card-hover p-5 flex flex-col">
                    <div class="flex items-start gap-3">
                        <div class="relative flex-shrink-0">
                            <img :src="person.avatar_url" class="w-12 h-12 rounded-full" :alt="person.name">
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full ring-2 ring-white dark:ring-slate-900"
                                  :class="person.dot_class"
                                  :title="person.is_on_leave ? 'On leave' : 'Available'"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900 dark:text-white truncate">{{ person.name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ person.job_title || 'No title' }}</p>
                            <span v-if="person.department"
                                  class="inline-block mt-1.5 text-[11px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                {{ person.department }}
                            </span>
                        </div>
                        <span v-if="person.is_on_leave"
                              class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium flex-shrink-0">
                            On leave
                        </span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1 text-xs text-slate-500">
                        <p class="truncate"><i class="bi bi-envelope mr-1.5"></i>{{ person.email }}</p>
                        <p v-if="person.phone" class="truncate"><i class="bi bi-telephone mr-1.5"></i>{{ person.phone }}</p>
                        <p v-if="person.manager" class="truncate">
                            <i class="bi bi-person-up mr-1.5"></i>Reports to {{ person.manager.name }}
                        </p>
                        <p v-if="person.direct_reports_count">
                            <i class="bi bi-diagram-2 mr-1.5"></i>{{ person.direct_reports_count }} direct {{ person.direct_reports_count === 1 ? 'report' : 'reports' }}
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </AppLayout>
</template>
