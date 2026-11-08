<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const props = defineProps({ confirm_url: String })

const form = ref({ password: '' })
const errors = ref({})
const processing = ref(false)

function submit() {
    processing.value = true
    router.post(props.confirm_url, form.value, {
        onError: (e) => { errors.value = e; processing.value = false },
        onFinish: () => { processing.value = false },
    })
}
</script>

<template>
    <GuestLayout title="Confirm password">
        <div class="space-y-1 mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Confirm your password</h2>
            <p class="text-slate-500 text-sm">This is a secure area. Please confirm your password before continuing.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i class="bi bi-lock text-sm"></i></span>
                    <input id="password" v-model="form.password" type="password" required autocomplete="current-password" placeholder="Your password"
                           :class="['w-full pl-9 pr-4 py-2.5 bg-white border hover:border-slate-300 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/15 rounded-xl text-slate-900 placeholder-slate-400 text-sm outline-none transition',
                                    errors.password ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/15' : 'border-slate-200']">
                </div>
                <p v-if="errors.password" class="flex items-center gap-1.5 text-rose-500 text-xs"><i class="bi bi-exclamation-circle"></i>{{ errors.password }}</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="processing"
                        class="flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold rounded-xl shadow-md shadow-violet-200 transition text-sm disabled:opacity-60">
                    Confirm
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
