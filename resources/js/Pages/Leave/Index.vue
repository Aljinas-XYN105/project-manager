<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    year: Number,
    balances: Array,
    types: Array,
    requests: Object,
    holidays: Array,
    pending_review: Number,
    manager_name: String,
})

const showApply = ref(false)
const applyForm = useForm({
    leave_type_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date().toISOString().slice(0, 10),
    is_half_day: false,
    reason: '',
})

function submitLeave() {
    applyForm.post(route('leave.store'), {
        onSuccess: () => {
            showApply.value = false
            applyForm.reset()
        },
    })
}

function cancelLeave(url) {
    if (!confirm('Cancel this leave request?')) return
    router.patch(url, {}, { preserveScroll: true })
}

function formatDays(d) {
    const n = parseFloat(d)
    return Number.isInteger(n) ? n.toString() : n.toFixed(1).replace(/\.?0+$/, '')
}

const statusClass = {
    pending:  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    cancelled:'bg-slate-100 text-slate-500 dark:bg-slate-800',
}
const statusLabel = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected', cancelled: 'Cancelled' }
</script>

<template>
    <AppLayout title="My Leave">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('dashboard')" class="hover:text-violet-600 transition-colors">Home</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Leave</span>
                </div>
                <h1 class="page-title">My Leave</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Balances for {{ year }}. Weekends and company holidays are not counted.</p>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('leave.calendar')" class="btn-ghost"><i class="bi bi-calendar3"></i> Team calendar</Link>
                <Link v-if="pending_review" :href="route('leave.approvals')" class="btn-secondary">
                    <i class="bi bi-inbox"></i> Approvals
                    <span class="ml-1 px-1.5 rounded-full bg-amber-500 text-white text-[10px]">{{ pending_review }}</span>
                </Link>
                <button @click="showApply = true" class="btn-primary"><i class="bi bi-plus-lg"></i> Request leave</button>
            </div>
        </div>

        <!-- Balances -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div v-for="b in balances" :key="b.type.id" class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ b.type.name }}</p>
                    <span class="w-2.5 h-2.5 rounded-full" :class="`bg-${b.type.color}-500`"></span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white leading-none">
                    {{ b.remaining === null ? '∞' : formatDays(b.remaining) }}
                    <span class="text-sm font-medium text-slate-400">{{ b.remaining === null ? 'unlimited' : 'days left' }}</span>
                </p>
                <div v-if="!b.type.is_unlimited" class="mt-3 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full" :class="`bg-${b.type.color}-500`"
                         :style="`width: ${b.type.days_per_year > 0 ? Math.min(100, ((b.approved + b.pending) / b.type.days_per_year) * 100) : 0}%`"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    {{ formatDays(b.approved) }} used
                    <template v-if="b.pending"> · {{ formatDays(b.pending) }} pending</template>
                    <template v-if="!b.type.is_unlimited"> · of {{ formatDays(b.type.days_per_year) }}</template>
                    <template v-if="!b.type.is_paid"> · unpaid</template>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Requests -->
            <div class="xl:col-span-2 card overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">My requests</h2>
                </div>
                <div v-if="requests.data.length === 0" class="empty-state py-14">
                    <i class="bi bi-airplane text-4xl text-slate-300 dark:text-slate-600"></i>
                    <p class="text-sm text-slate-500 mt-3">No leave requests yet.</p>
                </div>
                <div v-for="r in requests.data" :key="r.id"
                     class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-3.5 border-b border-slate-50 dark:border-slate-800 last:border-b-0">
                    <span class="w-1 self-stretch rounded-full hidden sm:block" :class="`bg-${r.type.color}-500`"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ r.type.name }} · {{ formatDays(r.days) }} {{ r.days > 1 ? 'days' : 'day' }}</p>
                        <p class="text-xs text-slate-500">{{ r.date_range }}</p>
                        <p v-if="r.reason" class="text-xs text-slate-400 mt-0.5 truncate">"{{ r.reason }}"</p>
                        <p v-if="r.reviewer" class="text-xs text-slate-400 mt-0.5">
                            {{ r.status.charAt(0).toUpperCase() + r.status.slice(1) }} by {{ r.reviewer.name }} {{ r.reviewed_at }}
                            <template v-if="r.review_note"> — "{{ r.review_note }}"</template>
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full font-medium self-start sm:self-center"
                          :class="statusClass[r.status]">
                        {{ statusLabel[r.status] }}
                    </span>
                    <button v-if="r.can_cancel" @click="cancelLeave(r.cancel_url)" class="btn-ghost btn-sm text-rose-500">Cancel</button>
                </div>
                <div v-if="requests.last_page > 1" class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1">
                    <template v-for="link in requests.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" class="px-2 py-1 rounded text-xs"
                              :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100'" v-html="link.label" />
                        <span v-else class="px-2 py-1 text-xs text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>

            <!-- Upcoming holidays -->
            <div class="card overflow-hidden self-start">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Upcoming holidays</h2>
                </div>
                <div v-if="holidays.length === 0" class="px-5 py-8 text-center text-sm text-slate-400">
                    No upcoming holidays have been added.
                </div>
                <div v-for="h in holidays" :key="h.name"
                     class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800 last:border-b-0">
                    <div class="w-11 text-center flex-shrink-0">
                        <p class="text-[10px] font-semibold uppercase text-rose-500">{{ h.month }}</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-white leading-none">{{ h.day }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ h.name }}</p>
                        <p class="text-xs text-slate-400">{{ h.weekday }} · {{ h.ago }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apply modal -->
        <Teleport to="body">
            <div v-if="showApply" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape="showApply = false">
                <div class="absolute inset-0 backdrop-blur-sm bg-slate-950/60" @click="showApply = false"></div>
                <div class="relative card w-full max-w-md overflow-hidden flex flex-col max-h-[90dvh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Request leave</h3>
                        <button type="button" @click="showApply = false" class="p-1 -mr-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i class="bi bi-x-lg text-base"></i>
                        </button>
                    </div>
                    <form @submit.prevent="submitLeave" class="px-6 py-5 space-y-4 overflow-y-auto">
                        <div v-if="applyForm.errors && Object.keys(applyForm.errors).length" class="text-sm text-rose-600">
                            {{ Object.values(applyForm.errors)[0] }}
                        </div>
                        <div>
                            <label class="form-label">Leave type <span class="text-rose-500">*</span></label>
                            <SearchSelect v-model="applyForm.leave_type_id"
                                :options="balances.map(b => ({value: b.type.id, label: b.label}))"
                                placeholder="Select type…" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">From <span class="text-rose-500">*</span></label>
                                <DatePicker v-model="applyForm.start_date" />
                            </div>
                            <div>
                                <label class="form-label">To <span class="text-rose-500">*</span></label>
                                <DatePicker v-model="applyForm.end_date" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                               :class="applyForm.start_date !== applyForm.end_date ? 'opacity-50' : ''">
                            <input type="checkbox" v-model="applyForm.is_half_day"
                                   :disabled="applyForm.start_date !== applyForm.end_date"
                                   class="rounded text-violet-600 border-slate-300">
                            Half day (single-day requests only)
                        </label>
                        <div>
                            <label class="form-label">Reason <span class="text-slate-400 font-normal">(optional)</span></label>
                            <textarea v-model="applyForm.reason" rows="3" maxlength="1000" class="form-input resize-none" placeholder="e.g. Family trip"></textarea>
                        </div>
                        <p class="text-xs text-slate-400">
                            Goes to {{ manager_name ? `${manager_name} (your manager)` : 'the leave approvers' }} for approval.
                        </p>
                    </form>
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex justify-end gap-2 flex-shrink-0">
                        <button type="button" @click="showApply = false" class="btn-ghost">Cancel</button>
                        <button @click="submitLeave" class="btn-primary" :disabled="applyForm.processing">
                            <i class="bi bi-send"></i> Submit request
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
