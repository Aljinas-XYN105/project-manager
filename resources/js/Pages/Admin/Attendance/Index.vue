<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import SearchSelect from '@/Components/SearchSelect.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    records: Object,
    users: Array,
    board: Array,
    stats: Object,
    from: String,
    to: String,
    can_manage: Boolean,
    timezone: String,
    today: String,
    store_url: String,
    clock_out_all_url: String,
    summary_url: String,
    export_url: String,
})

const dateFrom = ref(props.from)
const dateTo = ref(props.to)

function applyFilter() {
    router.get(route('admin.attendance.index'), { from: dateFrom.value, to: dateTo.value })
}

const modal = ref(false)
const modalMode = ref('create') // 'create' | 'edit'
const modalForm = ref({ user_id: '', work_date: props.today, clock_in: '09:00', clock_out: '', note: '' })
const modalUrl = ref('')
const modalErrors = ref({})

function openNew() {
    modalMode.value = 'create'
    modalForm.value = { user_id: '', work_date: props.today, clock_in: '09:00', clock_out: '', note: '' }
    modalUrl.value = props.store_url
    modalErrors.value = {}
    modal.value = true
}

function openEdit(record) {
    modalMode.value = 'edit'
    modalForm.value = {
        user_id: record.user_id,
        work_date: record.work_date,
        clock_in: record.clock_in || '',
        clock_out: record.clock_out || '',
        note: record.note || '',
    }
    modalUrl.value = record.edit_url
    modalErrors.value = {}
    modal.value = true
}

function submitModal() {
    const method = modalMode.value === 'create' ? 'post' : 'put'
    router[method](modalUrl.value, modalForm.value, {
        onSuccess: () => { modal.value = false },
        onError: (e) => { modalErrors.value = e },
        preserveScroll: true,
    })
}

function deleteRecord(url) {
    if (!confirm('Delete this attendance entry?')) return
    router.delete(url, { preserveScroll: true })
}

function clockOutAll() {
    const count = props.board.filter(r => r.clocked_in).length
    if (!confirm(`Clock out all ${count} clocked-in member(s)?`)) return
    router.post(props.clock_out_all_url, {}, { preserveScroll: true })
}

const kpiCards = [
    { key: 'online', label: 'Online now', icon: 'bi-broadcast', color: 'emerald' },
    { key: 'present', label: 'Present today', icon: 'bi-person-check', color: 'violet' },
    { key: 'absent', label: 'Absent (not on leave)', icon: 'bi-person-dash', color: 'amber' },
    { key: 'total', label: 'Active members', icon: 'bi-people', color: 'blue' },
]
const colorBg = { emerald: 'bg-emerald-100 dark:bg-emerald-900/30', violet: 'bg-violet-100 dark:bg-violet-900/30', amber: 'bg-amber-100 dark:bg-amber-900/30', blue: 'bg-blue-100 dark:bg-blue-900/30' }
const colorText = { emerald: 'text-emerald-600 dark:text-emerald-400', violet: 'text-violet-600 dark:text-violet-400', amber: 'text-amber-600 dark:text-amber-400', blue: 'text-blue-600 dark:text-blue-400' }

const clockedInCount = () => props.board.filter(r => r.clocked_in).length
</script>

