<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    project: Object,
})

const today = new Date().toISOString().split('T')[0]
const twoWeeks = new Date(Date.now() + 13 * 86400000).toISOString().split('T')[0]

const form = ref({
    name: 'Sprint ' + (props.project.sprint_count + 1),
    goal: '',
    start_date: today,
    end_date: twoWeeks,
})
const errors = ref({})
const submitting = ref(false)

function submit() {
    submitting.value = true
    errors.value = {}
    router.post(props.project.store_url, form.value, {
        onError: (e) => { errors.value = e; submitting.value = false },
        onFinish: () => { submitting.value = false },
    })
}
</script>

<template>
    <AppLayout :title="'New Sprint — ' + project.name">
        <div class="max-w-2xl mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <a :href="project.sprints_url" class="hover:text-violet-600 transition-colors">Sprints</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>New</span>
                    </div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-speedometer2 text-violet-600 dark:text-violet-400"></i>
                        </span>
                        Create Sprint
                    </h1>
                </div>
                <a :href="project.sprints_url" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <div class="card p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="form-label">Sprint Name <span class="text-rose-500">*</span></label>
                        <input type="text" v-model="form.name" required class="form-input text-base font-medium" placeholder="Sprint 1">
                        <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                    </div>

                    <div>
                        <label class="form-label">Sprint Goal</label>
                        <textarea v-model="form.goal" rows="3" class="form-input resize-none"
                                  placeholder="What does the team aim to achieve in this sprint?"></textarea>
                        <p class="text-xs text-slate-400 mt-1">Helps the team stay focused on the most important outcome.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Start Date <span class="text-rose-500">*</span></label>
                            <DatePicker v-model="form.start_date" />
                            <p v-if="errors.start_date" class="form-error">{{ errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="form-label">End Date <span class="text-rose-500">*</span></label>
                            <DatePicker v-model="form.end_date" />
                            <p v-if="errors.end_date" class="form-error">{{ errors.end_date }}</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <a :href="project.sprints_url" class="btn-ghost">Cancel</a>
                        <button type="submit" :disabled="submitting" class="btn-primary">
                            <i class="bi bi-check-lg"></i> Create Sprint
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tips -->
            <div class="mt-5 card p-5 bg-violet-50 dark:bg-violet-900/10 border-violet-200 dark:border-violet-800">
                <h3 class="font-semibold text-violet-800 dark:text-violet-300 text-sm mb-3 flex items-center gap-2">
                    <i class="bi bi-lightbulb-fill text-violet-500"></i> Sprint Tips
                </h3>
                <ul class="space-y-1.5 text-sm text-violet-700 dark:text-violet-400">
                    <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Keep sprints 1–2 weeks for most teams</li>
                    <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Write a clear, measurable sprint goal before adding tasks</li>
                    <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Add tasks from the backlog after creating the sprint</li>
                    <li class="flex items-start gap-2"><i class="bi bi-check2 mt-0.5 flex-shrink-0"></i>Only start the sprint when the team is aligned on the plan</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
