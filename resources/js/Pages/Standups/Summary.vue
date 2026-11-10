<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    preset: String,
    from: String,
    to: String,
    digest: Array,
    status_totals: Object,
    missing_users: Array,
    total_submitted: Number,
    total_members: Number,
    index_url: String,
})

const statusConfig = {
    todo:        { label: 'To Do',       dot: 'bg-slate-400',   bar: 'bg-slate-400' },
    in_progress: { label: 'In Progress', dot: 'bg-blue-500',    bar: 'bg-blue-500' },
    in_review:   { label: 'In Review',   dot: 'bg-amber-500',   bar: 'bg-amber-500' },
    qa_testing:  { label: 'QA Testing',  dot: 'bg-cyan-500',    bar: 'bg-cyan-500' },
    done:        { label: 'Done',         dot: 'bg-emerald-500', bar: 'bg-emerald-500' },
}

function statusDot(status) {
    return statusConfig[status]?.dot ?? 'bg-slate-400'
}

const presets = [
    { value: 'today',     label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'this_week', label: 'This Week' },
    { value: 'last_week', label: 'Last Week' },
]

function changePreset(p) {
    router.get(window.location.pathname, { preset: p }, { preserveScroll: true })
}

const totalItems = computed(() =>
    Object.values(props.status_totals).reduce((a, b) => a + b, 0)
)

const expanded = ref(new Set())
function toggle(idx) {
    if (expanded.value.has(idx)) expanded.value.delete(idx)
    else expanded.value.add(idx)
    expanded.value = new Set(expanded.value)
}
</script>

<template>
    <AppLayout title="Standup Summary">
        <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Team Summary</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ from === to ? from : `${from} – ${to}` }}
                    </p>
                </div>
                <Link :href="index_url"
                      class="btn-ghost btn-sm rounded-lg px-3 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                    ← Back
                </Link>
            </div>

            <!-- Preset tabs -->
            <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 rounded-xl p-1 w-fit">
                <button v-for="p in presets" :key="p.value"
                        @click="changePreset(p.value)"
                        :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition',
                                 preset === p.value
                                     ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
                                     : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200']">
                    {{ p.label }}
                </button>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ total_submitted }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Submitted</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ total_members - total_submitted }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Missing</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ status_totals.done }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Done</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalItems }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Total Items</p>
                </div>
            </div>

            <!-- Status breakdown bar -->
            <div v-if="totalItems > 0"
                 class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-4">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wide">Item Status Breakdown</p>
                <div class="flex rounded-full overflow-hidden h-3 gap-px">
                    <template v-for="(label, st) in {todo:'To Do',in_progress:'In Progress',in_review:'In Review',qa_testing:'QA',done:'Done'}" :key="st">
                        <div v-if="status_totals[st]"
                             :class="['h-full transition-all', statusConfig[st].bar]"
                             :style="{width: (status_totals[st] / totalItems * 100) + '%'}"
                             :title="`${label}: ${status_totals[st]}`">
                        </div>
                    </template>
                </div>
                <div class="flex flex-wrap gap-3 mt-3">
                    <div v-for="(label, st) in {todo:'To Do',in_progress:'In Progress',in_review:'In Review',qa_testing:'QA Testing',done:'Done'}" :key="st"
                         class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                        <span :class="['w-2 h-2 rounded-full', statusConfig[st].dot]"></span>
                        {{ label }}: {{ status_totals[st] }}
                    </div>
                </div>
            </div>

            <!-- Missing members -->
            <div v-if="missing_users.length"
                 class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/50 rounded-xl px-4 py-3">
                <p class="text-sm font-semibold text-rose-700 dark:text-rose-400 mb-1">
                    {{ missing_users.length }} member{{ missing_users.length !== 1 ? 's' : '' }} haven't submitted
                </p>
                <div class="flex flex-wrap gap-1.5 mt-1">
                    <span v-for="u in missing_users" :key="u.id"
                          class="inline-block text-xs bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 rounded-full px-2.5 py-0.5">
                        {{ u.name }}
                    </span>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!digest.length" class="text-center py-16 text-slate-400 dark:text-slate-500">
                <i class="bi bi-clipboard-data text-5xl mb-3 block opacity-50"></i>
                <p class="text-lg font-medium">No standups for this period</p>
            </div>

            <!-- Per-day digest -->
            <div v-else class="space-y-4">
                <div v-for="(day, idx) in digest" :key="day.date"
                     class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                    <!-- Day header -->
                    <button @click="toggle(idx)"
                            class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">{{ day.date_label }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">{{ day.entries.length }} member{{ day.entries.length !== 1 ? 's' : '' }}</span>
                            <i :class="['bi text-slate-400 text-sm', expanded.has(idx) ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
                        </div>
                    </button>

                    <!-- Member entries -->
                    <div v-if="!expanded.has(idx)" class="border-t border-slate-100 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700">
                        <div v-for="entry in day.entries" :key="entry.user_name" class="px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <img v-if="entry.avatar_url" :src="entry.avatar_url" :alt="entry.user_name"
                                         class="w-6 h-6 rounded-full object-cover">
                                    <div v-else class="w-6 h-6 rounded-full bg-violet-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ entry.user_name.substring(0, 1) }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ entry.user_name }}</span>
                                    <span v-if="entry.blockers" class="text-xs text-rose-500 font-medium">⚡ Blocker</span>
                                </div>
                                <Link :href="entry.member_day_url" class="text-xs text-violet-500 hover:underline">View →</Link>
                            </div>
                            <div class="space-y-1 ml-8">
                                <div v-for="item in entry.items" :key="item.description" class="flex items-start gap-2 text-xs">
                                    <span :class="['mt-1 w-1.5 h-1.5 rounded-full flex-shrink-0', statusDot(item.status)]"></span>
                                    <span class="text-slate-600 dark:text-slate-300 leading-snug">{{ item.description }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