<template>
    <AppLayout title="Team Attendance">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                    <a :href="route('admin.dashboard')" class="hover:text-violet-600 transition-colors">Admin</a>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span>Attendance</span>
                </div>
                <h1 class="page-title">Team Attendance</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Who is available right now, and everyone's sign-in / sign-out times. Times shown in {{ timezone }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <a :href="route('admin.attendance.summary')" class="btn-secondary">
                    <i class="bi bi-graph-up"></i> Working Hours
                </a>
                <a :href="export_url + '?from=' + from + '&to=' + to" class="btn-secondary">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <button v-if="can_manage" @click="openNew" class="btn-primary"><i class="bi bi-plus-lg"></i> Add entry</button>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div v-for="card in kpiCards" :key="card.key" class="card p-4 flex items-center gap-3">
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', colorBg[card.color]]">
                    <i :class="['bi', card.icon, colorText[card.color]]"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ stats[card.key] }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <!-- Live board -->
        <div class="card overflow-hidden mb-6">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900 dark:text-white">Today</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400">Shown as offline after 5 minutes without activity</span>
                    <button v-if="clockedInCount() > 0 && can_manage" @click="clockOutAll"
                            class="btn-secondary btn-sm text-red-600 border-red-200 hover:bg-red-50">
                        <i class="bi bi-box-arrow-right"></i> Clock out all
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 divide-y sm:divide-y-0 divide-slate-50 dark:divide-slate-800">
                <div v-for="row in board" :key="row.user_id"
                     class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 dark:border-slate-800">
                    <div class="relative flex-shrink-0">
                        <UserAvatar :src="row.avatar_url" :name="row.name" size="lg" />
                        <span :class="['absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900', row.status_dot]"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <a :href="row.show_url" class="text-sm font-medium text-slate-900 dark:text-white hover:text-violet-600 truncate block">{{ row.user_name }}</a>
                        <p class="text-xs text-slate-500 truncate">
                            {{ row.leave_type ? 'On leave: ' + row.leave_type : row.status_label }}
                            <template v-if="row.availability_note && row.status !== 'offline'"> · {{ row.availability_note }}</template>
                            <template v-if="row.status === 'offline' && row.last_seen_at"> · seen {{ row.last_seen_at }}</template>
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-500 flex-shrink-0">
                        <p v-if="row.first_in">In: <span class="font-mono text-slate-700 dark:text-slate-300">{{ row.first_in }}</span></p>
                        <p v-if="row.last_out">Out: <span class="font-mono text-slate-700 dark:text-slate-300">{{ row.last_out }}</span></p>
                        <p v-if="row.minutes > 0" class="font-semibold text-slate-700 dark:text-slate-300">{{ row.duration }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date filter -->
        <div class="card p-4 mb-4">
            <form @submit.prevent="applyFilter" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="form-label">From</label>
                    <DatePicker v-model="dateFrom" />
                </div>
                <div>
                    <label class="form-label">To</label>
                    <DatePicker v-model="dateTo" />
                </div>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
        </div>

        <!-- Records table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                            <th class="px-5 py-3 font-medium text-slate-500">Date</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Member</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Clock In</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Clock Out</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Duration</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Source</th>
                            <th v-if="can_manage" class="px-5 py-3 font-medium text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <template v-if="records?.data?.length">
                            <tr v-for="r in records.data" :key="r.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ r.work_date }}</td>
                                <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">{{ r.user_name }}</td>
                                <td class="px-5 py-3 font-mono text-slate-700 dark:text-slate-300">{{ r.clock_in || '—' }}</td>
                                <td class="px-5 py-3 font-mono text-slate-700 dark:text-slate-300">{{ r.clock_out || '—' }}</td>
                                <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ r.duration }}</td>
                                <td class="px-5 py-3 text-xs text-slate-500 capitalize">{{ r.source }}</td>
                                <td v-if="can_manage" class="px-5 py-3 flex gap-1">
                                    <button @click="openEdit(r)" class="btn-ghost btn-xs"><i class="bi bi-pencil"></i></button>
                                    <button @click="deleteRecord(r.destroy_url)" class="btn-ghost btn-xs text-rose-500"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </template>
                        <tr v-else><td :colspan="can_manage ? 7 : 6" class="px-5 py-10 text-center text-slate-400">No attendance records.</td></tr>
                    </tbody>
                </table>
            </div>
            <div v-if="records?.last_page > 1" class="p-4 border-t flex gap-2 flex-wrap">
                <template v-for="link in records.links" :key="link.label">
                    <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300" v-html="link.label"></span>
                    <button v-else @click="router.get(link.url)" class="px-2 py-1 text-sm rounded"
                            :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label"></button>
                </template>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <h2 class="font-semibold text-slate-900 dark:text-white">{{ modalMode === 'create' ? 'Add attendance entry' : 'Edit attendance entry' }}</h2>
                    <div>
                        <label class="form-label">Member</label>
                        <SearchSelect v-model="modalForm.user_id"
                            :options="users.map(u => ({value: u.id, label: u.name, avatar: u.avatar_url}))"
                            placeholder="Select member…" />
                        <p v-if="modalErrors.user_id" class="form-error">{{ modalErrors.user_id }}</p>
                    </div>
                    <div>
                        <label class="form-label">Work date</label>
                        <DatePicker v-model="modalForm.work_date" />
                        <p v-if="modalErrors.work_date" class="form-error">{{ modalErrors.work_date }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Clock in</label>
                            <input v-model="modalForm.clock_in" type="time" class="form-input">
                            <p v-if="modalErrors.clock_in" class="form-error">{{ modalErrors.clock_in }}</p>
                        </div>
                        <div>
                            <label class="form-label">Clock out</label>
                            <input v-model="modalForm.clock_out" type="time" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Note</label>
                        <input v-model="modalForm.note" type="text" class="form-input">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="modal = false" class="btn-ghost">Cancel</button>
                        <button type="button" @click="submitModal" class="btn-primary">{{ modalMode === 'create' ? 'Add entry' : 'Save' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
