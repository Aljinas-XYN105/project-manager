<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    role: Object,
    permission_groups: Array,
    selected: Array,
    is_admin_role: Boolean,
    store_url: String,
    update_url: String,
    back_url: String,
})

const form = ref({
    name: props.role?.name || '',
    permissions: [...(props.selected || [])],
})
const errors = ref({})
const processing = ref(false)
const previewHeadline = computed(() => {
    if (!form.value.name) return 'team_lead'
    return form.value.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})

function submit() {
    processing.value = true
    const url = props.role ? props.update_url : props.store_url
    const method = props.role ? 'put' : 'post'
    router[method](url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}

function togglePermission(name) {
    const idx = form.value.permissions.indexOf(name)
    if (idx === -1) form.value.permissions.push(name)
    else form.value.permissions.splice(idx, 1)
}

function toggleGroup(perms) {
    const names = perms.map(p => p.name)
    const allSelected = names.every(n => form.value.permissions.includes(n))
    if (allSelected) {
        form.value.permissions = form.value.permissions.filter(p => !names.includes(p))
    } else {
        form.value.permissions = [...new Set([...form.value.permissions, ...names])]
    }
}

function selectAll() {
    form.value.permissions = props.permission_groups.flatMap(pg => pg.perms.map(p => p.name))
}

function clearAll() {
    form.value.permissions = []
}
</script>

<template>
    <AppLayout :title="role ? 'Edit role' : 'Create role'">
        <div class="max-w-3xl mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="back_url" class="hover:text-violet-600">Roles &amp; Permissions</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>{{ role ? role.headline : 'New' }}</span>
                    </div>
                    <h1 class="page-title">{{ role ? 'Edit role' : 'Create role' }}</h1>
                </div>
                <a :href="back_url" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="card p-6">
                    <label class="form-label">Role name *</label>
                    <input v-model="form.name" type="text" required :readonly="is_admin_role"
                           pattern="[a-z0-9_]+"
                           :class="['form-input font-mono', is_admin_role ? 'bg-slate-50 dark:bg-slate-800 cursor-not-allowed' : '']"
                           placeholder="e.g. team_lead">
                    <p class="form-hint">Lowercase letters, numbers and underscores. Shown to users as "{{ previewHeadline }}".</p>
                    <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                </div>

                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">Permissions</h2>
                            <p class="text-xs text-slate-500">{{ form.permissions.length }} selected</p>
                        </div>
                        <div v-if="!is_admin_role" class="flex gap-2">
                            <button type="button" class="btn-ghost btn-sm" @click="selectAll">Select all</button>
                            <button type="button" class="btn-ghost btn-sm" @click="clearAll">Clear</button>
                        </div>
                    </div>

                    <div v-if="is_admin_role" class="px-6 py-4 bg-rose-50 dark:bg-rose-900/10 text-sm text-rose-700 dark:text-rose-400 flex items-center gap-2">
                        <i class="bi bi-shield-lock-fill"></i> The admin role always has every permission so it can never be locked out.
                    </div>
                    <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div v-for="pg in permission_groups" :key="pg.group" class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2.5">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ pg.headline }}</p>
                                <button type="button" class="text-xs text-violet-600 hover:underline" @click="toggleGroup(pg.perms)">Toggle group</button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <label v-for="perm in pg.perms" :key="perm.name"
                                       :class="['flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:border-violet-400',
                                                form.permissions.includes(perm.name) ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : '']">
                                    <input type="checkbox" :checked="form.permissions.includes(perm.name)" @change="togglePermission(perm.name)"
                                           class="rounded text-violet-600 border-slate-300 focus:ring-violet-500">
                                    <span class="text-xs text-slate-700 dark:text-slate-300">{{ perm.action_headline }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a :href="back_url" class="btn-ghost">Cancel</a>
                    <button type="submit" :disabled="processing" class="btn-primary">
                        <i class="bi bi-check-lg"></i> {{ role ? 'Save role' : 'Create role' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
