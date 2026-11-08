<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const props = defineProps({
    register_url: String,
    login_url: String,
})

const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({})
const processing = ref(false)

function submit() {
    processing.value = true
    router.post(props.register_url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}
</script>

<template>
    <GuestLayout title="Create account">
        <div class="space-y-1 mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Create your account</h2>
            <p class="text-slate-500 text-sm">Join your team on Workzen</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-person text-sm"></i></span>
                    <input id="name" v-model="form.name" type="text" required autofocus autocomplete="name" placeholder="Jane Smith"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.name ? 'border-rose-400' : 'border-slate-200']">
                </div>
                <p v-if="errors.name" class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ errors.name }}</p>
            </div>

            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-envelope text-sm"></i></span>
                    <input id="email" v-model="form.email" type="email" required autocomplete="username" placeholder="you@company.com"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.email ? 'border-rose-400' : 'border-slate-200']">
                </div>
                <p v-if="errors.email" class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ errors.email }}</p>
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-lock text-sm"></i></span>
                    <input id="password" v-model="form.password" type="password" required autocomplete="new-password" placeholder="Min 8 characters"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.password ? 'border-rose-400' : 'border-slate-200']">
                </div>
                <p v-if="errors.password" class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ errors.password }}</p>
            </div>

            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-lock-fill text-sm"></i></span>
                    <input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" placeholder="Repeat password"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.password_confirmation ? 'border-rose-400' : 'border-slate-200']">
                </div>
                <p v-if="errors.password_confirmation" class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ errors.password_confirmation }}</p>
            </div>

            <button type="submit" :disabled="processing"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-md shadow-violet-200 transition text-sm disabled:opacity-60">
                <i class="bi bi-person-plus-fill"></i>
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Already have an account?
            <a :href="login_url" class="text-violet-600 hover:text-violet-500 font-medium transition">Sign in</a>
        </p>
    </GuestLayout>
</template>
