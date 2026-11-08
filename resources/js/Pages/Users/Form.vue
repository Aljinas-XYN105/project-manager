<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user: Object,
    roles: Array,
    teams: Array,
    managers: Array,
    departments: Array,
    employment_types: Object,
    store_url: String,
    update_url: String,
    is_edit: Boolean,
})

const form = ref({ ...props.user })
const errors = ref({})
const processing = ref(false)

const title = computed(() => props.is_edit ? `Edit member` : 'Add member')

function submit() {
    processing.value = true
    const url = props.is_edit ? props.update_url : props.store_url
    const method = props.is_edit ? 'put' : 'post'
    router[method](url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}

function resetPassword() {
    const pwd = prompt(`New password for ${props.user.name} (leave empty to generate):`)
    if (pwd === null) return
    router.post(props.user.reset_password_url, { password: pwd }, { preserveScroll: true })
}

function removeUser() {
    if (!confirm(`Remove ${props.user.name}? They will no longer be able to sign in. Their tasks and history are kept.`)) return
    router.delete(props.user.destroy_url)
}

function toggleRole(name) {
    const idx = form.value.current_roles.indexOf(name)
    if (idx === -1) {
        form.value.current_roles = [...form.value.current_roles, name]
    } else {
        // Cannot remove admin from self
        if (props.user.is_self && name === 'admin') return
        form.value.current_roles = form.value.current_roles.filter(r => r !== name)
    }
}
</script>

<template>
    <AppLayout :title="title">
        <div class="max-w-2xl mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('users.index')" class="hover:text-violet-600">Members</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>{{ is_edit ? user.name : 'New' }}</span>
                    </div>
                    <h1 class="page-title">{{ title }}</h1>
                </div>
                <a :href="route('users.index')" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Account -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Account</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Full name *</label>
                            <input v-model="form.name" type="text" required class="form-input" placeholder="Jane Smith" autofocus>
                            <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                        </div>
                        <div>
                            <label class="form-label">Email *</label>
                            <input v-model="form.email" type="email" required class="form-input" placeholder="jane@company.com">
                            <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
                        </div>
                        <div>
                            <label class="form-label">Job title</label>
                            <input v-model="form.job_title" type="text" class="form-input" placeholder="e.g. Backend Developer">
                        </div>
                        <div>
                            <label class="form-label">Primary team</label>
                            <select v-model="form.team_id" class="form-input">
                                <option value="">No team</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Timezone</label>
                            <select v-model="form.timezone" class="form-input">
                                <option v-for="tz in Intl.supportedValuesOf('timeZone')" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                            <p class="form-hint">Used to show this member's attendance times.</p>
                        </div>
                        <div v-if="!is_edit">
                            <label class="form-label">Password</label>
                            <input v-model="form.password" type="password" class="form-input" autocomplete="new-password" placeholder="Leave empty to generate one">
                            <p v-if="errors.password" class="form-error">{{ errors.password }}</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-3 pt-1" :class="user.is_self ? 'opacity-60' : 'cursor-pointer'">
                        <input type="checkbox" v-model="form.is_active" :disabled="user.is_self"
                               class="rounded text-violet-600 border-slate-300 focus:ring-violet-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Account is active <span class="text-slate-400">— inactive members can't sign in</span></span>
                    </label>
                </div>

                <!-- Employment -->
                <div class="card p-6 space-y-4">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Employment</h2>
                        <p class="text-xs text-slate-500">Used for the people directory, org chart and leave approvals.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Employee ID</label>
                            <input v-model="form.employee_code" type="text" class="form-input" placeholder="e.g. EMP-0042">
                            <p v-if="errors.employee_code" class="form-error">{{ errors.employee_code }}</p>
                        </div>
                        <div>
                            <label class="form-label">Department</label>
                            <input v-model="form.department" type="text" list="depts-list" class="form-input" placeholder="e.g. Engineering">
                            <datalist id="depts-list">
                                <option v-for="d in departments" :key="d" :value="d"></option>
                            </datalist>
                        </div>
                        <div>
                            <label class="form-label">Reports to</label>
                            <select v-model="form.manager_id" class="form-input">
                                <option :value="null">— No manager —</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}{{ m.job_title ? ' · ' + m.job_title : '' }}</option>
                            </select>
                            <p class="form-hint">The manager can approve this person's leave.</p>
                            <p v-if="errors.manager_id" class="form-error">{{ errors.manager_id }}</p>
                        </div>
                        <div>
                            <label class="form-label">Employment type</label>
                            <select v-model="form.employment_type" class="form-input">
                                <option v-for="(label, key) in employment_types" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Joining date</label>
                            <input v-model="form.joined_on" type="date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Date of birth</label>
                            <input v-model="form.date_of_birth" type="date" class="form-input">
                            <p v-if="errors.date_of_birth" class="form-error">{{ errors.date_of_birth }}</p>
                        </div>
                        <div>
                            <label class="form-label">Phone</label>
                            <input v-model="form.phone" type="tel" class="form-input" placeholder="+1 555 0100">
                        </div>
                        <div></div>
                        <div>
                            <label class="form-label">Emergency contact name</label>
                            <input v-model="form.emergency_contact_name" type="text" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Emergency contact phone</label>
                            <input v-model="form.emergency_contact_phone" type="tel" class="form-input">
                        </div>
                    </div>
                </div>

                <!-- Roles -->
                <div class="card p-6 space-y-4">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Roles *</h2>
                    <p class="text-xs text-slate-500">A member gets every permission from each role they have. <a :href="route('admin.roles.index')" class="text-violet-600 hover:underline">Manage roles</a></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label v-for="role in roles" :key="role.name"
                               :class="[
                                   'flex items-center gap-3 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 transition-colors',
                                   form.current_roles.includes(role.name) ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'hover:border-violet-400',
                                   user.is_self && role.name === 'admin' ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                               ]">
                            <input type="checkbox"
                                   :checked="form.current_roles.includes(role.name)"
                                   :disabled="user.is_self && role.name === 'admin'"
                                   @change="toggleRole(role.name)"
                                   class="rounded text-violet-600 border-slate-300 focus:ring-violet-500">
                            <span class="flex-1 text-sm text-slate-800 dark:text-slate-200">{{ role.headline }}</span>
                            <span class="text-[11px] text-slate-400">{{ role.users_count }}</span>
                            <i v-if="user.is_self && role.name === 'admin'" class="bi bi-lock-fill text-xs text-slate-400" title="You can't remove your own admin role"></i>
                        </label>
                    </div>
                    <p v-if="errors.roles" class="form-error mt-2">{{ errors.roles }}</p>
                </div>

                <div class="flex justify-end gap-3">
                    <a :href="route('users.index')" class="btn-ghost">Cancel</a>
                    <button type="submit" :disabled="processing" class="btn-primary">
                        <i class="bi bi-check-lg"></i> {{ is_edit ? 'Save changes' : 'Create member' }}
                    </button>
                </div>
            </form>

            <!-- Danger zone (edit only, not self) -->
            <div v-if="is_edit && !user.is_self" class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-8">
                <div class="card p-5 space-y-3">
                    <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2"><i class="bi bi-key text-amber-500"></i> Reset password</h3>
                    <input type="password" id="reset-pwd" class="form-input text-sm" autocomplete="new-password" placeholder="New password (empty = generate)">
                    <button type="button" @click="resetPassword" class="btn-secondary btn-sm w-full justify-center">Reset password</button>
                </div>
                <div class="card p-5 space-y-3 border-rose-200 dark:border-rose-900">
                    <h3 class="font-semibold text-rose-600 dark:text-rose-400 flex items-center gap-2"><i class="bi bi-person-x"></i> Remove member</h3>
                    <p class="text-xs text-slate-500">Signs them out and hides them from the team. Tasks, reports and logs are kept.</p>
                    <button type="button" @click="removeUser" class="btn-danger btn-sm w-full justify-center">Remove {{ user.name }}</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
