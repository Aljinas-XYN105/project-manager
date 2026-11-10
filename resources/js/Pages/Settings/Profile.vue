<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    user: Object,
    success: String,
    update_url: String,
    my_tasks_url: String,
    reports_url: String,
    time_url: String,
    leave_url: String,
})

// Profile form
const profileForm = ref({
    name: props.user.name,
    email: props.user.email,
    job_title: props.user.job_title || '',
    timezone: props.user.timezone || 'UTC',
    avatar: null,
})
const avatarPreview = ref(props.user.avatar_url)
const avatarError = ref('')
const isDragging = ref(false)
const avatarInput = ref(null)
const profileErrors = ref({})
const timezones = Intl.supportedValuesOf('timeZone')

function pickFile(file) {
    avatarError.value = ''
    if (!file) return
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
    if (!allowed.includes(file.type)) { avatarError.value = 'Only JPG, PNG, GIF or WEBP images are allowed.'; return }
    if (file.size > 2 * 1024 * 1024) { avatarError.value = 'Image must be under 2 MB.'; return }
    avatarPreview.value = URL.createObjectURL(file)
    profileForm.value.avatar = file
}

function saveProfile() {
    const data = new FormData()
    data.append('_method', 'PATCH')
    data.append('name', profileForm.value.name)
    data.append('email', profileForm.value.email)
    data.append('job_title', profileForm.value.job_title)
    data.append('timezone', profileForm.value.timezone)
    if (profileForm.value.avatar) data.append('avatar', profileForm.value.avatar)

    fetch(props.update_url, {
        method: 'POST',
        body: data,
        headers: { 'X-Inertia': 'true', 'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '') },
    }).then(r => r.json()).then(() => {
        router.reload({ only: ['user', 'success'] })
    }).catch(() => {
        profileErrors.value = { name: 'Something went wrong.' }
    })
}

function saveProfileInertia() {
    profileErrors.value = {}
    const data = { name: profileForm.value.name, email: profileForm.value.email, job_title: profileForm.value.job_title, timezone: profileForm.value.timezone }
    if (profileForm.value.avatar) {
        const fd = new FormData()
        fd.append('_method', 'PATCH')
        Object.entries(data).forEach(([k, v]) => fd.append(k, v))
        fd.append('avatar', profileForm.value.avatar)
        router.post(props.update_url, fd, {
            onError: e => { profileErrors.value = e },
        })
    } else {
        router.patch(props.update_url, data, {
            onError: e => { profileErrors.value = e },
        })
    }
}

// Password form
const passwordForm = ref({ section: 'password', current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref({})

function savePassword() {
    passwordErrors.value = {}
    router.patch(props.update_url, passwordForm.value, {
        onSuccess: () => { passwordForm.value = { section: 'password', current_password: '', password: '', password_confirmation: '' } },
        onError: e => { passwordErrors.value = e },
    })
}

// Dark mode
const darkMode = ref(document.documentElement.classList.contains('dark'))
function toggleDark() {
    darkMode.value = !darkMode.value
    document.documentElement.classList.toggle('dark', darkMode.value)
    localStorage.setItem('theme', darkMode.value ? 'dark' : 'light')
}
</script>

<template>
    <AppLayout title="Profile Settings">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Profile Settings</span>
                </div>
                <h1 class="page-title flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                        <i class="bi bi-person-fill text-violet-600 dark:text-violet-400 text-sm"></i>
                    </span>
                    Profile Settings
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage your account information and preferences</p>
            </div>
        </div>

        <div v-if="success" class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-700 dark:text-emerald-400">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            {{ success }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Left column: forms -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Profile Info -->
                <div class="card p-6">
                    <h2 class="font-semibold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                        <i class="bi bi-person-circle text-slate-400"></i> Personal Information
                    </h2>
                    <form @submit.prevent="saveProfileInertia">
                        <!-- Avatar -->
                        <div class="flex items-start gap-5 mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                            <img :src="avatarPreview" class="w-16 h-16 rounded-2xl object-cover ring-2 ring-slate-200 dark:ring-slate-700 flex-shrink-0" alt="">
                            <div class="flex-1">
                                <input ref="avatarInput" type="file" accept="image/*" class="sr-only" @change="pickFile($event.target.files[0])">
                                <label @click.prevent="avatarInput.click()"
                                     @dragover.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @drop.prevent="isDragging = false; pickFile($event.dataTransfer?.files?.[0])"
                                     :class="isDragging ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                                     class="flex items-center gap-3 px-4 py-3 border-2 border-dashed rounded-xl cursor-pointer transition-colors">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                         :class="isDragging ? 'bg-violet-100 dark:bg-violet-900/40' : 'bg-slate-100 dark:bg-slate-800'">
                                        <i class="bi bi-cloud-upload text-sm"
                                           :class="isDragging ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400'"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                            <span class="text-violet-600 dark:text-violet-400">Click to upload</span> or drag &amp; drop
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">JPG, PNG, GIF, WEBP — max 2 MB</p>
                                    </div>
                                </label>
                                <p v-if="avatarError" class="mt-1.5 text-xs text-rose-500">{{ avatarError }}</p>
                                <p v-if="profileErrors.avatar" class="mt-1.5 text-xs text-rose-500">{{ profileErrors.avatar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Full Name <span class="text-rose-500">*</span></label>
                                <input v-model="profileForm.name" type="text" class="form-input" required>
                                <p v-if="profileErrors.name" class="form-error">{{ profileErrors.name }}</p>
                            </div>
                            <div>
                                <label class="form-label">Email <span class="text-rose-500">*</span></label>
                                <input v-model="profileForm.email" type="email" class="form-input" required>
                                <p v-if="profileErrors.email" class="form-error">{{ profileErrors.email }}</p>
                            </div>
                            <div>
                                <label class="form-label">Job Title</label>
                                <input v-model="profileForm.job_title" type="text" class="form-input" placeholder="e.g. Senior Developer">
                            </div>
                            <div>
                                <label class="form-label">Timezone</label>
                                <SearchSelect v-model="profileForm.timezone" :options="timezones" placeholder="Select timezone…" />
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-check-lg"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="card p-6">
                    <h2 class="font-semibold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                        <i class="bi bi-shield-lock text-slate-400"></i> Change Password
                    </h2>
                    <form @submit.prevent="savePassword">
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Current Password <span class="text-rose-500">*</span></label>
                                <input v-model="passwordForm.current_password" type="password" class="form-input" autocomplete="current-password">
                                <p v-if="passwordErrors.current_password" class="form-error">{{ passwordErrors.current_password }}</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">New Password <span class="text-rose-500">*</span></label>
                                    <input v-model="passwordForm.password" type="password" class="form-input" autocomplete="new-password">
                                    <p v-if="passwordErrors.password" class="form-error">{{ passwordErrors.password }}</p>
                                </div>
                                <div>
                                    <label class="form-label">Confirm New Password <span class="text-rose-500">*</span></label>
                                    <input v-model="passwordForm.password_confirmation" type="password" class="form-input" autocomplete="new-password">
                                </div>
                            </div>
                            <p class="text-xs text-slate-400">Use at least 8 characters with a mix of letters and numbers.</p>
                        </div>
                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-shield-check"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right column -->
            <div class="space-y-5">
                <!-- Account Info -->
                <div class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4 text-sm flex items-center gap-2">
                        <i class="bi bi-info-circle text-slate-400"></i> Account Info
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Member since</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ user.member_since }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Role</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400">{{ user.role }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Status</span>
                            <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Appearance -->
                <div class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4 text-sm flex items-center gap-2">
                        <i class="bi bi-palette text-slate-400"></i> Appearance
                    </h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Dark Mode</p>
                            <p class="text-xs text-slate-400 mt-0.5">Stored in your browser</p>
                        </div>
                        <button type="button" @click="toggleDark"
                                :class="darkMode ? 'bg-violet-600' : 'bg-slate-200 dark:bg-slate-700'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                  :class="darkMode ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm flex items-center gap-2">
                        <i class="bi bi-lightning text-slate-400"></i> Quick Links
                    </h3>
                    <div class="space-y-1">
                        <a :href="my_tasks_url" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                            <i class="bi bi-check-square text-slate-400 text-xs"></i> My Tasks
                        </a>
                        <a :href="reports_url" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                            <i class="bi bi-journal-text text-slate-400 text-xs"></i> My Reports
                        </a>
                        <a :href="time_url" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                            <i class="bi bi-clock text-slate-400 text-xs"></i> Time Tracking
                        </a>
                        <a :href="leave_url" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                            <i class="bi bi-calendar2-check text-slate-400 text-xs"></i> Leave Balance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
