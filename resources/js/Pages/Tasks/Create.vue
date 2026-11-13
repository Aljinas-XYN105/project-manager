<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import TimePicker from '@/Components/TimePicker.vue'
import { useClickOutside } from '@/Composables/useClickOutside'
import SearchSelect from '@/Components/SearchSelect.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    project: Object,
    column: Object,
    columns: Array,
    members: Array,
    labels: Array,
    sprints: Array,
})

const taskTypes = [
    { val: 'task',    icon: 'bi-check-square-fill', color: 'text-slate-500',  label: 'Task' },
    { val: 'bug',     icon: 'bi-bug-fill',           color: 'text-red-500',    label: 'Bug' },
    { val: 'feature', icon: 'bi-stars',               color: 'text-purple-500', label: 'Feature' },
    { val: 'story',   icon: 'bi-bookmark-fill',       color: 'text-blue-500',   label: 'Story' },
    { val: 'epic',    icon: 'bi-lightning-fill',      color: 'text-orange-500', label: 'Epic' },
]

const statusOpts = [
    { val: 'todo',        label: 'To Do',       icon: 'bi-circle',              chip: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' },
    { val: 'in_progress', label: 'In Progress', icon: 'bi-arrow-repeat',        chip: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    { val: 'qa_testing',  label: 'QA Testing',  icon: 'bi-clipboard2-check',    chip: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' },
    { val: 'in_review',   label: 'In Review',   icon: 'bi-eye',                 chip: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' },
]

const priorityOpts = [
    { val: 'low',      label: 'Low',      icon: 'bi-arrow-down',              chip: 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' },
    { val: 'medium',   label: 'Medium',   icon: 'bi-dash',                    chip: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    { val: 'high',     label: 'High',     icon: 'bi-arrow-up',                chip: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' },
    { val: 'critical', label: 'Critical', icon: 'bi-exclamation-circle-fill', chip: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
]

const form = useForm({
    type: 'task',
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    column_id: props.column?.id ?? props.columns[0]?.id ?? null,
    sprint_id: '',
    assignee_ids: [],
    label_ids: [],
    due_date: '',
    due_time: '',
    estimated_hours: '',
    story_points: '',
    parent_id: '',
})

const statusOpen   = ref(false)
const priorityOpen = ref(false)
const statusContainerRef   = ref(null)
const priorityContainerRef = ref(null)
useClickOutside([
    { elRef: statusContainerRef,   close: () => { statusOpen.value   = false } },
    { elRef: priorityContainerRef, close: () => { priorityOpen.value = false } },
])
const bugFields    = ref(false)
const memberSearch = ref('')
const filteredMembers = computed(() => {
    const q = memberSearch.value.toLowerCase().trim()
    return q ? props.members.filter(m => m.name.toLowerCase().includes(q)) : props.members
})

const curStatus   = () => statusOpts.find(o => o.val === form.status) ?? statusOpts[0]
const curPriority = () => priorityOpts.find(o => o.val === form.priority) ?? priorityOpts[1]

function toggleAssignee(id) {
    const i = form.assignee_ids.indexOf(id)
    i === -1 ? form.assignee_ids.push(id) : form.assignee_ids.splice(i, 1)
}
function toggleLabel(id) {
    const i = form.label_ids.indexOf(id)
    i === -1 ? form.label_ids.push(id) : form.label_ids.splice(i, 1)
}

function submit() {
    if (form.due_date && form.due_time) {
        form.due_date = form.due_date + ' ' + form.due_time
    }
    form.post(props.project.store_url)
}
</script>

<template>
    <AppLayout title="New Task">
        <div class="max-w-2xl mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <Link :href="project.show_url" class="hover:text-violet-600 transition-colors">{{ project.name }}</Link>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>New Task</span>
                    </div>
                    <h1 class="page-title">Create Task</h1>
                </div>
                <Link :href="project.index_url" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</Link>
            </div>

            <div class="card p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Validation error summary -->
                    <div v-if="Object.keys(form.errors).length"
                         class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 px-4 py-3 flex items-start gap-2.5">
                        <i class="bi bi-exclamation-circle-fill text-rose-500 flex-shrink-0 mt-0.5 text-sm"></i>
                        <ul class="text-sm text-rose-700 dark:text-rose-300 space-y-0.5 list-none">
                            <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                        </ul>
                    </div>

                    <!-- Type selector -->
                    <div>
                        <label class="form-label">Ticket Type *</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="t in taskTypes" :key="t.val" type="button"
                                    @click="form.type = t.val"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-all border-slate-200 dark:border-slate-600 hover:border-violet-400"
                                    :class="form.type === t.val ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 shadow-sm' : ''">
                                <i class="bi" :class="[t.icon, t.color]"></i>
                                <span class="text-sm">{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Title *</label>
                        <input v-model="form.title" type="text" required class="form-input text-base font-medium" placeholder="What needs to be done?">
                        <p v-if="form.errors.title" class="text-xs text-rose-500 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="form-label">Description</label>
                        <textarea v-model="form.description" rows="4" class="form-input resize-none" placeholder="Add more details..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Status -->
                        <div>
                            <label class="form-label">Status</label>
                            <div ref="statusContainerRef" class="relative">
                                <button type="button" @click="statusOpen = !statusOpen"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold w-full hover:opacity-90 transition-colors"
                                        :class="curStatus().chip">
                                    <i class="bi text-xs" :class="curStatus().icon"></i>
                                    <span class="flex-1 text-left">{{ curStatus().label }}</span>
                                    <i class="bi bi-chevron-down text-[10px] opacity-50"></i>
                                </button>
                                <div v-if="statusOpen"
                                     class="absolute left-0 top-full mt-1 z-50 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                                    <button v-for="o in statusOpts" :key="o.val" type="button" @click="form.status = o.val; statusOpen = false"
                                            class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="o.chip">
                                            <i class="bi text-[10px]" :class="o.icon"></i>
                                        </span>
                                        <span>{{ o.label }}</span>
                                        <i v-if="o.val === form.status" class="bi bi-check text-violet-500 text-xs ml-auto"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Priority -->
                        <div>
                            <label class="form-label">Priority</label>
                            <div ref="priorityContainerRef" class="relative">
                                <button type="button" @click="priorityOpen = !priorityOpen"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold w-full hover:opacity-90 transition-colors"
                                        :class="curPriority().chip">
                                    <i class="bi text-xs" :class="curPriority().icon"></i>
                                    <span class="flex-1 text-left">{{ curPriority().label }}</span>
                                    <i class="bi bi-chevron-down text-[10px] opacity-50"></i>
                                </button>
                                <div v-if="priorityOpen"
                                     class="absolute left-0 top-full mt-1 z-50 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5">
                                    <button v-for="o in priorityOpts" :key="o.val" type="button" @click="form.priority = o.val; priorityOpen = false"
                                            class="flex items-center gap-2.5 w-full px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <span class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0" :class="o.chip">
                                            <i class="bi text-[10px]" :class="o.icon"></i>
                                        </span>
                                        <span>{{ o.label }}</span>
                                        <i v-if="o.val === form.priority" class="bi bi-check text-violet-500 text-xs ml-auto"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Column</label>
                            <SearchSelect v-model="form.column_id"
                                :options="columns.map(c => ({value: c.id, label: c.name}))"
                                placeholder="Select column…" />
                        </div>
                        <div v-if="sprints.length">
                            <label class="form-label">Sprint</label>
                            <SearchSelect v-model="form.sprint_id"
                                :options="sprints.map(s => ({value: s.id, label: s.name}))"
                                :clearable="true" clearLabel="No sprint" placeholder="No sprint" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Due date &amp; time</label>
                            <DatePicker v-model="form.due_date" />
                            <TimePicker v-if="form.due_date" v-model="form.due_time" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="form-label">Estimate (hrs)</label>
                            <input v-model="form.estimated_hours" type="number" min="0" step="0.5" class="form-input" placeholder="0">
                            <p v-if="form.errors.estimated_hours" class="text-xs text-rose-500 mt-1">{{ form.errors.estimated_hours }}</p>
                        </div>
                    </div>

                    <!-- Assignees -->
                    <div v-if="members.length">
                        <label class="form-label">Assignees</label>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <span v-for="id in form.assignee_ids" :key="id"
                                  class="inline-flex items-center gap-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs px-2 py-0.5 rounded-full">
                                {{ members.find(m => m.id === id)?.name }}
                                <button type="button" @click="toggleAssignee(id)" class="hover:text-rose-500"><i class="bi bi-x text-xs"></i></button>
                            </span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-2 pt-2 pb-1">
                                <input v-model="memberSearch" type="text" placeholder="Search members…"
                                       class="w-full text-xs bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 outline-none placeholder-slate-400 text-slate-700 dark:text-slate-300 focus:ring-1 focus:ring-violet-400" />
                            </div>
                            <div class="grid grid-cols-2 gap-1 max-h-36 overflow-y-auto p-2">
                                <button v-for="m in filteredMembers" :key="m.id" type="button" @click="toggleAssignee(m.id)"
                                        class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-sm transition-colors"
                                        :class="form.assignee_ids.includes(m.id) ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' : 'hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
                                    <UserAvatar :src="m.avatar_url" :name="m.name" size="xs" />
                                    <span class="truncate text-xs">{{ m.name }}</span>
                                    <i v-if="form.assignee_ids.includes(m.id)" class="bi bi-check-lg text-violet-500 ml-auto text-xs flex-shrink-0"></i>
                                </button>
                                <p v-if="!filteredMembers.length" class="text-xs text-slate-400 py-3 text-center col-span-2">No match</p>
                            </div>
                        </div>
                    </div>

                    <!-- Labels -->
                    <div v-if="labels.length">
                        <label class="form-label">Labels</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="lbl in labels" :key="lbl.id" type="button" @click="toggleLabel(lbl.id)"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-white transition-all"
                                    :style="form.label_ids.includes(lbl.id) ? { backgroundColor: lbl.color, boxShadow: '0 0 0 2px ' + lbl.color } : { backgroundColor: lbl.color + '66' }">
                                {{ lbl.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Bug-specific -->
                    <div v-if="form.type === 'bug'" class="border border-rose-200 dark:border-rose-900/40 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-rose-500 uppercase tracking-wide">Bug Details</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Severity</label>
                                <SearchSelect v-model="form.severity"
                                    :options="[{value:'low',label:'Low'},{value:'medium',label:'Medium'},{value:'high',label:'High'},{value:'critical',label:'Critical'}]"
                                    :clearable="true" clearLabel="None" placeholder="Select…" />
                            </div>
                            <div>
                                <label class="form-label">Environment</label>
                                <input v-model="form.environment" type="text" class="form-input" placeholder="e.g. Production">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Steps to Reproduce</label>
                            <textarea v-model="form.steps_to_reproduce" rows="3" class="form-input resize-none" placeholder="1. Go to…"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Expected Behavior</label>
                                <textarea v-model="form.expected_behavior" rows="2" class="form-input resize-none"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Actual Behavior</label>
                                <textarea v-model="form.actual_behavior" rows="2" class="form-input resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <Link :href="project.index_url" class="btn-ghost">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary gap-2 disabled:opacity-50">
                            <i class="bi" :class="form.processing ? 'bi-arrow-repeat animate-spin' : 'bi-plus-lg'"></i>
                            {{ form.processing ? 'Creating…' : 'Create Task' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
