<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    date: String,
    date_label: String,
    full_date_label: String,
    is_today: Boolean,
    users: Array,
    can_export: Boolean,
    export_excel_url: String,
    export_pdf_url: String,
    export_excel_range_url: String,
    export_pdf_range_url: String,
    my_standup_url: String,
    calendar_url: String,
    summary_url: String,
})

const exportOpen = ref(false)

function rangeUrl(base, preset) {
    return base + '?preset=' + preset
}

const avatarBgs   = ['#ede9fe','#dbeafe','#d1fae5','#fef3c7','#fee2e2','#e0e7ff','#ccfbf1','#fce7f3','#ddd6fe','#bfdbfe']
const avatarTexts = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#be185d','#6d28d9','#1d4ed8']

function avatarBg(user)   { return avatarBgs[user.id % avatarBgs.length] }
function avatarText(user) { return avatarTexts[user.id % avatarTexts.length] }
function initials(name)   { return name.substring(0, 2).toUpperCase() }

function cardShadow(user) {
    if (user.has_blockers)   return '0 3px 12px 0 rgba(239,68,68,0.28), 0 1px 4px 0 rgba(239,68,68,0.12)'
    if (user.has_standup)    return '0 3px 12px 0 rgba(34,197,94,0.22), 0 1px 4px 0 rgba(34,197,94,0.08)'
    return '0 3px 12px 0 rgba(251,146,60,0.18), 0 1px 4px 0 rgba(251,146,60,0.06)'
}

function cardBorderClass(user) {
    if (user.has_blockers)   return 'border-rose-200 dark:border-rose-800/50'
    if (user.has_standup)    return 'border-green-200 dark:border-green-800/40'
    return 'border-amber-200 dark:border-amber-700/40'
}

function dividerClass(user) {
    if (user.has_blockers)   return 'border-rose-100 dark:border-rose-900/30'
    if (user.has_standup)    return 'border-green-100 dark:border-green-900/20'
    return 'border-amber-100 dark:border-amber-900/20'
}

function roleClass(role) {
    if (role === 'admin')   return 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300'
    if (role === 'manager') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
    return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
}

