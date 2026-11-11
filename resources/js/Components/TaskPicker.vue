<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    options:    { type: Array,  default: () => [] }, // [{ id, label }]
})
const emit = defineEmits(['update:modelValue'])

const open    = ref(false)
const search  = ref('')
const root    = ref(null)
const inputEl = ref(null)

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.options
    return props.options.filter(o => o.label.toLowerCase().includes(q))
})

const selectedLabel = computed(() => {
    if (!props.modelValue || props.modelValue === '') return null
    if (props.modelValue === '__new__') return null
    const found = props.options.find(o => String(o.id) === props.modelValue)
    return found?.label ?? null
})

function toggle() {
    open.value = !open.value
    if (open.value) {
        search.value = ''
        nextTick(() => inputEl.value?.focus())
    }
}

function pick(val) {
    emit('update:modelValue', val)
    open.value = false
    search.value = ''
}

function handleOutside(e) {
    if (root.value && !root.value.contains(e.target)) {
        open.value = false
    }
}

onMounted(()       => document.addEventListener('mousedown', handleOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutside))
</script>

<template>
    <div ref="root" class="relative">
        <!-- Trigger -->
        <button type="button" @click="toggle"
                class="form-input text-sm text-left flex items-center justify-between gap-2 w-full"
                :class="modelValue === '__new__' ? 'border-violet-400 ring-1 ring-violet-300 dark:ring-violet-700' : ''">
            <span v-if="modelValue === '__new__'"
                  class="flex items-center gap-1.5 text-violet-600 dark:text-violet-400 font-medium">
                <i class="bi bi-plus-circle-fill text-xs"></i>
                Create a new task…
            </span>
            <span v-else-if="selectedLabel" class="truncate text-slate-800 dark:text-slate-200">{{ selectedLabel }}</span>
            <span v-else class="text-slate-400">— Not linked to a task —</span>
            <i class="bi bi-chevron-down flex-shrink-0 text-slate-400 text-[0.65rem] transition-transform"
               :class="open ? 'rotate-180' : ''"></i>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1">
            <div v-if="open"
                 class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden"
                 style="z-index:300">

                <!-- Search input -->
                <div class="p-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="relative">
                        <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input ref="inputEl"
                               v-model="search"
                               type="text"
                               placeholder="Search tasks…"
                               class="w-full pl-7 pr-3 py-1.5 text-sm rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 focus:outline-none focus:border-violet-400 dark:focus:border-violet-500 text-slate-800 dark:text-slate-200 placeholder-slate-400">
                    </div>
                </div>

                <div class="max-h-56 overflow-y-auto py-1">
                    <!-- Not linked option -->
                    <button type="button" @click="pick('')"
                            class="flex items-center gap-2 w-full px-3 py-2 text-sm transition-colors text-left"
                            :class="(!modelValue || modelValue === '')
                                ? 'bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-200 font-medium'
                                : 'hover:bg-slate-50 dark:hover:bg-slate-700/40 text-slate-500 dark:text-slate-400'">
                        <i class="bi bi-dash-circle text-slate-400 text-xs flex-shrink-0"></i>
                        <span class="flex-1">Not linked to a task</span>
                        <i v-if="!modelValue || modelValue === ''" class="bi bi-check2 text-violet-500 text-xs"></i>
                    </button>

                    <!-- Create new option -->
                    <button type="button" @click="pick('__new__')"
                            class="flex items-center gap-2 w-full px-3 py-2 text-sm transition-colors text-left border-b border-slate-100 dark:border-slate-700/60"
                            :class="modelValue === '__new__'
                                ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-medium'
                                : 'hover:bg-violet-50/60 dark:hover:bg-violet-900/10 text-violet-600 dark:text-violet-400'">
                        <i class="bi bi-plus-circle-fill text-xs flex-shrink-0"></i>
                        <span class="flex-1 font-medium">Create a new task…</span>
                        <i v-if="modelValue === '__new__'" class="bi bi-check2 text-violet-500 text-xs"></i>
                    </button>

                    <!-- Task list -->
                    <template v-if="filtered.length">
                        <button v-for="opt in filtered" :key="opt.id" type="button"
                                @click="pick(String(opt.id))"
                                class="flex items-center gap-2 w-full px-3 py-2 text-sm transition-colors text-left"
                                :class="String(opt.id) === modelValue
                                    ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-medium'
                                    : 'hover:bg-slate-50 dark:hover:bg-slate-700/40 text-slate-700 dark:text-slate-200'">
                            <i class="bi bi-check2-square text-violet-400 text-xs flex-shrink-0"></i>
                            <span class="flex-1 truncate">{{ opt.label }}</span>
                            <i v-if="String(opt.id) === modelValue" class="bi bi-check2 text-violet-500 text-xs flex-shrink-0"></i>
                        </button>
                    </template>

                    <!-- Empty state -->
                    <div v-else-if="search" class="px-3 py-4 text-center text-xs text-slate-400">
                        No tasks match "<span class="font-medium">{{ search }}</span>"
                    </div>
                    <div v-else class="px-3 py-4 text-center text-xs text-slate-400">
                        No tasks assigned to you
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
