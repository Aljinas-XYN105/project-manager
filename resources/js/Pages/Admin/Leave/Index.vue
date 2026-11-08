<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    types: Array,
    holidays: Array,
    year: Number,
    summary: Object,
    leave_type_colors: Array,
    store_type_url: String,
    store_holiday_url: String,
    approvals_url: String,
    calendar_url: String,
    prev_year_url: String,
    next_year_url: String,
})

const editingType = ref(null)
const editForm = ref({})

function startEdit(type) {
    editingType.value = type.id
    editForm.value = { name: type.name, days_per_year: type.days_per_year, color: type.color, is_paid: type.is_paid, is_active: type.is_active }
}
function cancelEdit() { editingType.value = null }

function saveType(type) {
    router.put(type.update_url, editForm.value, {
        onSuccess: () => { editingType.value = null },
        preserveScroll: true,
    })
}

function deleteType(type) {
    if (!confirm(`Delete ${type.name}?`)) return
    router.delete(type.destroy_url, { preserveScroll: true })
}

const newType = ref({ name: '', days_per_year: 5, color: 'violet', is_paid: true })
function storeType() {
    router.post(props.store_type_url, newType.value, {
        onSuccess: () => { newType.value = { name: '', days_per_year: 5, color: 'violet', is_paid: true } },
        preserveScroll: true,
    })
}

const newHoliday = ref({ date: '', name: '' })
function storeHoliday() {
    router.post(props.store_holiday_url, newHoliday.value, {
        onSuccess: () => { newHoliday.value = { date: '', name: '' } },
        preserveScroll: true,
    })
}

function deleteHoliday(h) {
    if (!confirm(`Remove ${h.name}?`)) return
    router.delete(h.destroy_url, { preserveScroll: true })
}

const kpiCards = [
    { key: 'pending', label: 'Pending requests', icon: 'bi-hourglass-split', color: 'amber' },
    { key: 'on_leave_today', label: 'On leave today', icon: 'bi-airplane', color: 'violet' },
    { key: 'approved_year', label: `Days approved in ${new Date().getFullYear()}`, icon: 'bi-calendar-check', color: 'emerald' },
]
const colorBg = { amber: 'bg-amber-100 dark:bg-amber-900/30', violet: 'bg-violet-100 dark:bg-violet-900/30', emerald: 'bg-emerald-100 dark:bg-emerald-900/30' }
const colorText = { amber: 'text-amber-600 dark:text-amber-400', violet: 'text-violet-600 dark:text-violet-400', emerald: 'text-emerald-600 dark:text-emerald-400' }
</script>

