<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MemberPicker from '@/Components/MemberPicker.vue'
import { usePage } from '@inertiajs/vue3'
import DatePicker from '@/Components/DatePicker.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    users: Array,
})

const page = usePage()
const authUser = page.props.auth.user

const form = useForm({
    name: '',
    description: '',
    status: 'planning',
    priority: 'medium',
    color: '#3B82F6',
    start_date: '',
    due_date: '',
    budget: '',
    is_public: false,
    member_ids: [],
    member_roles: {},
})

function submit(e) {
    // Collect member_ids and member_roles from hidden inputs in the form
    const formEl = e.target
    const memberIds = [...formEl.querySelectorAll('input[name="member_ids[]"]')].map(i => parseInt(i.value))
    const memberRoleInputs = [...formEl.querySelectorAll('select[name^="member_roles["]')]
    const memberRoles = {}
    memberRoleInputs.forEach(sel => {
        const id = sel.name.match(/\[(\d+)\]/)?.[1]
        if (id) memberRoles[id] = sel.value
    })

    form.member_ids = memberIds
    form.member_roles = memberRoles
    form.post(route('projects.store'))
}
</script>

<template>
    <AppLayout title="New Project">
        <div class="max-w-2xl mx-auto">
            <div class="page-header">
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('projects.index')" class="hover:text-violet-600 transition-colors">Projects</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>New</span>
                    </div>
                    <h1 class="page-title">New Project</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Fill in the details to create a new project</p>
                </div>
                <Link :href="route('projects.index')" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back
                </Link>
            </div>

            <div class="card p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Validation error banner -->
                    <div v-if="Object.keys(form.errors).length"
                         class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 px-4 py-3 flex items-start gap-2.5">
                        <i class="bi bi-exclamation-circle-fill text-rose-500 flex-shrink-0 mt-0.5 text-sm"></i>
                        <ul class="text-sm text-rose-700 dark:text-rose-300 space-y-0.5 list-none m-0 p-0">
                            <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="form-label">Project Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required class="form-input"
                                   :class="form.errors.name ? 'border-rose-400 dark:border-rose-600' : ''"
                                   placeholder="e.g. Website Redesign">
                            <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description" rows="3" class="form-input resize-none"
                                      placeholder="Brief description of the project..."></textarea>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <SearchSelect v-model="form.status"
                                :options="[{value:'planning',label:'Planning'},{value:'active',label:'Active'},{value:'on_hold',label:'On Hold'}]"
                                placeholder="Select status…" />
                        </div>
                        <div>
                            <label class="form-label">Priority</label>
                            <SearchSelect v-model="form.priority"
                                :options="[{value:'low',label:'Low'},{value:'medium',label:'Medium'},{value:'high',label:'High'},{value:'critical',label:'Critical'}]"
                                placeholder="Select priority…" />
                        </div>
                        <div>
                            <label class="form-label">Project Color</label>
                            <div class="flex items-center gap-3">
                                <div class="color-swatch-btn">
                                    <input type="color" v-model="form.color">
                                </div>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Click to choose accent color</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Start Date</label>
                            <DatePicker v-model="form.start_date" />
                        </div>
                        <div>
                            <label class="form-label">Due Date</label>
                            <DatePicker v-model="form.due_date" />
                        </div>
                        <div>
                            <label class="form-label">Budget ($)</label>
                            <input v-model="form.budget" type="number" min="0" step="0.01" class="form-input" placeholder="0.00">
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <input v-model="form.is_public" type="checkbox" id="is_public"
                                       class="w-4 h-4 text-violet-600 border-slate-300 rounded focus:ring-violet-500">
                                <label for="is_public" class="text-sm text-slate-700 dark:text-slate-300">Make project public</label>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 ml-7">All members can view this project, even if they're not assigned to it.</p>
                        </div>

                        <MemberPicker :users="users" :owner-id="authUser.id" :initial="[]" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <Link :href="route('projects.index')" class="btn-secondary">Cancel</Link>
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            <i class="bi bi-check-lg"></i> Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
