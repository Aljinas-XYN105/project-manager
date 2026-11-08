<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    roles: Array,
    permission_groups: Array,
    store_permission_url: String,
    create_url: String,
})

const openGroups = ref({})
const newPermName = ref('')
const permError = ref('')

function toggleGroup(group) {
    openGroups.value[group] = !openGroups.value[group]
}

function addPermission() {
    permError.value = ''
    router.post(props.store_permission_url, { name: newPermName.value }, {
        onSuccess: () => { newPermName.value = '' },
        onError: (e) => { permError.value = e.name || 'Invalid permission name' },
        preserveScroll: true,
    })
}

function deletePermission(url) {
    router.delete(url, { preserveScroll: true })
}

function deleteRole(role) {
    if (!confirm(`Delete the ${role.name} role?`)) return
    router.delete(role.destroy_url)
}
</script>

<template>
    <AppLayout title="Roles & Permissions">
        <!-- Admin nav placeholder -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Roles &amp; Permissions</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Roles bundle permissions. Assign roles to members on the Members page.</p>
            </div>
            <a :href="create_url" class="btn-primary"><i class="bi bi-plus-lg"></i> New role</a>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Roles -->
            <div class="xl:col-span-2 card overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Roles <span class="text-slate-400 font-normal">({{ roles.length }})</span></h2>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-for="role in roles" :key="role.id"
                         class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0', role.is_admin ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-violet-100 dark:bg-violet-900/30']">
                            <i :class="['bi', role.is_admin ? 'bi-shield-fill-check text-rose-600 dark:text-rose-400' : 'bi-key-fill text-violet-600 dark:text-violet-400']"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white">{{ role.headline }}</p>
                            <p class="text-xs text-slate-500">
                                <a :href="role.users_url" class="hover:text-violet-600">{{ role.users_count }} {{ role.users_count === 1 ? 'member' : 'members' }}</a>
                                · {{ role.is_admin ? 'All permissions' : role.permissions_count + ' ' + (role.permissions_count === 1 ? 'permission' : 'permissions') }}
                            </p>
                        </div>
                        <a :href="role.edit_url" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                        <button v-if="!role.is_admin" @click="deleteRole(role)" class="btn-ghost btn-sm text-rose-500" title="Delete role"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="card overflow-hidden self-start">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Permissions</h2>
                    <p class="text-xs text-slate-500 mt-0.5">New permissions are granted to admin automatically.</p>
                </div>
                <form @submit.prevent="addPermission" class="p-4 border-b border-slate-100 dark:border-slate-800 flex gap-2">
                    <input v-model="newPermName" type="text" required placeholder="area.action, e.g. invoices.view" class="form-input text-sm flex-1">
                    <button class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i></button>
                </form>
                <p v-if="permError" class="px-4 pt-2 form-error">{{ permError }}</p>
                <div class="max-h-[32rem] overflow-y-auto">
                    <div v-for="pg in permission_groups" :key="pg.group"
                         class="border-b border-slate-50 dark:border-slate-800 last:border-b-0">
                        <button type="button" @click="toggleGroup(pg.group)"
                                class="w-full flex items-center justify-between px-5 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ pg.headline }}</span>
                            <span class="flex items-center gap-2 text-xs text-slate-400">
                                {{ pg.perms.length }}
                                <i class="bi bi-chevron-down transition-transform" :class="openGroups[pg.group] ? 'rotate-180' : ''"></i>
                            </span>
                        </button>
                        <div v-show="openGroups[pg.group]" class="px-5 pb-3 space-y-1">
                            <div v-for="perm in pg.perms" :key="perm.id" class="flex items-center justify-between group">
                                <code class="text-xs text-slate-600 dark:text-slate-400">{{ perm.name }}</code>
                                <button type="button" @click="deletePermission(perm.destroy_url)"
                                        class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 transition-opacity"
                                        :title="`Delete permission ${perm.name}? It will be removed from every role.`">
                                    <i class="bi bi-x-lg text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
