<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    daily_report: Object,
    my_tasks: Array,
    existing_tasks: Array,
    projects: Array,
})

let uid = 0
const blankNewTask = () => ({ project_id: '', title: '', type: 'task', priority: 'medium' })
const normalize = (t) => ({
    _id: ++uid,
    _descTouched: !!t.description,
    task_id: t.task_id ? String(t.task_id) : '',
    task_title: t.task_title ?? null,
    description: t.description ?? '',
    hours: parseFloat(t.hours) || 0,
    notes: t.notes ?? '',
    status_update: t.status_update ?? '',
    is_blocked: !!t.is_blocked,
    from_timer: false,
    new_task: blankNewTask(),
})

const summary = ref(props.daily_report.summary ?? '')
const planForTomorrow = ref(props.daily_report.plan_for_tomorrow ?? '')
const blockers = ref(props.daily_report.blockers ?? '')
const tasks = ref((props.existing_tasks ?? []).map(normalize))
const processing = ref(false)

const totalHours = computed(() => tasks.value.reduce((s, t) => s + (parseFloat(t.hours) || 0), 0))

function optionsFor(row) {
    const opts = (props.my_tasks ?? []).slice()
    if (row.task_id && row.task_id !== '__new__' && !opts.some(o => String(o.id) === row.task_id)) {
        opts.unshift({ id: row.task_id, label: row.task_title || ('Task #' + row.task_id) })
    }
    return opts
}

function onTaskPicked(row) {
    if (row.task_id === '__new__') {
        if (!row.new_task.title && row.description) row.new_task.title = row.description
        return
    }
    const found = (props.my_tasks ?? []).find(t => String(t.id) === row.task_id)
    if (found && !row._descTouched) row.description = found.title
}

function addTask() { tasks.value.push(normalize({})) }
function removeTask(idx) { tasks.value.splice(idx, 1) }

const statusOpts = [
    { val: '', label: 'No change' },
    { val: 'in_progress', label: 'In Progress' },
    { val: 'qa_testing', label: 'QA Testing' },
    { val: 'in_review', label: 'In Review' },
    { val: 'done', label: 'Done' },
    { val: 'blocked', label: 'Blocked' },
    { val: 'todo', label: 'Back to To Do' },
]
const typeOpts = [
    { val: 'task', label: 'Task' }, { val: 'bug', label: 'Bug' },
    { val: 'feature', label: 'Feature' }, { val: 'story', label: 'Story' },
]
const priorityOpts = [
    { val: 'low', label: 'Low' }, { val: 'medium', label: 'Medium' },
    { val: 'high', label: 'High' }, { val: 'critical', label: 'Critical' },
]

async function submit() {
    processing.value = true
    const csrfToken = document.querySelector('meta[name=csrf-token]')?.content
    try {
        const res = await fetch(props.daily_report.update_url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                summary: summary.value,
                plan_for_tomorrow: planForTomorrow.value || null,
                blockers: blockers.value || null,
                tasks: tasks.value.map(t => ({
                    task_id: t.task_id === '__new__' ? null : (t.task_id || null),
                    description: t.description,
                    hours: t.hours,
                    notes: t.notes || null,
                    status_update: t.status_update || null,
                    is_blocked: t.is_blocked ? 1 : 0,
                    new_task: t.task_id === '__new__' ? t.new_task : undefined,
                })),
            }),
        })
        if (res.ok || res.redirected) {
            window.location.href = props.daily_report.show_url
        }
    } finally {
        processing.value = false
    }
}

function deleteReport() {
    if (!confirm('Delete this report?')) return
    router.delete(props.daily_report.destroy_url)
}
</script>

