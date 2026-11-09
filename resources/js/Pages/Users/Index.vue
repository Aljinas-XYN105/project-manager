<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    users: Object,
    roles: Array,
    counts: Object,
    can_manage: Boolean,
    is_admin: Boolean,
    filters: Object,
})

const search = ref(props.filters?.search ?? '')
const role   = ref(props.filters?.role ?? '')
const active = ref(props.filters?.active ?? '')

function applyFilters() {
    router.get(route('users.index'), {
        search: search.value || undefined,
        role: role.value || undefined,
        active: active.value !== '' ? active.value : undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''; role.value = ''; active.value = ''
    router.get(route('users.index'), {})
}

const hasFilters = () => search.value || role.value || active.value !== ''

function toggleActive(user) {
    router.patch(user.toggle_url, {}, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Members">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Members</span>
                </div>
                <h1 class="page-title">Members</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ counts.total }} members · {{ counts.online }} online now
                </p>
            </div>
            <Link v-if="can_manage" :href="route('users.create')" class="btn-primary">
                <i class="bi bi-person-plus"></i> Add member
            </Link>
        </div>

        <!-- Filters -->
        <form @submit.prevent="applyFilters" class="card p-4 mb-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <label class="form-label">Search</label>
                <input v-model="search" type="search" class="form-input" placeholder="Name or email…">
            </div>
            <div>
                <label class="form-label">Role</label>
                <select v-model="role" class="form-input">
                    <option value="">All roles</option>
                    <option v-for="r in roles" :key="r.name" :value="r.name">{{ r.headline }} ({{ r.count }})</option>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select v-model="active" class="form-input">
                    <option value="">All ({{ counts.total }})</option>
                    <option value="1">Active ({{ counts.active }})</option>
                    <option value="0">Inactive ({{ counts.inactive }})</option>
                </select>
            </div>
            <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            <button v-if="hasFilters()" type="button" @click="clearFilters" class="btn-ghost">Clear</button>
        </form>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 font-medium">Member</th>
                            <th class="px-5 py-3 font-medium">Roles</th>
                            <th class="px-5 py-3 font-medium">Availability</th>
                            <th class="px-5 py-3 font-medium">Account</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">No members match these filters.</td>
                        </tr>
                        <tr v-for="user in users.data" :key="user.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/40"
                            :class="user.is_active ? '' : 'opacity-60'">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-shrink-0">
                                        <img :src="user.avatar_url" class="w-9 h-9 rounded-full" alt="">
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900"
                                              :class="user.availability_dot"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <Link :href="user.show_url" class="font-medium text-slate-900 dark:text-white hover:text-violet-600">{{ user.name }}</Link>
                                        <p class="text-xs text-slate-500 truncate">{{ user.email }}<template v-if="user.job_title"> · {{ user.job_title }}</template></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    <span v-if="user.roles.length === 0" class="text-xs text-slate-400">No role</span>
                                    <span v-for="r in user.roles" :key="r.name"
                                          class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                          :class="r.name === 'admin' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'">
                                        {{ r.headline }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ user.availability_label }}</p>
                                <p class="text-[11px] text-slate-400">{{ user.last_seen }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                                      :class="user.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <Link :href="user.show_url" class="btn-ghost btn-sm" title="Profile"><i class="bi bi-eye"></i></Link>
                                <Link v-if="is_admin" :href="user.admin_url" class="btn-ghost btn-sm" title="Performance"><i class="bi bi-graph-up"></i></Link>
                                <template v-if="can_manage">
                                    <Link :href="user.edit_url" class="btn-ghost btn-sm" title="Edit"><i class="bi bi-pencil"></i></Link>
                                    <button v-if="!user.is_self" @click="toggleActive(user)"
                                            class="btn-ghost btn-sm" :class="user.is_active ? 'text-amber-600' : 'text-emerald-600'"
                                            :title="user.is_active ? 'Deactivate' : 'Activate'">
                                        <i :class="`bi ${user.is_active ? 'bi-pause-circle' : 'bi-play-circle'}`"></i>
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="users.last_page > 1" class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1">
                <template v-for="link in users.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs"
                          :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100'" v-html="link.label" />
                    <span v-else class="px-3 py-1 text-xs text-slate-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