function formatRole(role) {
    return role.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

const submittedCount = computed(() => props.users.filter(u => u.has_standup).length)
const pendingCount   = computed(() => props.users.length - submittedCount.value)
</script>

<template>
    <AppLayout :title="`Standups — ${date_label}`">
        <div class="relative">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-mic text-violet-600 dark:text-violet-400"></i>
                        </span>
                        Standups
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">Click a member to review their tasks during standup.</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Date chip -->
                    <a :href="`${calendar_url}?return_date=${date}`"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700
                              bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200
                              hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
                        <i class="bi bi-calendar3 text-violet-500 text-sm"></i>
                        {{ date_label }}
                    </a>

                    <Link v-if="!is_today" :href="route('standups.index')"
                          class="btn-ghost btn-sm text-violet-600 dark:text-violet-400">
                        Today
                    </Link>

                    <!-- Summary button -->
                    <Link v-if="can_export && summary_url" :href="summary_url"
                          class="btn-ghost btn-sm flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <i class="bi bi-bar-chart-line text-sm"></i>
                        Summary
                    </Link>

                    <!-- Export dropdown -->
                    <div v-if="can_export" class="relative">
                        <button @click="exportOpen = !exportOpen"
                                @blur="setTimeout(() => exportOpen = false, 150)"
                                class="btn-secondary flex items-center gap-2">
                            <i class="bi bi-download"></i>
                            Export
                            <i class="bi bi-chevron-down text-xs opacity-60"></i>
                        </button>

                        <div v-if="exportOpen"
                             class="absolute right-0 top-full mt-1 z-30 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg py-1 min-w-[220px]">

                            <!-- Today's date -->
                            <div class="px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                {{ date_label }}
                            </div>
                            <a :href="export_excel_url"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200">
                                <i class="bi bi-file-earmark-excel text-green-600 w-4 text-center"></i> Excel
                            </a>
                            <a :href="export_pdf_url"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200">
                                <i class="bi bi-file-earmark-pdf text-red-500 w-4 text-center"></i> PDF
                            </a>

                            <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>

                            <!-- This week -->
                            <div class="px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">This week</div>
                            <a :href="rangeUrl(export_excel_range_url, 'this_week')"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200">
                                <i class="bi bi-file-earmark-excel text-green-600 w-4 text-center"></i> Excel
                            </a>
                            <a :href="rangeUrl(export_pdf_range_url, 'this_week')"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200">
                                <i class="bi bi-file-earmark-pdf text-red-500 w-4 text-center"></i> PDF
                            </a>

                            <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>

                            <!-- This month -->
                            <div class="px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">This month</div>
                            <a :href="rangeUrl(export_excel_range_url, 'this_month')"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200">
                                <i class="bi bi-file-earmark-excel text-green-600 w-4 text-center"></i> Excel
                            </a>
                            <a :href="rangeUrl(export_pdf_range_url, 'this_month')"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200">
                                <i class="bi bi-file-earmark-pdf text-red-500 w-4 text-center"></i> PDF
                            </a>
                        </div>
                    </div>

                    <Link :href="my_standup_url" class="btn-primary">
                        <i class="bi bi-pencil-square text-sm"></i> My Standup
                    </Link>
                </div>
            </div>

            <!-- Past-date notice -->
            <div v-if="!is_today"
                 class="mb-5 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800
                        text-amber-700 dark:text-amber-300 text-sm flex items-center gap-2">
                <i class="bi bi-clock-history flex-shrink-0"></i>
                Viewing <strong>{{ full_date_label }}</strong> — past standups are read-only
            </div>

            <!-- Stats row -->
            <div class="flex items-center gap-5 mb-6 text-sm">
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ users.length }} members</span>
                <span class="flex items-center gap-1.5 text-green-600 dark:text-green-400 font-medium">
                    <i class="bi bi-check-circle-fill text-xs"></i>
                    {{ submittedCount }} submitted
                </span>
                <span v-if="pendingCount > 0 && is_today" class="flex items-center gap-1.5 text-slate-400 font-medium">
                    <i class="bi bi-clock text-xs"></i>
                    {{ pendingCount }} pending
                </span>
            </div>

            <!-- Card grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <a v-for="user in users" :key="user.id"
                   :href="user.member_day_url"
                   :class="['group flex flex-col bg-white dark:bg-slate-800 rounded-xl border overflow-hidden transition-all duration-200 w-full hover:border-violet-300 dark:hover:border-violet-600', cardBorderClass(user)]"
                   :style="{ boxShadow: cardShadow(user) }">

                    <!-- Top section: avatar + name + status badge -->
                    <div class="flex items-center gap-3 px-4 pt-4 pb-3.5">
                        <!-- Avatar with initials -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                             :style="{ background: avatarBg(user), color: avatarText(user) }">
                            {{ initials(user.name) }}
                        </div>

                        <!-- Name + roles + subtitle -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-slate-900 dark:text-white text-sm leading-tight truncate">{{ user.name }}</span>
                                <span v-for="role in user.roles" :key="role"
                                      :class="['inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide flex-shrink-0', roleClass(role)]">
                                    {{ formatRole(role) }}
                                </span>
                            </div>
                            <p v-if="user.subtitle" class="text-xs text-slate-400 mt-0.5 truncate">{{ user.subtitle }}</p>
                        </div>

                        <!-- Status badge + chevron -->
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                            <span v-if="user.has_standup"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                         bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800/50">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                                Submitted
                            </span>
                            <span v-else
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold
                                         bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                Pending
                            </span>
                            <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 group-hover:text-violet-500 transition-colors text-xs"></i>
                        </div>
                    </div>

                    <!-- Counts bar -->
                    <div :class="['flex border-t px-3 py-2.5 gap-3', dividerClass(user)]">
                        <!-- To Do -->
                        <div class="flex items-center gap-1 flex-1 justify-center">
                            <i :class="['bi bi-circle-fill text-[11px]', user.todo_count > 0 ? 'text-slate-400' : 'text-slate-200 dark:text-slate-700']"></i>
                            <span :class="['text-[11px] font-bold tabular-nums leading-none', user.todo_count > 0 ? 'text-slate-600 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600']">
                                {{ user.todo_count }}
                            </span>
                        </div>
                        <!-- In Progress -->
                        <div class="flex items-center gap-1 flex-1 justify-center">
                            <i :class="['bi bi-arrow-repeat text-[11px]', user.in_prog_count > 0 ? 'text-blue-500' : 'text-slate-200 dark:text-slate-700']"></i>
                            <span :class="['text-[11px] font-bold tabular-nums leading-none', user.in_prog_count > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-300 dark:text-slate-600']">
                                {{ user.in_prog_count }}
                            </span>
                        </div>
                        <!-- QA Testing -->
                        <div class="flex items-center gap-1 flex-1 justify-center">
                            <i :class="['bi bi-bug-fill text-[11px]', user.qa_count > 0 ? 'text-cyan-500' : 'text-slate-200 dark:text-slate-700']"></i>
                            <span :class="['text-[11px] font-bold tabular-nums leading-none', user.qa_count > 0 ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-300 dark:text-slate-600']">
                                {{ user.qa_count }}
                            </span>
                        </div>
                        <!-- In Review -->
                        <div class="flex items-center gap-1 flex-1 justify-center">
                            <i :class="['bi bi-eye-fill text-[11px]', user.review_count > 0 ? 'text-amber-500' : 'text-slate-200 dark:text-slate-700']"></i>
                            <span :class="['text-[11px] font-bold tabular-nums leading-none', user.review_count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-300 dark:text-slate-600']">
                                {{ user.review_count }}
                            </span>
                        </div>
                        <!-- Done -->
                        <div class="flex items-center gap-1 flex-1 justify-center">
                            <i :class="['bi bi-check-circle-fill text-[11px]', user.done_count > 0 ? 'text-green-500' : 'text-slate-200 dark:text-slate-700']"></i>
                            <span :class="['text-[11px] font-bold tabular-nums leading-none', user.done_count > 0 ? 'text-green-600 dark:text-green-400' : 'text-slate-300 dark:text-slate-600']">
                                {{ user.done_count }}
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Empty state -->
                <div v-if="users.length === 0" class="col-span-4 text-center py-16 text-slate-400 text-sm">
                    No team members found.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
