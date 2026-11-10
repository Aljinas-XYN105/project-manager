<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    project: Object,
    sprints: Array,
    can_update: Boolean,
})

const csrf = () => document.querySelector('meta[name=csrf-token]')?.content

async function patchSprint(url, confirm_msg) {
    if (!confirm(confirm_msg)) return
    const fd = new FormData()
    fd.append('_method', 'PATCH')
    fd.append('_token', csrf())
    await fetch(url, { method: 'POST', body: fd })
    router.reload()
}

async function deleteSprint(url) {
    if (!confirm('Delete sprint? Tasks return to backlog.')) return
    const fd = new FormData()
    fd.append('_method', 'DELETE')
    fd.append('_token', csrf())
    await fetch(url, { method: 'POST', body: fd })
    router.reload()
}
</script>

<template>
    <AppLayout :title="project.name + ' — Sprints'">
        <div>
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Sprints</span>
                    </div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-speedometer2 text-violet-600 dark:text-violet-400 text-sm"></i>
                        </span>
                        Sprints
                    </h1>
                </div>
                <a v-if="can_update" :href="project.create_url" class="btn-primary">
                    <i class="bi bi-plus-lg"></i> New Sprint
                </a>
            </div>

            <!-- Empty state -->
            <div v-if="!sprints.length" class="empty-state">
                <div class="empty-icon"><i class="bi bi-speedometer2"></i></div>
                <h3>No sprints yet</h3>
                <p>Create a sprint to plan your team's work in time-boxed iterations.<br>
                   <span class="text-xs text-slate-400 mt-1 block">A sprint is a fixed period (e.g. 2 weeks) during which tasks from the backlog are worked on.</span>
                </p>
                <a v-if="can_update" :href="project.create_url" class="btn-primary btn-sm mt-2">
                    <i class="bi bi-plus-lg"></i> Create First Sprint
                </a>
            </div>

            <!-- Sprint list -->
            <div v-else class="space-y-3">
                <div v-for="sprint in sprints" :key="sprint.id"
                     class="card p-5 transition-all hover:shadow-card-md"
                     :class="sprint.is_active ? 'border-l-4 border-l-emerald-400 dark:border-l-emerald-600' : ''">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap mb-1">
                                <a :href="sprint.show_url"
                                   class="font-semibold text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 text-base transition-colors">
                                    {{ sprint.name }}
                                </a>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="sprint.status_class">
                                    {{ sprint.status_label }}
                                </span>
                                <span v-if="sprint.is_overdue"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                                    <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Overdue
                                </span>
                            </div>
                            <p v-if="sprint.goal" class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed">{{ sprint.goal }}</p>
                            <div class="flex items-center gap-4 text-xs text-slate-400 flex-wrap">
                                <span class="flex items-center gap-1"><i class="bi bi-calendar3"></i> {{ sprint.date_range }}</span>
                                <span class="flex items-center gap-1"><i class="bi bi-list-task"></i> {{ sprint.tasks_count }} tasks</span>
                                <span v-if="sprint.status !== 'planning'" class="flex items-center gap-1">
                                    <i class="bi bi-pie-chart"></i> {{ sprint.progress }}% complete
                                </span>
                            </div>
                            <div v-if="sprint.is_active" class="mt-3">
                                <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-700"
                                         :style="'width:' + sprint.progress + '%; background: linear-gradient(90deg, #7c3aed, #a855f7)'"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a :href="sprint.show_url" class="btn-ghost btn-sm"><i class="bi bi-eye"></i> View</a>
                            <template v-if="can_update">
                                <button v-if="sprint.is_planning" @click="patchSprint(sprint.start_url, 'Start sprint ' + sprint.name + '?')"
                                        class="btn-primary btn-sm">
                                    <i class="bi bi-play-fill"></i> Start
                                </button>
                                <button v-else-if="sprint.is_active"
                                        @click="patchSprint(sprint.complete_url, 'Complete sprint? Unfinished tasks return to backlog.')"
                                        class="btn-sm px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                                    <i class="bi bi-check2-all"></i> Complete
                                </button>
                                <button @click="deleteSprint(sprint.destroy_url)"
                                        class="btn-ghost btn-sm text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
