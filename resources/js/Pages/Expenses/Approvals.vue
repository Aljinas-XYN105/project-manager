<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    expenses: Object,
    status: String,
    index_url: String,
})

const rejectNotes = ref({})
const rejectOpen = ref({})

function openReject(id) {
    rejectOpen.value[id] = true
    rejectNotes.value[id] = ''
}
function cancelReject(id) {
    rejectOpen.value[id] = false
    rejectNotes.value[id] = ''
}

async function approve(url) {
    const fd = new FormData()
    fd.append('_method', 'PATCH')
    fd.append('_token', document.querySelector('meta[name=csrf-token]')?.content)
    fd.append('decision', 'approved')
    await fetch(url, { method: 'POST', body: fd })
    router.reload({ only: ['expenses'] })
}

async function reject(url, id) {
    const note = rejectNotes.value[id]
    if (!note?.trim()) return
    const fd = new FormData()
    fd.append('_method', 'PATCH')
    fd.append('_token', document.querySelector('meta[name=csrf-token]')?.content)
    fd.append('decision', 'rejected')
    fd.append('note', note.trim())
    await fetch(url, { method: 'POST', body: fd })
    rejectOpen.value[id] = false
    router.reload({ only: ['expenses'] })
}

const tabs = [
    { val: 'pending', label: 'Pending' },
    { val: 'approved', label: 'Approved' },
    { val: 'rejected', label: 'Rejected' },
    { val: 'all', label: 'All' },
]
</script>

<template>
    <AppLayout title="Expense Approvals">
        <div>
            <!-- Header -->
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                        <Link :href="index_url" class="hover:text-violet-600 transition-colors flex items-center gap-1">
                            <i class="bi bi-arrow-left text-[10px]"></i> My Expenses
                        </Link>
                    </div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <i class="bi bi-inbox text-emerald-600 dark:text-emerald-400"></i>
                        </span>
                        Expense Approvals
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Review and approve your team's expense submissions.</p>
                </div>
            </div>

            <!-- Status filter tabs -->
            <div class="flex items-center gap-1 mb-5 border-b border-slate-200 dark:border-slate-700">
                <button v-for="tab in tabs" :key="tab.val"
                        @click="router.get(route('expenses.approvals', { status: tab.val }))"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px"
                        :class="status === tab.val
                            ? 'border-violet-600 text-violet-600 dark:text-violet-400 dark:border-violet-400'
                            : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Expenses list -->
            <div class="card overflow-hidden">
                <template v-if="expenses.data?.length">
                    <div v-for="e in expenses.data" :key="e.id"
                         class="px-5 py-4 border-b border-slate-50 dark:border-slate-800/60 last:border-b-0">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                            <!-- Avatar -->
                            <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center font-bold text-sm text-violet-700 dark:text-violet-300 flex-shrink-0">
                                {{ (e.user?.name ?? '?').substring(0, 2).toUpperCase() }}
                            </div>

                            <!-- Main details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ e.title }}</p>
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">{{ e.category }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            by <span class="font-medium text-slate-700 dark:text-slate-200">{{ e.user?.name ?? '—' }}</span>
                                            · {{ e.expense_date }}
                                            · submitted {{ e.created_at }}
                                        </p>
                                        <p v-if="e.description" class="text-xs text-slate-400 mt-1 italic">"{{ e.description }}"</p>
                                        <p v-if="e.reviewer" class="text-xs text-slate-400 mt-1">
                                            {{ e.status?.charAt(0).toUpperCase() + e.status?.slice(1) }} by {{ e.reviewer.name }} {{ e.reviewed_at }}
                                            <template v-if="e.review_note"> — "{{ e.review_note }}"</template>
                                        </p>
                                    </div>

                                    <!-- Amount + status + receipt -->
                                    <div class="flex items-center gap-3 flex-shrink-0 self-start">
                                        <template v-if="e.receipt_urls?.length">
                                            <a v-for="(r, ri) in e.receipt_urls" :key="ri" :href="r.url" target="_blank"
                                               class="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-400 hover:underline">
                                                <i class="bi bi-paperclip text-xs"></i>
                                                {{ e.receipt_urls.length > 1 ? 'Receipt '+(ri+1) : 'Receipt' }}
                                            </a>
                                        </template>
                                        <p class="text-base font-bold text-slate-900 dark:text-white">
                                            {{ e.currency }} {{ Number(e.amount).toFixed(2) }}
                                        </p>
                                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium" :class="e.status_class">
                                            {{ e.status_label }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Approve / Reject actions (pending only) -->
                                <div v-if="e.status === 'pending'" class="mt-3">
                                    <div v-if="!rejectOpen[e.id]" class="flex items-center gap-2">
                                        <button @click="approve(e.review_url)" class="btn-primary btn-sm">
                                            <i class="bi bi-check-lg text-xs"></i> Approve
                                        </button>
                                        <button @click="openReject(e.id)"
                                                class="btn-ghost btn-sm text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                            <i class="bi bi-x-lg text-xs"></i> Reject
                                        </button>
                                    </div>
                                    <div v-else class="mt-2">
                                        <div class="flex flex-col gap-2">
                                            <textarea v-model="rejectNotes[e.id]" rows="2" required
                                                      class="form-input resize-none text-sm"
                                                      placeholder="Reason for rejection (required)…"></textarea>
                                            <div class="flex items-center gap-2">
                                                <button @click="reject(e.review_url, e.id)"
                                                        :disabled="!rejectNotes[e.id]?.trim()"
                                                        class="btn-sm text-white font-semibold px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                                                        style="background:#ef4444">
                                                    Confirm Reject
                                                </button>
                                                <button type="button" @click="cancelReject(e.id)" class="btn-ghost btn-sm">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-16 text-center">
                    <i class="bi bi-inbox text-4xl text-slate-200 dark:text-slate-700"></i>
                    <p class="mt-3 text-sm text-slate-400">
                        {{ status === 'pending' ? 'No pending expenses to review.' : `No ${status} expenses found.` }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="expenses.last_page > 1" class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2 flex-wrap">
                    <template v-for="link in expenses.links" :key="link.label">
                        <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300 dark:text-slate-600" v-html="link.label"></span>
                        <button v-else @click="router.get(link.url)"
                                class="px-2 py-1 text-sm rounded transition-colors"
                                :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                v-html="link.label"></button>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
