<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    project: Object,
    tasks: Array,
    year: Number,
    month: Number,
})

const monthName = computed(() => {
    return new Date(props.year, props.month - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })
})

const calendarDays = computed(() => {
    const first = new Date(props.year, props.month - 1, 1)
    const last  = new Date(props.year, props.month, 0)
    // Start from Sunday before the first day
    const start = new Date(first)
    start.setDate(start.getDate() - start.getDay())
    // End on Saturday after the last day
    const end = new Date(last)
    end.setDate(end.getDate() + (6 - end.getDay()))

    const days = []
    const cur = new Date(start)
    while (cur <= end) {
        days.push({
            date: new Date(cur),
            dateStr: cur.toISOString().split('T')[0],
            day: cur.getDate(),
            isToday: cur.toDateString() === new Date().toDateString(),
            isCurrentMonth: cur.getMonth() === props.month - 1,
        })
        cur.setDate(cur.getDate() + 1)
    }
    return days
})

const tasksByDate = computed(() => {
    const map = {}
    for (const t of props.tasks) {
        if (!map[t.due_date]) map[t.due_date] = []
        map[t.due_date].push(t)
    }
    return map
})

function prevMonth() {
    let m = props.month - 1, y = props.year
    if (m < 1) { m = 12; y-- }
    router.get(props.project.calendar_url, { year: y, month: m }, { preserveState: false })
}
function nextMonth() {
    let m = props.month + 1, y = props.year
    if (m > 12) { m = 1; y++ }
    router.get(props.project.calendar_url, { year: y, month: m }, { preserveState: false })
}
function goToday() {
    router.get(props.project.calendar_url, {}, { preserveState: false })
}
</script>

<template>
    <AppLayout :title="project.name + ' — Calendar'">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <Link :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</Link>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Calendar</span>
                </div>
                <h1 class="page-title">{{ monthName }}</h1>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button @click="prevMonth" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></button>
                <button @click="goToday" class="btn-secondary btn-sm">Today</button>
                <button @click="nextMonth" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></button>
                <div class="tab-bar ml-1">
                    <Link :href="project.index_url" class="tab-item text-xs px-2.5 py-1.5"><i class="bi bi-list-task"></i> <span class="hidden sm:inline">List</span></Link>
                    <Link :href="route('projects.tasks.kanban', project.id)" class="tab-item text-xs px-2.5 py-1.5"><i class="bi bi-kanban"></i> <span class="hidden sm:inline">Board</span></Link>
                    <Link :href="project.calendar_url" class="tab-item active text-xs px-2.5 py-1.5"><i class="bi bi-calendar3"></i> <span class="hidden sm:inline">Calendar</span></Link>
                    <Link :href="project.backlog_url" class="tab-item text-xs px-2.5 py-1.5"><i class="bi bi-inbox"></i> <span class="hidden sm:inline">Backlog</span></Link>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <!-- Day headers -->
            <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-700">
                <div v-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="d"
                     class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                    {{ d }}
                </div>
            </div>
            <!-- Calendar grid -->
            <div class="grid grid-cols-7">
                <div v-for="(day, i) in calendarDays" :key="day.dateStr"
                     class="min-h-[110px] border-b border-r border-slate-100 dark:border-slate-700/50 p-2 col-span-1"
                     :class="[
                         !day.isCurrentMonth ? 'bg-slate-50/50 dark:bg-slate-800/20' : '',
                         (i + 1) % 7 === 0 ? 'border-r-0' : '',
                     ]">
                    <div class="flex items-center justify-center w-7 h-7 rounded-full mb-1"
                         :class="day.isToday ? 'bg-violet-600 text-white font-bold' : 'text-sm font-medium ' + (day.isCurrentMonth ? 'text-slate-900 dark:text-white' : 'text-slate-300 dark:text-slate-600')">
                        {{ day.day }}
                    </div>
                    <div class="space-y-1">
                        <a v-for="task in (tasksByDate[day.dateStr] ?? []).slice(0, 3)" :key="task.id"
                           :href="task.show_url"
                           class="block text-[11px] px-1.5 py-0.5 rounded text-white truncate hover:opacity-90 transition-opacity"
                           :style="{ backgroundColor: project.color || '#3B82F6' }"
                           :title="task.title">
                            {{ task.title }}
                        </a>
                        <span v-if="(tasksByDate[day.dateStr] ?? []).length > 3" class="text-[11px] text-slate-400 pl-1.5">
                            +{{ (tasksByDate[day.dateStr] ?? []).length - 3 }} more
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
