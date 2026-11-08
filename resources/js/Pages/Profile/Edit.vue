<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user: Object,
    status: String,
    update_url: String,
    delete_url: String,
    resend_url: String,
})

const profileForm = ref({ name: props.user.name, email: props.user.email })
const profileErrors = ref({})
const profileProcessing = ref(false)
const showSaved = ref(props.status === 'profile-updated')

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref({})
const passwordProcessing = ref(false)

const deleteForm = ref({ password: '' })
const deleteErrors = ref({})
const deleteProcessing = ref(false)

function updateProfile() {
    profileProcessing.value = true
    router.patch(props.update_url, profileForm.value, {
        onSuccess: () => { showSaved.value = true; setTimeout(() => { showSaved.value = false }, 2000) },
        onError: (e) => { profileErrors.value = e },
        onFinish: () => { profileProcessing.value = false },
    })
}

function resendVerification() {
    router.post(props.resend_url)
}

function updatePassword() {
    passwordProcessing.value = true
    router.put(route('password.update'), passwordForm.value, {
        onSuccess: () => { passwordForm.value = { current_password: '', password: '', password_confirmation: '' } },
        onError: (e) => { passwordErrors.value = e },
        onFinish: () => { passwordProcessing.value = false },
    })
}

function deleteAccount() {
    if (!confirm('Are you sure you want to delete your account? This cannot be undone.')) return
    deleteProcessing.value = true
    router.delete(props.delete_url, { data: deleteForm.value, onError: (e) => { deleteErrors.value = e; deleteProcessing.value = false } })
}
</script>

<template>
    <AppLayout title="Profile">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Profile Information -->
                <div class="p-4 sm:p-8 bg-white dark:bg-slate-800 shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">Profile Information</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Update your account's profile information and email address.</p>

                        <form @submit.prevent="updateProfile" class="mt-6 space-y-6">
                            <div>
                                <label class="form-label">Name</label>
                                <input v-model="profileForm.name" type="text" required autofocus autocomplete="name" class="form-input mt-1">
                                <p v-if="profileErrors.name" class="text-xs text-red-500 mt-1">{{ profileErrors.name }}</p>
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input v-model="profileForm.email" type="email" required autocomplete="username" class="form-input mt-1">
                                <p v-if="profileErrors.email" class="text-xs text-red-500 mt-1">{{ profileErrors.email }}</p>

                                <div v-if="!user.is_verified">
                                    <p class="text-sm mt-2 text-slate-800 dark:text-slate-200">Your email address is unverified.
                                        <button type="button" @click="resendVerification" class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                                            Click here to re-send the verification email.
                                        </button>
                                    </p>
                                    <p v-if="status === 'verification-link-sent'" class="mt-2 font-medium text-sm text-green-600">A new verification link has been sent to your email address.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="submit" :disabled="profileProcessing" class="btn-primary">Save</button>
                                <p v-if="showSaved" class="text-sm text-slate-600 dark:text-slate-400 transition-opacity">Saved.</p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Update Password -->
                <div class="p-4 sm:p-8 bg-white dark:bg-slate-800 shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">Update Password</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Ensure your account is using a long, random password to stay secure.</p>

                        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
                            <div>
                                <label class="form-label">Current Password</label>
                                <input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="form-input mt-1">
                                <p v-if="passwordErrors.current_password" class="text-xs text-red-500 mt-1">{{ passwordErrors.current_password }}</p>
                            </div>
                            <div>
                                <label class="form-label">New Password</label>
                                <input v-model="passwordForm.password" type="password" autocomplete="new-password" class="form-input mt-1">
                                <p v-if="passwordErrors.password" class="text-xs text-red-500 mt-1">{{ passwordErrors.password }}</p>
                            </div>
                            <div>
                                <label class="form-label">Confirm Password</label>
                                <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="form-input mt-1">
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="submit" :disabled="passwordProcessing" class="btn-primary">Save</button>
                                <p v-if="status === 'password-updated'" class="text-sm text-slate-600 dark:text-slate-400">Saved.</p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Account -->
                <div class="p-4 sm:p-8 bg-white dark:bg-slate-800 shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">Delete Account</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.</p>

                        <div class="mt-6 space-y-4">
                            <div>
                                <label class="form-label">Password (to confirm)</label>
                                <input v-model="deleteForm.password" type="password" class="form-input mt-1" placeholder="Enter your password">
                                <p v-if="deleteErrors.password" class="text-xs text-red-500 mt-1">{{ deleteErrors.password }}</p>
                            </div>
                            <button type="button" @click="deleteAccount" :disabled="deleteProcessing" class="btn-danger">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