<template>
    <AppLayout title="Leave & Holidays">
        <div class="page-header">
            <div>
                <h1 class="page-title">Leave &amp; Holidays</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Leave types and yearly allowances, plus the company holiday calendar.</p>
            </div>
            <div class="flex gap-2">
                <a :href="approvals_url" class="btn-secondary">
                    <i class="bi bi-inbox"></i> Approvals
                    <span v-if="summary.pending" class="ml-1 px-1.5 rounded-full bg-amber-500 text-white text-[10px]">{{ summary.pending }}</span>
                </a>
                <a :href="calendar_url" class="btn-ghost"><i class="bi bi-calendar3"></i> Calendar</a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div v-for="card in kpiCards" :key="card.key" class="card p-4 flex items-center gap-3">
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', colorBg[card.color]]">
                    <i :class="['bi', card.icon, colorText[card.color]]"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ summary[card.key] }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Leave types -->
            <div class="card overflow-hidden self-start">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Leave types</h2>
                    <p class="text-xs text-slate-500">Set days per year to 0 for unlimited (e.g. unpaid leave).</p>
                </div>

                <div v-for="type in types" :key="type.id"
                     :class="['px-5 py-3 border-b border-slate-50 dark:border-slate-800', !type.is_active ? 'opacity-60' : '']">
                    <!-- View mode -->
                    <div v-if="editingType !== type.id" class="flex items-center gap-3">
                        <span :class="['w-3 h-3 rounded-full', 'bg-' + type.color + '-500']"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ type.name }} <span v-if="!type.is_active" class="text-xs text-slate-400">(inactive)</span></p>
                            <p class="text-xs text-slate-500">
                                {{ type.is_unlimited ? 'Unlimited' : type.days_per_year + ' days / year' }}
                                · {{ type.is_paid ? 'Paid' : 'Unpaid' }} · {{ type.used_this_year }} approved this year
                            </p>
                        </div>
                        <button @click="startEdit(type)" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i></button>
                        <button @click="deleteType(type)" class="btn-ghost btn-sm text-rose-500"><i class="bi bi-trash"></i></button>
                    </div>
                    <!-- Edit mode -->
                    <div v-else class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-end">
                        <div class="col-span-2"><label class="form-label text-xs">Name</label><input v-model="editForm.name" required class="form-input text-sm"></div>
                        <div><label class="form-label text-xs">Days/yr</label><input v-model="editForm.days_per_year" type="number" min="0" max="365" step="0.5" required class="form-input text-sm"></div>
                        <div>
                            <label class="form-label text-xs">Colour</label>
                            <select v-model="editForm.color" class="form-input text-sm">
                                <option v-for="c in leave_type_colors" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600 dark:text-slate-400">
                            <label class="flex items-center gap-1.5"><input type="checkbox" v-model="editForm.is_paid" class="rounded text-violet-600"> Paid</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" v-model="editForm.is_active" class="rounded text-violet-600"> Active</label>
                        </div>
                        <div class="flex gap-1">
                            <button type="button" @click="saveType(type)" class="btn-primary btn-sm">Save</button>
                            <button type="button" @click="cancelEdit" class="btn-ghost btn-sm">✕</button>
                        </div>
                    </div>
                </div>

                <!-- Add new type -->
                <div class="px-5 py-4 bg-slate-50/60 dark:bg-slate-800/30 grid grid-cols-2 sm:grid-cols-6 gap-2 items-end">
                    <div class="col-span-2"><label class="form-label text-xs">New type</label><input v-model="newType.name" required placeholder="e.g. Parental Leave" class="form-input text-sm"></div>
                    <div><label class="form-label text-xs">Days/yr</label><input v-model="newType.days_per_year" type="number" min="0" max="365" step="0.5" required class="form-input text-sm"></div>
                    <div>
                        <label class="form-label text-xs">Colour</label>
                        <select v-model="newType.color" class="form-input text-sm">
                            <option v-for="c in leave_type_colors" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 pb-2">
                        <input type="checkbox" v-model="newType.is_paid" class="rounded text-violet-600"> Paid
                    </label>
                    <button type="button" @click="storeType" class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add</button>
                </div>
            </div>

            <!-- Holidays -->
            <div class="card overflow-hidden self-start">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Holidays {{ year }}</h2>
                        <p class="text-xs text-slate-500">Holidays aren't counted as leave days.</p>
                    </div>
                    <div class="flex gap-1">
                        <a :href="prev_year_url" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>
                        <a :href="next_year_url" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>

                <template v-if="holidays.length">
                    <div v-for="h in holidays" :key="h.id"
                         :class="['flex items-center gap-3 px-5 py-2.5 border-b border-slate-50 dark:border-slate-800', h.is_past ? 'opacity-50' : '']">
                        <span class="w-24 text-sm font-mono text-slate-600 dark:text-slate-400">{{ h.date_formatted }}</span>
                        <span class="flex-1 text-sm text-slate-900 dark:text-white">{{ h.name }}</span>
                        <button @click="deleteHoliday(h)" class="btn-ghost btn-sm text-rose-500"><i class="bi bi-trash"></i></button>
                    </div>
                </template>
                <p v-else class="px-5 py-8 text-center text-sm text-slate-400">No holidays added for {{ year }}.</p>

                <!-- Add holiday -->
                <div class="px-5 py-4 bg-slate-50/60 dark:bg-slate-800/30 flex flex-wrap gap-2 items-end">
                    <div><label class="form-label text-xs">Date</label><input v-model="newHoliday.date" type="date" class="form-input text-sm"></div>
                    <div class="flex-1 min-w-[10rem]"><label class="form-label text-xs">Name</label><input v-model="newHoliday.name" required placeholder="e.g. Thanksgiving" class="form-input text-sm"></div>
                    <button type="button" @click="storeHoliday" class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add holiday</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
