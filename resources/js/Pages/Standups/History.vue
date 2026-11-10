<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    standups: Object,
    index_url: String,
    my_today_url: String,
})

const statusConfig = {
    todo:        { label: 'To Do',       dot: 'bg-slate-400' },
    in_progress: { label: 'In Progress', dot: 'bg-blue-500' },
    in_review:   { label: 'In Review',   dot: 'bg-amber-500' },
    qa_testing:  { label: 'QA Testing',  dot: 'bg-cyan-500' },
    done:        { label: 'Done',         dot: 'bg-emerald-500' },
}

function statusDot(status) {
    return statusConfig[status]?.dot ?? 'bg-slate-400'
}

function statusLabel(status) {
    return statusConfig[status]?.label ?? status
}

function goPage(page) {
    router.get(props.index_url.replace('/standups', '/standups/my/history'), { page }, { preserveScroll: true })
}

const expanded = ref(new Set())
function toggle(id) {
    if (expanded.value.has(id)) expanded.value.delete(id)
    else expanded.value.add(id)
    expanded.value = new Set(expanded.value)
}
</script>

<template>
    <AppLayout title="My Standup History">
        <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My Standup History</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All your past standups</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="my_today_url"
                          class="btn-sm bg-violet-600 hover:bg-violet-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition">
                        Today's Standup
                    </Link>
                    <Link :href="index_url"
                          class="btn-ghost btn-sm rounded-lg px-3 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        ← Back
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!standups.data.length" class="text-center py-16 text-slate-400 dark:text-slate-500">
                <i class="bi bi-journal-text text-5xl mb-3 block opacity-50"></i>
                <p class="text-lg font-medium">No standups yet</p>
                <p class="text-sm mt-1">Your submitted standups will appear here.</p>
            </div>

            <!-- Standup list -->
            <div v-else class="space-y-3">
                <div v-for="s in standups.data" :key="s.id"
                     class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                    <!-- Row header -->
                    <button @click="toggle(s.id)"
                            class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <div>
                            <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">{{ s.date_label }}</span>
                            <span class="ml-3 text-xs text-slate-500 dark:text-slate-400">{{ s.items.length }} item{{ s.items.length !== 1 ? 's' : '' }}</span>
                            <span v-if="s.blockers" class="ml-2 text-xs text-rose-500 font-medium">⚡ Blocker</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Status pill counts -->
                            <div class="hidden sm:flex items-center gap-1.5">
                                <template v-for="st in ['todo','in_progress','in_review','qa_testing','done']" :key="st">
                                    <span v-if="s.items.filter(i => i.status === st).length"
                                          class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        <span :class="['w-1.5 h-1.5 rounded-full', statusDot(st)]"></span>
                                        {{ s.items.filter(i => i.status === st).length }}
                                    </span>
                                </template>
                            </div>
                            <i :class="['bi text-slate-400 text-sm transition-transform', expanded.has(s.id) ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
                        </div>
                    </button>

                    <!-- Expanded items -->
                    <div v-if="expanded.has(s.id)" class="border-t border-slate-100 dark:border-slate-700 px-4 py-3 space-y-2">
                        <div v-if="s.blockers" class="text-sm text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 rounded-lg px-3 py-2 mb-3">
                            <span class="font-medium">Blockers:</span> {{ s.blockers }}
                        </div>
                        <div v-for="item in s.items" :key="item.id" class="flex items-start gap-2.5 text-sm">
                            <span :class="['mt-1.5 w-2 h-2 rounded-full flex-shrink-0', statusDot(item.status)]"></span>
                            <span class="text-slate-700 dark:text-slate-300 leading-snug">{{ item.description }}</span>
                            <span class="ml-auto text-xs text-slate-400 whitespace-nowrap">{{ statusLabel(item.status) }}</span>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <Link :href="s.show_url" class="text-xs text-violet-600 dark:text-violet-400 hover:underline">
                                Edit this standup →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="standups.last_page > 1" class="flex items-center justify-between pt-2">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    Page {{ standups.current_page }} of {{ standups.last_page }}
                    <span class="ml-1 text-slate-400">({{ standups.total }} total)</span>
                </span>
                <div class="flex gap-2">
                    <button :disabled="standups.current_page <= 1"
                            @click="goPage(standups.current_page - 1)"
                            class="btn-ghost btn-sm rounded-lg px-3 py-1.5 text-sm disabled:opacity-40">
                        ← Prev
                    </button>
                    <button :disabled="standups.current_page >= standups.last_page"
                            @click="goPage(standups.current_page + 1)"
                            class="btn-ghost btn-sm rounded-lg px-3 py-1.5 text-sm disabled:opacity-40">
                        Next →
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
