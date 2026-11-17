<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useConfirm } from '@/composables/useConfirm'

const { confirmState, accept, reject } = useConfirm()

function onKeydown(e) {
    if (!confirmState.open) return
    if (e.key === 'Escape') reject()
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="confirmState.open"
                 class="fixed inset-0 z-[200] flex items-center justify-center p-4"
                 @keydown.escape="reject">
                <div class="absolute inset-0 backdrop-blur-sm bg-slate-950/60"
                     @click="reject"></div>
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="confirmState.open"
                         class="relative card w-full max-w-sm shadow-xl">
                        <div class="p-6">
                            <div class="flex items-start gap-3 mb-5">
                                <div :class="confirmState.danger
                                        ? 'bg-rose-100 dark:bg-rose-900/30'
                                        : 'bg-violet-100 dark:bg-violet-900/30'"
                                     class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i :class="confirmState.danger ? 'bi-exclamation-triangle-fill text-rose-500' : 'bi-question-circle-fill text-violet-500'"
                                       class="bi text-base"></i>
                                </div>
                                <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed pt-1.5">
                                    {{ confirmState.message }}
                                </p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="reject"
                                        class="btn-ghost btn-sm">
                                    Cancel
                                </button>
                                <button @click="accept"
                                        :class="confirmState.danger ? 'btn-danger' : 'btn-primary'"
                                        class="btn-sm">
                                    {{ confirmState.confirmLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
