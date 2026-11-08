<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    term: String,
    projects: Array,
    tasks: Array,
    people: Array,
})

const q = ref(props.term ?? '')

function search() {
    if (!q.value) return
    router.get(route('search'), { q: q.value }, { preserveState: true, replace: true })
}

const hasResults = props.projects?.length || props.tasks?.length || props.people?.length
const noResults = (props.term?.length ?? 0) >= 2 && !hasResults
</script>

<template>
    <AppLayout :title="term ? `Search: ${term}` : 'Search'">
        <div class="max-w-4xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Search</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Projects, tasks (including ticket keys like MOBILE-12) and people.</p>
                </div>
            </div>

            <form @submit.prevent="search" class="card p-3 mb-6 flex gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input v-model="q" type="search" autofocus minlength="2" class="form-input pl-10" placeholder="Search…">
                </div>
                <button class="btn-primary">Search</button>
            </form>

            <div v-if="!term || term.length < 2" class="py-16 text-center">
                <i class="bi bi-search text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                <p class="text-sm text-slate-500">Type at least 2 characters.</p>
            </div>

            <div v-else-if="noResults" class="py-16 text-center">
                <i class="bi bi-emoji-neutral text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                <p class="text-sm text-slate-500">Nothing matches "{{ term }}".</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Tasks -->
                <div v-if="tasks?.length" class="card overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 font-semibold text-slate-900 dark:text-white">
                        Tasks <span class="text-slate-400 font-normal">({{ tasks.length }})</span>
                    </div>
                    <Link v-for="task in tasks" :key="task.id" :href="task.show_url"
                          class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <i :class="`bi ${task.type_icon} ${task.type_color}`"></i>
                        <span class="font-mono text-xs text-slate-400 w-20 flex-shrink-0">{{ task.ticket_key }}</span>
                        <span class="flex-1 min-w-0 truncate text-sm"
                              :class="['done','cancelled'].includes(task.status) ? 'line-through text-slate-400' : 'text-slate-900 dark:text-white'">
                            {{ task.title }}
                        </span>
                        <span class="text-xs text-slate-400 hidden sm:inline">{{ task.project?.name ?? '—' }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                            {{ task.status.replace(/_/g, ' ') }}
                        </span>
                    </Link>
                </div>

                <!-- Projects -->
                <div v-if="projects?.length" class="card overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 font-semibold text-slate-900 dark:text-white">
                        Projects <span class="text-slate-400 font-normal">({{ projects.length }})</span>
                    </div>
                    <Link v-for="project in projects" :key="project.id" :href="project.show_url"
                          class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" :style="`background-color: ${project.color}`"></span>
                        <span class="flex-1 text-sm font-medium text-slate-900 dark:text-white">{{ project.name }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                            {{ project.status.replace(/_/g, ' ') }}
                        </span>
                    </Link>
                </div>

                <!-- People -->
                <div v-if="people?.length" class="card overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800 font-semibold text-slate-900 dark:text-white">
                        People <span class="text-slate-400 font-normal">({{ people.length }})</span>
                    </div>
                    <Link v-for="person in people" :key="person.id" :href="person.show_url"
                          class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <img :src="person.avatar_url" class="w-8 h-8 rounded-full" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ person.name }}</p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ [person.job_title, person.department, person.email].filter(Boolean).join(' · ') }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
