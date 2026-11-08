<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const props = defineProps({
    status: String,
    login_url: String,
    forgot_url: String,
    register_url: String,
})

const form = ref({ email: '', password: '', remember: false })
const errors = ref({})
const processing = ref(false)
const showPassword = ref(false)

function submit() {
    processing.value = true
    router.post(props.login_url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}
</script>

<template>
    <GuestLayout title="Sign in">
        <div class="space-y-1 mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
            <p class="text-slate-500 text-sm">Sign in to your Workzen workspace</p>
        </div>

        <div v-if="status" class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="bi bi-envelope text-sm"></i>
                    </span>
                    <input id="email" v-model="form.email" type="email" required autofocus autocomplete="username"
                           placeholder="you@company.com"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.email ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/15' : 'border-slate-200']">
                </div>
                <p v-if="errors.email" class="flex items-center gap-1.5 text-rose-500 text-xs">
                    <i class="bi bi-exclamation-circle"></i>{{ errors.email }}
                </p>
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <a v-if="forgot_url" :href="forgot_url" class="text-xs text-violet-600 hover:text-violet-500 transition">Forgot password?</a>
                </div>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="bi bi-lock text-sm"></i>
                    </span>
                    <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                           required autocomplete="current-password" placeholder="••••••••"
                           :class="['w-full pl-9 pr-10 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.password ? 'border-rose-400' : 'border-slate-200']">
                    <button type="button" tabindex="-1" @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i :class="showPassword ? 'bi bi-eye-slash text-sm' : 'bi bi-eye text-sm'"></i>
                    </button>
                </div>
                <p v-if="errors.password" class="flex items-center gap-1.5 text-rose-500 text-xs">
                    <i class="bi bi-exclamation-circle"></i>{{ errors.password }}
                </p>
            </div>

            <!-- Remember me -->
            <label class="flex items-center gap-2.5 cursor-pointer select-none group">
                <div class="relative">
                    <input v-model="form.remember" type="checkbox" class="sr-only peer">
                    <div class="w-4 h-4 rounded border border-slate-300 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition"></div>
                    <i class="bi bi-check text-white text-xs absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition pointer-events-none"></i>
                </div>
                <span class="text-sm text-slate-500 group-hover:text-slate-700 transition">Remember me for 30 days</span>
            </label>

            <!-- Submit -->
            <button type="submit" :disabled="processing"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-md shadow-violet-200 transition text-sm disabled:opacity-60">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign in
            </button>
        </form>

        <p v-if="register_url" class="mt-6 text-center text-sm text-slate-500">
            Don't have an account?
            <a :href="register_url" class="text-violet-600 hover:text-violet-500 font-medium transition">Create one</a>
        </p>
    </GuestLayout>
</template>
