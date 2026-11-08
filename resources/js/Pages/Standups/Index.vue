<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    date: String,
    date_label: String,
    is_today: Boolean,
    users: Array,
    can_export: Boolean,
    export_excel_url: String,
    export_pdf_url: String,
    my_standup_url: String,
    calendar_url: String,
})

const selectedUser = ref(null)

function changeDate(d) {
    router.get(route('standups.index'), { date: d }, { preserveState: false })
}

const submittedCount = props.users.filter(u => u.has_standup).length
</script>

<template>
    <AppLayout :title="`Standups — ${date_label}`">
        <div class="page-header">
            <div>
                <h1 class="page-title flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                        <i class="bi bi-mic text-violet-600 dark:text-violet-400"></i>
                    </span>
                    Standups
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ submittedCount }} / {{ users.length }} submitted today.</p>
            </div>
            <div class="flex items-center gap-3">
                <input type="date" :value="date" @change="changeDate($event.target.value)"
                       class="form-input py-1.5 text-sm w-44">
                <Link v-if="!is_today" :href="route('standups.index')" class="btn-ghost btn-sm text-violet-600">Today</Link>
                <template v-if="can_export">
                    <a :href="export_excel_url" class="btn-secondary btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    <a :href="export_pdf_url" class="btn-secondary btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </template>
                <Link :href="my_standup_url" class="btn-primary"><i class="bi bi-pencil-square text-sm"></i> My Standup</Link>
            </div>
        </div>

        <!-- User grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <button v-for="user in users" :key="user.id"
                    @click="selectedUser = selectedUser?.id === user.id ? null : user"
                    class="card p-4 text-center transition-shadow hover:shadow-card-md"
                    :class="[
                        user.has_standup ? '' : 'opacity-60',
                        selectedUser?.id === user.id ? 'ring-2 ring-violet-500' : '',
                    ]">
                <div class="relative inline-block mb-2">
                    <img :src="user.avatar_url" class="w-12 h-12 rounded-full mx-auto" alt="">
                    <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center"
                          :class="user.has_standup ? 'bg-emerald-400' : 'bg-slate-300'">
                    </span>
                </div>
                <p class="text-xs font-medium text-slate-900 dark:text-white truncate">{{ user.name }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ user.has_standup ? 'Submitted' : 'Pending' }}</p>
            </button>
        </div>

        <!-- Selected user standup detail -->
        <div v-if="selectedUser?.standup" class="mt-6 card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                <img :src="selectedUser.avatar_url" class="w-7 h-7 rounded-full" alt="">
                <h2 class="font-semibold text-slate-900 dark:text-white">{{ selectedUser.name }}'s standup</h2>
            </div>
            <div class="p-5 space-y-4">
                <div v-if="selectedUser.standup.items?.length">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Tasks</h3>
                    <div class="space-y-1.5">
                        <div v-for="item in selectedUser.standup.items" :key="item.id"
                             class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <i :class="item.type === 'done' ? 'bi bi-check-circle-fill text-emerald-500' : item.type === 'today' ? 'bi bi-arrow-right-circle-fill text-blue-500' : 'bi bi-exclamation-circle-fill text-amber-500'"></i>
                            <span>{{ item.description }}</span>
                            <span v-if="item.hours" class="text-xs text-slate-400 ml-auto">{{ item.hours }}h</span>
                        </div>
                    </div>
                </div>
                <div v-if="selectedUser.standup.blockers">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-500 mb-1">Blockers</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ selectedUser.standup.blockers }}</p>
                </div>
                <div v-if="selectedUser.standup.summary">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Summary</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ selectedUser.standup.summary }}</p>
                </div>
            </div>
        </div>
        <div v-else-if="selectedUser && !selectedUser.has_standup" class="mt-6 card p-8 text-center">
            <i class="bi bi-journal-x text-3xl text-slate-300 dark:text-slate-600 block mb-2"></i>
            <p class="text-sm text-slate-500">{{ selectedUser.name }} hasn't submitted a standup for today.</p>
        </div>
    </AppLayout>
</template>
