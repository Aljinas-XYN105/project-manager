<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const props = defineProps({
    status: String,
    email_url: String,
    login_url: String,
})

const form = ref({ email: '' })
const errors = ref({})
const processing = ref(false)

function submit() {
    processing.value = true
    router.post(props.email_url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}
</script>

<template>
    <GuestLayout title="Forgot password">
        <div class="space-y-2 mb-8">
            <div class="w-12 h-12 bg-violet-600/15 border border-violet-500/25 rounded-2xl flex items-center justify-center mb-4">
                <i class="bi bi-key text-violet-400 text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">Forgot password?</h2>
            <p class="text-slate-500 text-sm">Enter your email and we'll send a reset link.</p>
        </div>

        <div v-if="status" class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-envelope text-sm"></i></span>
                    <input id="email" v-model="form.email" type="email" required autofocus placeholder="you@company.com"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.email ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/15' : 'border-slate-200']">
                </div>
                <p v-if="errors.email" class="flex items-center gap-1.5 text-rose-500 text-xs mt-1"><i class="bi bi-exclamation-circle"></i>{{ errors.email }}</p>
            </div>

            <button type="submit" :disabled="processing"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-md shadow-violet-200 transition text-sm disabled:opacity-60">
                <i class="bi bi-send"></i>
                Send reset link
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            <a :href="login_url" class="text-violet-600 hover:text-violet-500 font-medium transition">
                <i class="bi bi-arrow-left text-xs mr-1"></i>Back to sign in
            </a>
        </p>
    </GuestLayout>
</template>
