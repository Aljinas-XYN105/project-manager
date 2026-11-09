<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    store_url: String,
    back_url: String,
})

const form = ref({ name: '', description: '' })
const errors = ref({})
const processing = ref(false)

function submit() {
    processing.value = true
    router.post(props.store_url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}
</script>

<template>
    <AppLayout title="New Team">
        <div class="max-w-lg mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('teams.index')" class="hover:text-violet-600 transition-colors">Teams</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>New</span>
                    </div>
                    <h1 class="page-title">New Team</h1>
                </div>
                <a :href="back_url" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="form-label">Team Name <span class="text-rose-500">*</span></label>
                        <input v-model="form.name" type="text" required class="form-input" placeholder="e.g. Engineering">
                        <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea v-model="form.description" rows="3" class="form-input resize-none" placeholder="What does this team work on?"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a :href="back_url" class="btn-secondary">Cancel</a>
                        <button type="submit" :disabled="processing" class="btn-primary">
                            <i class="bi bi-check-lg"></i> Create Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
