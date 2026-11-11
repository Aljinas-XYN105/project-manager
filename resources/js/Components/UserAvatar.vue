<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    src:  { type: String, default: '' },
    name: { type: String, default: '' },
    size: { type: String, default: 'md' }, // xs | sm | md | lg | xl
})

const imgFailed = ref(false)

const initials = computed(() => {
    if (!props.name) return '?'
    const parts = props.name.trim().split(/\s+/)
    return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : props.name.slice(0, 2).toUpperCase()
})

const sizeClass = computed(() => ({
    xs: 'w-5 h-5 text-[9px]',
    sm: 'w-6 h-6 text-[10px]',
    md: 'w-8 h-8 text-xs',
    lg: 'w-10 h-10 text-sm',
    xl: 'w-12 h-12 text-base',
}[props.size] ?? 'w-8 h-8 text-xs'))

const showImg = computed(() => !!props.src && !imgFailed.value)
</script>

<template>
    <img v-if="showImg"
         :src="src"
         :alt="name"
         :class="[sizeClass, 'rounded-full object-cover flex-shrink-0']"
         @error="imgFailed = true" />
    <div v-else
         :class="[sizeClass, 'rounded-full bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-300 flex items-center justify-center font-semibold flex-shrink-0 select-none']">
        {{ initials }}
    </div>
</template>
