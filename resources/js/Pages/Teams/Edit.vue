<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    team: Object,
})

const form = ref({ name: props.team.name, description: props.team.description || '' })
const errors = ref({})
const processing = ref(false)

function submit() {
    processing.value = true
    router.put(props.team.update_url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}

function deleteTeam() {
    if (!confirm(`Delete team '${props.team.name}'? This cannot be undone.`)) return
    router.delete(props.team.destroy_url)
}
</script>

<template>
    <AppLayout :title="`Edit Team — ${team.name}`">
        <div class="max-w-xl">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('teams.index')" class="hover:text-violet-600 transition-colors">Teams</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <a :href="team.show_url" class="hover:text-violet-600 transition-colors">{{ team.name }}</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>Edit</span>
                    </div>
                    <h1 class="page-title">Edit Team</h1>
                </div>
                <a :href="team.show_url" class="btn-secondary">Cancel</a>
            </div>

            <div class="card p-6">
                <form @submit.prevent="submit">
                    <div class="space-y-4">
                        <!-- Validation error banner -->
                        <div v-if="Object.keys(errors).length"
                             class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 px-4 py-3 flex items-start gap-2.5">
                            <i class="bi bi-exclamation-circle-fill text-rose-500 flex-shrink-0 mt-0.5 text-sm"></i>
                            <ul class="text-sm text-rose-700 dark:text-rose-300 space-y-0.5 list-none m-0 p-0">
                                <li v-for="(msg, field) in errors" :key="field">{{ msg }}</li>
                            </ul>
                        </div>

                        <div>
                            <label class="form-label">Team Name <span class="text-rose-500">*</span></label>
                            <input v-model="form.name" type="text" required autofocus class="form-input"
                                   :class="errors.name ? 'border-rose-400 dark:border-rose-600' : ''">
                            <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name }}</p>
                        </div>
                        <div>
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description" rows="3" class="form-input resize-none"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a :href="team.show_url" class="btn-secondary">Cancel</a>
                        <button type="submit" :disabled="processing" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Danger zone -->
            <div v-if="team.can_delete" class="mt-6 card border-red-200 dark:border-red-900 p-5">
                <h3 class="font-semibold text-red-600 dark:text-red-400 mb-2">Danger Zone</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Deleting this team is permanent and cannot be undone. All projects associated with this team will be orphaned.</p>
                <button @click="deleteTeam" class="btn-danger btn-sm">Delete Team</button>
            </div>
        </div>
    </AppLayout>
</template>