<template>
    <AppLayout :title="`Edit Report — ${daily_report.report_date}`">
        <div class="max-w-3xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="bi bi-journal-check text-amber-600 dark:text-amber-400"></i>
                        </span>
                        Edit Daily Report
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">{{ daily_report.report_date }}</p>
                </div>
                <Link :href="daily_report.show_url" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Summary -->
                <div class="card p-5">
                    <label class="form-label">What did you accomplish? *</label>
                    <textarea v-model="summary" rows="4" required
                              class="form-input resize-none"
                              placeholder="Describe your accomplishments, meetings, reviews…"></textarea>
                </div>

                <!-- Task Breakdown -->
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <label class="form-label mb-0">Task Breakdown</label>
                            <p class="text-xs text-slate-400 mt-0.5">Log hours per task.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">Total: {{ totalHours.toFixed(1) }}h</span>
                            <button type="button" @click="addTask()" class="btn-primary btn-sm">
                                <i class="bi bi-plus-lg"></i> Add Row
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(task, idx) in tasks" :key="task._id"
                             class="border rounded-xl p-4 space-y-3 transition-colors"
                             :class="task.task_id === '__new__'
                                ? 'border-violet-300 dark:border-violet-700 bg-violet-50/40 dark:bg-violet-900/10'
                                : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30'">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 min-w-0 space-y-3">
                                    <div>
                                        <label class="form-label">Task</label>
                                        <select v-model="task.task_id" @change="onTaskPicked(task)" class="form-input text-sm">
                                            <option value="">— Not linked to a task —</option>
                                            <option value="__new__">＋ Create a new task…</option>
                                            <option v-for="opt in optionsFor(task)" :key="opt.id" :value="String(opt.id)">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div v-if="task.task_id === '__new__'"
                                         class="rounded-xl border border-violet-200 dark:border-violet-800 bg-white dark:bg-slate-900 p-3.5 space-y-3">
                                        <p class="text-xs font-semibold text-violet-700 dark:text-violet-300 flex items-center gap-1.5">
                                            <i class="bi bi-plus-circle-fill"></i> New task — created on submit
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="sm:col-span-2">
                                                <label class="form-label">Task title *</label>
                                                <input type="text" v-model="task.new_task.title" required maxlength="255"
                                                       class="form-input text-sm" placeholder="Task title…">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="form-label">Project *</label>
                                                <select v-model="task.new_task.project_id" class="form-input text-sm">
                                                    <option value="">Select a project…</option>
                                                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Type</label>
                                                <select v-model="task.new_task.type" class="form-input text-sm">
                                                    <option v-for="t in typeOpts" :key="t.val" :value="t.val">{{ t.label }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Priority</label>
                                                <select v-model="task.new_task.priority" class="form-input text-sm">
                                                    <option v-for="p in priorityOpts" :key="p.val" :value="p.val">{{ p.label }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label">What was done? *</label>
                                        <input type="text" v-model="task.description"
                                               @input="task._descTouched = true"
                                               required class="form-input text-sm"
                                               placeholder="Brief description…">
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div>
                                            <label class="form-label">Hours *</label>
                                            <input type="number" v-model.number="task.hours"
                                                   step="0.05" min="0.05" max="24" required
                                                   class="form-input text-sm text-center">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="form-label">Status After Today</label>
                                            <select v-model="task.status_update" class="form-input text-sm">
                                                <option v-for="o in statusOpts" :key="o.val" :value="o.val">{{ o.label }}</option>
                                            </select>
                                        </div>
                                        <div class="flex items-end">
                                            <label class="flex items-center gap-2 cursor-pointer py-2">
                                                <input type="checkbox" v-model="task.is_blocked"
                                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                                <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">
                                                    <i class="bi bi-exclamation-octagon text-rose-500 mr-0.5"></i> Blocked
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Notes (optional)</label>
                                        <input type="text" v-model="task.notes"
                                               class="form-input text-sm" placeholder="Any blockers, links, or context…">
                                    </div>
                                </div>
                                <button type="button" @click="removeTask(idx)"
                                        class="flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors mt-6">
                                    <i class="bi bi-trash3 text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" @click="addTask()"
                                class="w-full py-3 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700
                                       text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300
                                       hover:border-violet-300 dark:hover:border-violet-700 hover:bg-violet-50 dark:hover:bg-violet-900/10
                                       transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-plus-lg"></i> Add another task
                        </button>
                    </div>
                </div>

                <!-- Plan & Blockers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="card p-5">
                        <label class="form-label">Plan for Tomorrow</label>
                        <textarea v-model="planForTomorrow" rows="4" class="form-input resize-none"
                                  placeholder="What will you work on tomorrow?"></textarea>
                    </div>
                    <div class="card p-5">
                        <label class="form-label flex items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500"></i>
                            Blockers / Impediments
                        </label>
                        <textarea v-model="blockers" rows="4"
                                  class="form-input resize-none border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-400/20"
                                  placeholder="Anything blocking you?"></textarea>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <button type="button" @click="deleteReport"
                            class="btn-ghost text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                        <i class="bi bi-trash3"></i> Delete Report
                    </button>
                    <div class="flex gap-3">
                        <Link :href="daily_report.show_url" class="btn-ghost">Cancel</Link>
                        <button type="submit" :disabled="processing" class="btn-primary">
                            <i class="bi" :class="processing ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'"></i>
                            {{ processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
