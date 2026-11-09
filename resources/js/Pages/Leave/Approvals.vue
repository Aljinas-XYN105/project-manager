<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    requests: Object,
    status: String,
    is_global_view: Boolean,
    calendar_url: String,
})

const declineOpen = ref({})
const declineNotes = ref({})

function openDecline(id) { declineOpen.value[id] = true; declineNotes.value[id] = '' }
function cancelDecline(id) { declineOpen.value[id] = false }

function approve(url) {
    router.patch(url, { decision: 'approved' }, { preserveScroll: true })
}

function decline(url, id) {
    const note = declineNotes.value[id]
    if (!note?.trim()) return
    router.patch(url, { decision: 'rejected', note: note.trim() }, {
        preserveScroll: true,
        onSuccess: () => { declineOpen.value[id] = false },
    })
}

const tabs = [
    { val: 'pending', label: 'Pending' },
    { val: 'approved', label: 'Approved' },
    { val: 'rejected', label: 'Declined' },
    { val: 'all', label: 'All' },
]

function typeColorDot(color) {
    const map = { blue: 'bg-blue-500', green: 'bg-green-500', amber: 'bg-amber-500', rose: 'bg-rose-500', violet: 'bg-violet-500', cyan: 'bg-cyan-500', orange: 'bg-orange-500', teal: 'bg-teal-500', indigo: 'bg-indigo-500', purple: 'bg-purple-500', pink: 'bg-pink-500', slate: 'bg-slate-500', red: 'bg-red-500', yellow: 'bg-yellow-500', lime: 'bg-lime-500', emerald: 'bg-emerald-500' }
    return map[color] ?? 'bg-slate-400'
}
</script>

<template>
    <AppLayout title="Leave Approvals">
        <div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">Leave approvals</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ is_global_view ? 'Requests from everyone.' : 'Requests from people who report to you.' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="calendar_url" class="btn-ghost">
                        <i class="bi bi-calendar3"></i> Team calendar
                    </Link>
                    <div class="tab-bar">
                        <button v-for="tab in tabs" :key="tab.val"
                                @click="router.get(route('leave.approvals', { status: tab.val }))"
                                class="tab-item" :class="status === tab.val ? 'active' : ''">
                            {{ tab.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <template v-if="requests.data?.length">
                    <div v-for="r in requests.data" :key="r.id"
                         class="card p-5 flex flex-col lg:flex-row lg:items-center gap-4">
                        <!-- User info -->
                        <div class="flex items-center gap-3 lg:w-64 flex-shrink-0">
                            <img :src="r.user.avatar_url" class="w-10 h-10 rounded-full" alt="">
                            <div class="min-w-0">
                                <a :href="r.user.show_url" class="font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">{{ r.user.name }}</a>
                                <p class="text-xs text-slate-500 truncate">{{ r.user.department || r.user.job_title }}</p>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-900 dark:text-white">
                                <span class="inline-block w-2 h-2 rounded-full mr-1" :class="typeColorDot(r.type.color)"></span>
                                <strong>{{ r.type.name }}</strong> · {{ r.days }} working {{ Number(r.days) === 1 ? 'day' : 'days' }}
                            </p>
                            <p class="text-xs text-slate-500">{{ r.date_range }} · requested {{ r.created_at }}</p>
                            <p v-if="r.reason" class="text-xs text-slate-600 dark:text-slate-400 mt-1">"{{ r.reason }}"</p>
                            <p v-if="r.overlaps?.length" class="text-xs text-amber-600 mt-1">
                                <i class="bi bi-people"></i> Also off then: {{ r.overlaps.join(', ') }}
                            </p>
                            <p v-if="r.reviewer" class="text-xs text-slate-400 mt-1">
                                {{ r.status_label }} by {{ r.reviewer.name }} {{ r.reviewed_at }}
                                <template v-if="r.review_note"> — "{{ r.review_note }}"</template>
                            </p>
                        </div>

                        <!-- Actions or status badge -->
                        <div v-if="r.can_review" class="flex-shrink-0">
                            <div v-if="!declineOpen[r.id]" class="flex gap-2">
                                <button @click="approve(r.review_url)" class="btn-primary btn-sm">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                                <button @click="openDecline(r.id)" class="btn-secondary btn-sm">Decline</button>
                            </div>
                            <div v-else class="flex gap-2">
                                <input type="text" v-model="declineNotes[r.id]" required maxlength="500"
                                       placeholder="Reason for declining"
                                       class="form-input text-sm py-1.5 w-56">
                                <button @click="decline(r.review_url, r.id)" :disabled="!declineNotes[r.id]?.trim()"
                                        class="btn-sm text-white font-semibold px-3 py-1.5 rounded-lg disabled:opacity-50"
                                        style="background:#ef4444">Decline</button>
                                <button @click="cancelDecline(r.id)" class="btn-ghost btn-sm">✕</button>
                            </div>
                        </div>
                        <span v-else class="text-[11px] px-2 py-0.5 rounded-full font-medium self-start lg:self-center" :class="r.status_class">
                            {{ r.status_label }}
                        </span>
                    </div>
                </template>

                <div v-else class="card empty-state py-16">
                    <i class="bi bi-inbox text-4xl text-slate-300 dark:text-slate-600"></i>
                    <p class="text-sm text-slate-500 mt-3">{{ status === 'pending' ? 'No requests waiting for you.' : 'Nothing here.' }}</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="requests.last_page > 1" class="mt-4 flex items-center gap-2 flex-wrap">
                <template v-for="link in requests.links" :key="link.label">
                    <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300 dark:text-slate-600" v-html="link.label"></span>
                    <button v-else @click="router.get(link.url)"
                            class="px-2 py-1 text-sm rounded transition-colors"
                            :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                            v-html="link.label"></button>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
