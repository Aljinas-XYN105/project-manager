<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    year: Number,
    month: Number,
    month_label: String,
    cal_days: Array,
    return_date: String,
    index_url: String,
    prev_url: String,
    next_url: String,
})

const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
</script>

<template>
    <AppLayout title="Standup Calendar">
        <div class="max-w-3xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-calendar3 text-violet-600 dark:text-violet-400"></i>
                        </span>
                        Standup Calendar
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">{{ month_label }}</p>
                </div>
                <Link :href="index_url" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back to Standups
                </Link>
            </div>

            <!-- Month nav -->
            <div class="flex items-center justify-between mb-5">
                <Link :href="prev_url" class="btn-ghost btn-sm">
                    <i class="bi bi-chevron-left text-xs"></i> Prev
                </Link>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ month_label }}</h2>
                <Link :href="next_url" class="btn-ghost btn-sm">
                    Next <i class="bi bi-chevron-right text-xs"></i>
                </Link>
            </div>

            <!-- Calendar grid -->
            <div class="card overflow-hidden">
                <!-- Day headers -->
                <div class="grid grid-cols-7 border-b border-slate-100 dark:border-slate-800">
                    <div v-for="d in days" :key="d"
                         class="py-2 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide">
                        {{ d }}
                    </div>
                </div>
                <!-- Days -->
                <div class="grid grid-cols-7">
                    <div v-for="(day, i) in cal_days" :key="i"
                         class="min-h-[80px] p-2 border-b border-r border-slate-50 dark:border-slate-800/60 relative"
                         :class="[
                             !day.is_current_month ? 'bg-slate-50/50 dark:bg-slate-800/20' : '',
                             (i + 1) % 7 === 0 ? 'border-r-0' : '',
                         ]">
                        <!-- Day number -->
                        <div class="flex items-center justify-center w-7 h-7 rounded-full mb-1"
                             :class="day.is_today
                                 ? 'bg-violet-600 text-white font-bold text-sm'
                                 : 'text-sm font-medium ' + (day.is_current_month ? 'text-slate-700 dark:text-slate-200' : 'text-slate-300 dark:text-slate-600')">
                            {{ day.day }}
                        </div>
                        <!-- Submitters -->
                        <div v-if="day.submitters?.length" class="space-y-0.5">
                            <div v-for="(name, si) in day.submitters.slice(0, 3)" :key="si"
                                 class="text-[10px] truncate px-1 py-0.5 rounded bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                                {{ name }}
                            </div>
                            <div v-if="day.submitters.length > 3"
                                 class="text-[10px] text-slate-400 px-1">
                                +{{ day.submitters.length - 3 }} more
                            </div>
                        </div>
                        <!-- Clickable overlay to go to that date's standups -->
                        <Link :href="`${index_url}?date=${day.date}`"
                              class="absolute inset-0 opacity-0 hover:opacity-100 hover:bg-violet-50/30 dark:hover:bg-violet-900/10 transition-opacity rounded"
                              v-if="day.is_current_month">
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
