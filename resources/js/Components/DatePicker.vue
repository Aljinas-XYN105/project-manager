<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    name:        { type: String, default: 'date' },
    placeholder: { type: String, default: 'Select date' },
    id:          { type: String, default: null },
    min:         { type: String, default: '' },
    max:         { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const open      = ref(false)
const alignRight = ref(false)
const root      = ref(null)
const viewYear  = ref(0)
const viewMonth = ref(0)

function initView() {
    const base = props.modelValue ? new Date(props.modelValue + 'T00:00:00') : new Date()
    viewYear.value  = base.getFullYear()
    viewMonth.value = base.getMonth()
}
initView()

function toggleOpen() {
    open.value = !open.value
    if (open.value) {
        setTimeout(() => {
            if (root.value) {
                const rect = root.value.getBoundingClientRect()
                alignRight.value = (rect.left + 352) > window.innerWidth
            }
        }, 0)
    }
}

function formatDisplay(iso) {
    if (!iso) return ''
    const [y, m, d] = iso.split('-').map(Number)
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
    return months[m - 1] + ' ' + d + ', ' + y
}

const monthLabel = computed(() => {
    const months = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December']
    return months[viewMonth.value] + ' ' + viewYear.value
})

const daysInMonth = computed(() => new Date(viewYear.value, viewMonth.value + 1, 0).getDate())
const startDay    = computed(() => new Date(viewYear.value, viewMonth.value, 1).getDay())
const leadingCells = computed(() => Array.from({ length: startDay.value }, (_, i) => i))
const dayCells    = computed(() => Array.from({ length: daysInMonth.value }, (_, i) => i + 1))

function prevMonth() {
    if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value-- }
    else viewMonth.value--
}
function nextMonth() {
    if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++ }
    else viewMonth.value++
}

function padded(n) { return String(n).padStart(2, '0') }

function iso(day) {
    return viewYear.value + '-' + padded(viewMonth.value + 1) + '-' + padded(day)
}

function isDisabled(day) {
    const d = iso(day)
    if (props.min && d < props.min) return true
    if (props.max && d > props.max) return true
    return false
}
function isSelected(day)  { return props.modelValue === iso(day) }
function isToday(day) {
    const t = new Date()
    return t.getFullYear() === viewYear.value && t.getMonth() === viewMonth.value && t.getDate() === day
}

function selectDay(day) {
    if (isDisabled(day)) return
    emit('update:modelValue', iso(day))
    open.value = false
}

function goToday() {
    const t = new Date()
    viewYear.value  = t.getFullYear()
    viewMonth.value = t.getMonth()
    emit('update:modelValue', t.getFullYear() + '-' + padded(t.getMonth() + 1) + '-' + padded(t.getDate()))
    open.value = false
}

function clear() {
    emit('update:modelValue', '')
    open.value = false
}

function handleOutsideClick(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false
}

onMounted(()       => document.addEventListener('mousedown', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutsideClick))
</script>

<template>
    <div ref="root" class="relative">
        <!-- Hidden real input for form submission -->
        <input type="hidden" :name="name" :value="modelValue" :id="id ?? undefined">

        <!-- Trigger button -->
        <button type="button"
                @click="toggleOpen"
                :class="open ? 'ring-2 ring-violet-500/30 border-violet-500 dark:border-violet-400' : ''"
                class="form-input text-left flex items-center gap-2 w-full">
            <i class="bi bi-calendar3 text-slate-400 flex-shrink-0 text-sm"></i>
            <span :class="modelValue ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'">
                {{ modelValue ? formatDisplay(modelValue) : placeholder }}
            </span>
            <i class="bi bi-chevron-down ml-auto text-slate-400 transition-transform duration-150 flex-shrink-0"
               :class="open ? 'rotate-180' : ''"
               style="font-size:0.65rem"></i>
        </button>

        <!-- Calendar dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1">
            <div v-if="open"
                 :class="alignRight ? 'right-0' : 'left-0'"
                 style="min-width:22rem"
                 class="absolute z-50 mt-1 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl p-4">

                <!-- Month / Year navigation -->
                <div class="flex items-center justify-between mb-3">
                    <button type="button" @click="prevMonth"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ monthLabel }}</span>
                    <button type="button" @click="nextMonth"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>

                <!-- Day-of-week headers -->
                <div class="grid grid-cols-7 mb-1">
                    <div v-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']" :key="d"
                         class="text-center text-xs font-medium text-slate-400 dark:text-slate-500 py-1">{{ d }}</div>
                </div>

                <!-- Day grid -->
                <div class="grid grid-cols-7 gap-y-0.5">
                    <div v-for="i in leadingCells" :key="'e'+i"></div>
                    <button v-for="day in dayCells" :key="day"
                            type="button"
                            @click="selectDay(day)"
                            :disabled="isDisabled(day)"
                            :class="{
                                'bg-violet-600 text-white font-semibold hover:bg-violet-700': isSelected(day),
                                'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 font-medium': isToday(day) && !isSelected(day) && !isDisabled(day),
                                'text-slate-300 dark:text-slate-600 cursor-not-allowed': isDisabled(day),
                                'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800': !isSelected(day) && !isToday(day) && !isDisabled(day),
                            }"
                            class="text-sm h-10 w-full rounded-lg transition-colors">{{ day }}</button>
                </div>

                <!-- Today / Clear -->
                <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <button type="button" @click="goToday"
                            class="text-xs text-violet-500 hover:text-violet-700 font-medium">Today</button>
                    <button type="button" @click="clear"
                            class="text-xs text-slate-400 hover:text-slate-600">Clear</button>
                </div>
            </div>
        </Transition>
    </div>
</template>
