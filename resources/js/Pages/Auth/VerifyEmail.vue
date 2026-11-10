<script setup>
import { router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const props = defineProps({
    status: String,
    resend_url: String,
    logout_url: String,
})

function resend() {
    router.post(props.resend_url)
}

function logout() {
    router.post(props.logout_url)
}
</script>

<template>
    <GuestLayout title="Verify email">
        <div class="space-y-1 mb-8">
            <div class="w-12 h-12 bg-emerald-100 border border-emerald-200 rounded-2xl flex items-center justify-center mb-4">
                <i class="bi bi-envelope-check text-emerald-600 text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">Verify your email</h2>
            <p class="text-slate-500 text-sm">
                Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
                If you didn't receive the email, we will gladly send you another.
            </p>
        </div>

        <div v-if="status === 'verification-link-sent'" class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <div class="flex items-center justify-between gap-4">
            <button type="button" @click="resend"
                    class="flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-md shadow-violet-200 transition text-sm">
                <i class="bi bi-send"></i>
                Resend Verification Email
            </button>
            <button type="button" @click="logout"
                    class="text-sm text-slate-500 underline hover:text-slate-700 transition">
                Log Out
            </button>
        </div>
    </GuestLayout>
</template>
