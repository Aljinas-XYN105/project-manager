<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' }, // HH:MM
    disabled:   { type: Boolean, default: false },
    compact:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const open    = ref(false)
const root    = ref(null)

const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'))
const minutes = ['00', '15', '30', '45']

const displayHour   = computed(() => props.modelValue ? props.modelValue.split(':')[0] : '')
const displayMinute = computed(() => props.modelValue ? props.modelValue.split(':')[1] : '')

const displayLabel = computed(() => {
    if (!props.modelValue) return 'Set time'
    const [h, m] = props.modelValue.split(':').map(Number)
    const ampm = h >= 12 ? 'PM' : 'AM'
    const h12  = h % 12 || 12
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`
})

function toggleOpen() {
    if (props.disabled) return
    open.value = !open.value
}

function pick(h, m) {
    emit('update:modelValue', `${h}:${m}`)
    open.value = false
}

function clear() {
    emit('update:modelValue', '')
    open.value = false
}

function handleOutside(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false
}

onMounted(()       => document.addEventListener('mousedown', handleOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutside))
</script>

<template>
    <div ref="root" class="relative inline-block">
        <!-- Compact chip trigger -->
        <button v-if="compact"
                type="button"
                @click="toggleOpen"
                :disabled="disabled"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                :class="[
                    disabled ? 'cursor-default opacity-50' : 'cursor-pointer',
                    modelValue
                        ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700'
                        : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300',
                ]">
            <i class="bi bi-clock text-[10px] flex-shrink-0"></i>
            <span>{{ displayLabel }}</span>
            <i v-if="!disabled" class="bi bi-chevron-down text-[9px] opacity-50 ml-0.5"></i>
        </button>

        <!-- Form-style trigger -->
        <button v-else
                type="button"
                @click="toggleOpen"
                :class="open ? 'ring-2 ring-violet-500/30 border-violet-500' : ''"
                class="form-input text-left flex items-center gap-2 w-full">
            <i class="bi bi-clock text-slate-400 flex-shrink-0 text-sm"></i>
            <span :class="modelValue ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'">
                {{ displayLabel }}
            </span>
            <i class="bi bi-chevron-down ml-auto text-slate-400 flex-shrink-0" style="font-size:0.65rem"
               :class="open ? 'rotate-180' : ''"></i>
        </button>

        <!-- Dropdown panel -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1">
            <div v-if="open"
                 class="absolute left-0 top-full mt-1 z-50 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl p-3"
                 style="min-width:14rem">

                <div class="flex gap-2">
                    <!-- Hours -->
                    <div class="flex-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5 text-center">Hour</p>
                        <div class="grid grid-cols-4 gap-0.5 max-h-44 overflow-y-auto">
                            <button v-for="h in hours" :key="h"
                                    type="button"
                                    @click="pick(h, displayMinute || '00')"
                                    class="text-xs h-7 rounded-md transition-colors"
                                    :class="displayHour === h
                                        ? 'bg-violet-600 text-white font-semibold'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'">
                                {{ h }}
                            </button>
                        </div>
                    </div>

                    <!-- Minutes -->
                    <div class="w-16">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1.5 text-center">Min</p>
                        <div class="flex flex-col gap-0.5">
                            <button v-for="m in minutes" :key="m"
                                    type="button"
                                    @click="pick(displayHour || '09', m)"
                                    class="text-xs h-7 rounded-md transition-colors"
                                    :class="displayMinute === m
                                        ? 'bg-violet-600 text-white font-semibold'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'">
                                :{{ m }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                    <button type="button" @click="clear"
                            class="text-xs text-slate-400 hover:text-slate-600">Clear</button>
                </div>
            </div>
        </Transition>
    </div>
</template>
