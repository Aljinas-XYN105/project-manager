<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
    modelValue: { type: [Number, String], default: null },
    projects: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select project…' },
    clearable: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // 'sm' | 'md'
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const searchInput = ref(null)
const wrapper = ref(null)

const selected = computed(() =>
    props.projects.find(p => p.id === props.modelValue) ?? null
)

const displayText = computed(() => {
    if (props.clearable && !props.modelValue) return ''
    return selected.value?.name ?? ''
})

const filtered = computed(() => {
    const q = query.value.toLowerCase().trim()
    if (!q) return props.projects
    return props.projects.filter(p => p.name.toLowerCase().includes(q))
})

function openDropdown() {
    open.value = true
    query.value = ''
    nextTick(() => searchInput.value?.focus())
}

function closeDropdown() {
    open.value = false
    query.value = ''
}

function pick(id) {
    emit('update:modelValue', id)
    closeDropdown()
}

function handleBlur(e) {
    // close only if focus moved outside the component
    if (!wrapper.value?.contains(e.relatedTarget)) {
        setTimeout(closeDropdown, 100)
    }
}

function onKeydown(e) {
    if (e.key === 'Escape') closeDropdown()
}
</script>

<template>
    <div ref="wrapper" class="relative" @keydown="onKeydown">
        <!-- Trigger button -->
        <button
            type="button"
            :class="[
                'form-input w-full text-left flex items-center justify-between gap-2 cursor-pointer',
                size === 'sm' ? 'py-1.5 text-sm' : ''
            ]"
            @click="open ? closeDropdown() : openDropdown()"
            @blur="handleBlur"
        >
            <span :class="displayText ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'">
                {{ displayText || (clearable ? 'All Projects' : placeholder) }}
            </span>
            <i class="bi bi-chevron-down text-slate-400 text-xs flex-shrink-0 transition-transform"
               :class="open ? 'rotate-180' : ''"></i>
        </button>

        <!-- Dropdown -->
        <div v-if="open"
             class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg overflow-hidden"
        >
            <!-- Search input -->
            <div class="relative border-b border-slate-100 dark:border-slate-700">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    placeholder="Search projects…"
                    class="w-full pl-8 pr-3 py-2 text-sm bg-transparent outline-none text-slate-800 dark:text-slate-100 placeholder:text-slate-400"
                    @blur="handleBlur"
                />
            </div>

            <!-- Options list -->
            <ul class="max-h-52 overflow-y-auto py-1">
                <!-- Clear / All Projects option -->
                <li v-if="clearable">
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 text-sm transition-colors"
                        :class="!modelValue
                            ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-medium'
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                        @mousedown.prevent="pick(null)"
                    >
                        All Projects
                    </button>
                </li>

                <li v-for="p in filtered" :key="p.id">
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 text-sm transition-colors"
                        :class="modelValue === p.id
                            ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-medium'
                            : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                        @mousedown.prevent="pick(p.id)"
                    >
                        {{ p.name }}
                    </button>
                </li>

                <li v-if="filtered.length === 0" class="px-3 py-3 text-sm text-slate-400 dark:text-slate-500 text-center">
                    No projects found
                </li>
            </ul>
        </div>
    </div>
</template>
