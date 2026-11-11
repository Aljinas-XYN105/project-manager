<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProjectSelect from '@/Components/ProjectSelect.vue'
import TaskPicker from '@/Components/TaskPicker.vue'

const props = defineProps({
    my_tasks: Array,
    today: String,
    projects: Array,
    timed_task_rows: Array,
    store_url: String,
    index_url: String,
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
    from_timer: !!t.from_timer,
    new_task: Object.assign(blankNewTask(), t.new_task || {}),
})

const initialRows = (props.timed_task_rows && props.timed_task_rows.length)
    ? props.timed_task_rows.map(normalize)
    : [normalize({})]

const summary = ref('')
const planForTomorrow = ref('')
const blockers = ref('')
const tasks = ref(initialRows)
const processing = ref(false)
const errors = ref({})

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
    { val: 'task', label: 'Task' },
    { val: 'bug', label: 'Bug' },
    { val: 'feature', label: 'Feature' },
    { val: 'story', label: 'Story' },
]
const priorityOpts = [
    { val: 'low', label: 'Low' },
    { val: 'medium', label: 'Medium' },
    { val: 'high', label: 'High' },
    { val: 'critical', label: 'Critical' },
]

const openDropdowns = ref({})
function toggleDropdown(key) { openDropdowns.value[key] = !openDropdowns.value[key] }

async function submit() {
    processing.value = true
    errors.value = {}
    const csrfToken = document.querySelector('meta[name=csrf-token]')?.content
    try {
        const res = await fetch(props.store_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Inertia': 'true',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                report_date: props.today,
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
        if (res.redirected || res.ok) {
            window.location.href = props.index_url
        }
    } catch (e) {
        console.error(e)
    } finally {
        processing.value = false
    }
}
</script>

<template>
    <AppLayout title="Submit Daily Report">
        <div class="max-w-3xl mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('daily-reports.index')" class="hover:text-violet-600 transition-colors">Daily Reports</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>New</span>
                    </div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="bi bi-journal-plus text-amber-600 dark:text-amber-400"></i>
                        </span>
                        Daily Report
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                </div>
                <Link :href="index_url" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back
                </Link>
            </div>

            <!-- Timer pre-fill notice -->
            <div v-if="timed_task_rows?.length"
                 class="mb-4 flex items-start gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-800 dark:text-emerald-300">
                <i class="bi bi-stopwatch-fill mt-0.5 flex-shrink-0"></i>
                <div>
                    <span class="font-semibold">{{ timed_task_rows.length }} task{{ timed_task_rows.length > 1 ? 's' : '' }} pre-filled from your timer</span>
                    — hours and descriptions are auto-populated from today's tracked time. You can edit any field before submitting.
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Summary -->
                <div class="card p-5">
                    <label class="form-label">What did you accomplish today? *</label>
                    <textarea v-model="summary" rows="4" required
                              class="form-input resize-none"
                              placeholder="Describe your accomplishments, meetings, reviews…"></textarea>
                    <p v-if="errors.summary" class="mt-1 text-xs font-medium text-rose-500">{{ errors.summary }}</p>
                </div>

                <!-- Task Breakdown -->
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <label class="form-label mb-0">Task Breakdown</label>
                            <p class="text-xs text-slate-400 mt-0.5">Log hours per task.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                Total: {{ totalHours.toFixed(1) }}h
                            </span>
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
                                : (task.from_timer
                                    ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/30 dark:bg-emerald-900/10'
                                    : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30')">
                            <div v-if="task.from_timer"
                                 class="flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400 mb-1">
                                <i class="bi bi-stopwatch-fill"></i>
                                <span>Auto-filled from timer — feel free to edit</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-1 min-w-0 space-y-3">
                                    <!-- Task selector -->
                                    <div>
                                        <label class="form-label">Task</label>
                                        <TaskPicker
                                            :modelValue="task.task_id"
                                            :options="optionsFor(task)"
                                            @update:modelValue="val => { task.task_id = val; onTaskPicked(task) }" />
                                    </div>

                                    <!-- New task inline form -->
                                    <div v-if="task.task_id === '__new__'"
                                         class="rounded-xl border border-violet-200 dark:border-violet-800 bg-white dark:bg-slate-900 p-3.5 space-y-3">
                                        <p class="text-xs font-semibold text-violet-700 dark:text-violet-300 flex items-center gap-1.5">
                                            <i class="bi bi-plus-circle-fill"></i> New task — created and assigned to you when you submit
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="sm:col-span-2">
                                                <label class="form-label">Task title *</label>
                                                <input type="text" v-model="task.new_task.title"
                                                       @input="!task._descTouched && (task.description = task.new_task.title)"
                                                       required maxlength="255"
                                                       class="form-input text-sm"
                                                       placeholder="e.g. Fix checkout total rounding">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="form-label">Project *</label>
                                                <ProjectSelect v-model="task.new_task.project_id" :projects="projects" placeholder="Select a project…" size="sm" />
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
                                        <p v-if="!projects?.length" class="text-xs text-amber-600">
                                            You're not a member of any project yet — ask a manager to add you.
                                        </p>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label class="form-label">What was done? *</label>
                                        <input type="text" v-model="task.description"
                                               @input="task._descTouched = true"
                                               required class="form-input text-sm"
                                               placeholder="Brief description of what you worked on…">
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
                                        <label class="form-label">Notes / Context (optional)</label>
                                        <input type="text" v-model="task.notes"
                                               class="form-input text-sm" placeholder="Any blockers, links, or context…">
                                    </div>
                                </div>
                                <button type="button" @click="removeTask(idx)"
                                        class="flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors mt-6">
                                    <i class="bi bi-trash3 text-sm"></i>
                                </button>
                            </div>

                            <div v-if="task.is_blocked || task.status_update === 'blocked'"
                                 class="flex items-center gap-2 px-3 py-2 rounded-lg bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-400">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>This task is marked as blocked — describe the blocker in your notes or the Blockers box.</span>
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

                    <div v-if="tasks.length" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Total hours logged</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ totalHours.toFixed(1) }}h</span>
                        </div>
                        <div class="mt-2 h-2 bg-slate-100 dark:bg-slate-800 rounded-full">
                            <div class="h-2 rounded-full transition-all duration-500"
                                 :class="totalHours >= 8 ? 'bg-emerald-500' : (totalHours >= 4 ? 'bg-amber-500' : 'bg-rose-400')"
                                 :style="'width:' + Math.min(100, totalHours/8*100) + '%'"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ totalHours >= 8 ? 'Great work today!' : (totalHours >= 4 ? 'Halfway there' : 'Less than half a day logged') }}
                        </p>
                    </div>
                </div>

                <!-- Plan & Blockers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="card p-5">
                        <label class="form-label">Plan for Tomorrow</label>
                        <textarea v-model="planForTomorrow" rows="4"
                                  class="form-input resize-none"
                                  placeholder="What will you work on tomorrow?"></textarea>
                    </div>
                    <div class="card p-5">
                        <label class="form-label flex items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500"></i>
                            Blockers / Impediments
                        </label>
                        <textarea v-model="blockers" rows="4"
                                  class="form-input resize-none border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-400/20"
                                  placeholder="Anything blocking you? (leave empty if none)"></textarea>
                        <p class="text-xs text-slate-400 mt-1.5">Leave empty if no blockers</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="index_url" class="btn-ghost">Cancel</Link>
                    <button type="submit" :disabled="processing" class="btn-primary">
                        <i class="bi" :class="processing ? 'bi-arrow-repeat animate-spin' : 'bi-send-fill'"></i>
                        {{ processing ? 'Submitting…' : 'Submit Report' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
