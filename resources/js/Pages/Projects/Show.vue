<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import SearchSelect from '@/Components/SearchSelect.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    project: Object,
    stats: Object,
    available_users: Array,
    can_update: Boolean,
    can_manage_member: Boolean,
})

const statusBadge = {
    planning:  'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
    active:    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    on_hold:   'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    archived:  'bg-slate-100 dark:bg-slate-800 text-slate-500',
}

const priorityBadge = {
    critical: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    high:     'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    medium:   'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    low:      'bg-slate-100 dark:bg-slate-800 text-slate-500',
}

function label(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : ''
}

// Add member modal
const showAddMember = ref(false)
const addMemberForm = useForm({ user_id: '', role: 'developer' })

function submitAddMember() {
    addMemberForm.post(props.project.add_member_url, {
        preserveScroll: true,
        onSuccess: () => {
            showAddMember.value = false
            addMemberForm.reset()
        },
    })
}

function removeMember(memberId) {
    router.delete(route('projects.members.remove', [props.project.id, memberId]), {
        preserveScroll: true,
    })
}

// Attachment manager
const files = ref([...props.project.attachments])
const uploading = ref(false)
const uploadProgress = ref(0)
const uploadStatus = ref('')
const dragging = ref(false)

const blockedExts = ['exe','bat','sh','cmd','msi','dmg','app','bin','jar','vbs','ps1']

function validateFile(file) {
    const ext = file.name.split('.').pop().toLowerCase()
    if (blockedExts.includes(ext)) return false
    if (file.size > 100 * 1024 * 1024) return false
    return true
}

async function upload(fileList) {
    if (!fileList || !fileList.length) return
    uploading.value = true
    uploadProgress.value = 0

    for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i]
        if (!validateFile(file)) continue
        uploadStatus.value = `Uploading ${file.name}… (${i + 1}/${fileList.length})`

        const form = new FormData()
        form.append('file', file)

        try {
            const res = await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest()
                xhr.open('POST', props.project.upload_attachment_url)
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content)
                xhr.upload.onprogress = e => {
                    if (e.lengthComputable) uploadProgress.value = Math.round((e.loaded / e.total) * 100)
                }
                xhr.onload = () => xhr.status >= 200 && xhr.status < 300
                    ? resolve(JSON.parse(xhr.responseText))
                    : reject(new Error(xhr.responseText))
                xhr.onerror = () => reject(new Error('Network error'))
                xhr.send(form)
            })
            const att = res.attachment
            files.value.push({
                id: att.id,
                original_name: att.original_name,
                mime_type: att.mime_type,
                size_formatted: att.size_formatted,
                is_image: att.is_image,
                is_video: att.is_video,
                url: res.url,
                uploader_name: att.user?.name ?? 'You',
                created_ago: 'just now',
            })
        } catch {}
    }
    uploading.value = false
    uploadProgress.value = 0
}

async function removeAttachment(id) {
    if (!confirm('Delete this attachment?')) return
    const url = props.project.destroy_attachment_url.replace(':id', id)
    const res = await fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
    })
    if (res.ok) files.value = files.value.filter(f => f.id !== id)
}

function fileIcon(mimeType) {
    if (!mimeType) return 'bi bi-file-earmark text-slate-400'
    if (mimeType.includes('pdf')) return 'bi bi-file-earmark-pdf text-rose-400'
    if (mimeType.includes('word')) return 'bi bi-file-earmark-word text-blue-400'
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'bi bi-file-earmark-excel text-emerald-400'
    if (mimeType.includes('zip')) return 'bi bi-file-earmark-zip text-amber-400'
    return 'bi bi-file-earmark text-slate-400'
}

const taskRows = [
    { label: 'To Do',       key: 'todo',        color: 'bg-slate-400' },
    { label: 'In Progress', key: 'in_progress',  color: 'bg-blue-500' },
    { label: 'QA Testing',  key: 'qa_testing',   color: 'bg-violet-500' },
    { label: 'In Review',   key: 'in_review',    color: 'bg-yellow-500' },
    { label: 'Done',        key: 'done',         color: 'bg-green-500' },
]
</script>

<template>
    <AppLayout :title="project.name">
        <!-- Page header -->
        <div class="page-header">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                     :style="`background-color: ${project.color}25;`">
                    <div class="w-4 h-4 rounded-full" :style="`background-color: ${project.color};`"></div>
                </div>
                <div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                        <a :href="route('projects.index')" class="hover:text-violet-600 transition-colors">Projects</a>
                        <i class="bi bi-chevron-right text-[10px]"></i>
                        <span>{{ project.name }}</span>
                    </div>
                    <h1 class="page-title">{{ project.name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium"
                              :class="statusBadge[project.status] ?? statusBadge.planning">
                            {{ label(project.status) }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium"
                              :class="priorityBadge[project.priority] ?? priorityBadge.medium">
                            {{ label(project.priority) }}
                        </span>
                        <span v-if="project.is_overdue"
                              class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            <i class="bi bi-exclamation-triangle-fill mr-1"></i>Overdue
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="project.notes_url" class="btn-ghost btn-sm">
                    <i class="bi bi-journal-text"></i> Notes
                </Link>
                <Link :href="project.sprints_url" class="btn-ghost btn-sm">
                    <i class="bi bi-speedometer2"></i> Sprints
                </Link>
                <Link :href="project.board_url" class="btn-secondary btn-sm">
                    <i class="bi bi-kanban"></i> Board
                </Link>
                <Link :href="project.add_task_url" class="btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Task
                </Link>
                <Link v-if="can_update" :href="project.edit_url" class="btn-ghost btn-sm">
                    <i class="bi bi-pencil"></i>
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <span class="text-xs text-slate-500 font-medium">Total Tasks</span>
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total_tasks }}</span>
            </div>
            <div class="stat-card">
                <span class="text-xs text-slate-500 font-medium">Completed</span>
                <span class="text-2xl font-bold text-green-600">{{ stats.done }}</span>
            </div>
            <div class="stat-card">
                <span class="text-xs text-slate-500 font-medium">In Progress</span>
                <span class="text-2xl font-bold text-blue-600">{{ stats.in_progress }}</span>
            </div>
            <div class="stat-card">
                <span class="text-xs text-slate-500 font-medium">Hours Logged</span>
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total_hours }}h</span>
            </div>
        </div>

        <!-- Progress -->
        <div class="card p-5 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Overall Progress</span>
                <span class="text-lg font-bold text-violet-600">{{ stats.progress }}%</span>
            </div>
            <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-700"
                     :style="`width: ${stats.progress}%`"></div>
            </div>
            <div class="flex justify-between text-xs text-slate-400 mt-2">
                <span>{{ stats.done }} of {{ stats.total_tasks }} tasks done</span>
                <span v-if="project.due_date" :class="project.is_overdue ? 'text-red-500' : ''">
                    Due {{ project.due_date }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Task Status Breakdown -->
            <div class="xl:col-span-2">
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Tasks by Status</h3>
                        <Link :href="project.tasks_url" class="text-xs text-violet-500 hover:underline">View all</Link>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="row in taskRows" :key="row.key">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700 dark:text-slate-300">{{ row.label }}</span>
                                <span class="font-medium text-slate-900 dark:text-white">
                                    {{ stats[row.key] }}
                                    <span class="text-slate-400 font-normal">
                                        ({{ stats.total_tasks > 0 ? Math.round(stats[row.key] / stats.total_tasks * 100) : 0 }}%)
                                    </span>
                                </span>
                            </div>
                            <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500" :class="row.color"
                                     :style="`width: ${stats.total_tasks > 0 ? Math.round(stats[row.key] / stats.total_tasks * 100) : 0}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Members -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Members</h3>
                    <button v-if="can_manage_member" @click="showAddMember = true"
                            class="text-xs text-violet-500 hover:underline">+ Add</button>
                </div>
                <div class="divide-y divide-slate-50 dark:divide-slate-700">
                    <div v-for="member in project.members" :key="member.id"
                         class="flex items-center gap-3 px-5 py-3">
                        <UserAvatar :src="member.avatar_url" :name="member.name" size="md" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ member.name }}</p>
                            <p class="text-xs text-slate-400">{{ member.role.charAt(0).toUpperCase() + member.role.slice(1) }}</p>
                        </div>
                        <button v-if="can_manage_member && member.id !== project.owner_id"
                                @click="removeMember(member.id)"
                                class="text-slate-300 hover:text-red-500 transition-colors">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments -->
        <div class="card overflow-hidden mt-6">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-paperclip text-slate-400 text-sm"></i>
                    Project Files &amp; Attachments
                    <span class="text-xs font-normal text-slate-400">({{ files.length }})</span>
                </h3>
                <label class="btn-ghost btn-sm cursor-pointer">
                    <i class="bi bi-plus-lg text-sm"></i> Upload
                    <input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                </label>
            </div>

            <!-- Drop zone -->
            <div class="px-5 py-3 border-b border-dashed border-slate-200 dark:border-slate-700 transition-colors"
                 :class="dragging ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-400' : ''"
                 @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="dragging = false; upload($event.dataTransfer.files)">
                <div class="flex items-center justify-center gap-2 py-2 text-center">
                    <i class="bi bi-cloud-upload text-xl" :class="dragging ? 'text-violet-400' : 'text-slate-300 dark:text-slate-600'"></i>
                    <p class="text-xs text-slate-400" :class="dragging ? 'text-violet-500' : ''">
                        Drop files here or
                        <label class="text-violet-500 hover:text-violet-600 cursor-pointer font-medium">
                            browse<input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                        </label>
                        <span class="text-slate-300 dark:text-slate-600"> · images, PDFs, videos, docs · max 100 MB</span>
                    </p>
                </div>
            </div>

            <!-- Upload progress -->
            <div v-if="uploading" class="px-5 py-3 flex items-center gap-3 border-b border-slate-50 dark:border-slate-800 bg-violet-50/50 dark:bg-violet-900/10">
                <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-arrow-up text-violet-500 text-sm animate-pulse"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-medium text-violet-700 dark:text-violet-300">{{ uploadStatus }}</p>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1 mt-1.5">
                        <div class="bg-violet-500 h-1 rounded-full transition-all duration-300"
                             :style="`width: ${uploadProgress}%`"></div>
                    </div>
                </div>
            </div>

            <!-- File grid -->
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <div v-if="files.length === 0 && !uploading" class="col-span-full py-8 text-center">
                    <i class="bi bi-cloud-upload text-3xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                    <p class="text-sm text-slate-400">No project files yet — drag and drop or click Upload</p>
                </div>
                <div v-for="att in files" :key="att.id"
                     class="group relative rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800/50 hover:border-violet-300 dark:hover:border-violet-700 transition-all">
                    <div class="h-28 flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                        <img v-if="att.is_image" :src="att.url" :alt="att.original_name" class="h-full w-full object-cover">
                        <div v-else-if="att.is_video" class="flex flex-col items-center gap-1 text-violet-500">
                            <i class="bi bi-play-circle-fill text-3xl"></i>
                            <span class="text-[10px] font-medium text-slate-400">Video</span>
                        </div>
                        <div v-else class="flex flex-col items-center gap-1">
                            <i class="text-3xl" :class="fileIcon(att.mime_type)"></i>
                        </div>
                    </div>
                    <div class="px-3 py-2">
                        <p class="text-xs font-medium text-slate-900 dark:text-white truncate">{{ att.original_name }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ att.size_formatted }} · {{ att.uploader_name }}</p>
                    </div>
                    <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a :href="att.url" target="_blank"
                           class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/90 dark:bg-slate-900/90 shadow text-slate-600 hover:text-violet-600">
                            <i class="bi bi-download text-xs"></i>
                        </a>
                        <button @click="removeAttachment(att.id)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/90 dark:bg-slate-900/90 shadow text-slate-400 hover:text-rose-500">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        <Teleport to="body">
            <div v-if="showAddMember" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @click.self="showAddMember = false">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showAddMember = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Add Member</h2>
                    <form @submit.prevent="submitAddMember" class="space-y-4">
                        <div>
                            <label class="form-label">Member</label>
                            <p v-if="available_users.length === 0" class="text-sm text-slate-400">
                                All active users are already members of this project.
                            </p>
                            <SearchSelect v-else v-model="addMemberForm.user_id"
                                :options="available_users.map(u => ({value: u.id, label: u.name, avatar: u.avatar_url}))"
                                placeholder="Select a user…" />
                        </div>
                        <div v-if="available_users.length > 0">
                            <label class="form-label">Role</label>
                            <SearchSelect v-model="addMemberForm.role"
                                :options="[{value:'developer',label:'Developer'},{value:'manager',label:'Manager'},{value:'viewer',label:'Viewer'}]"
                                placeholder="Select role…" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showAddMember = false" class="btn-secondary">
                                {{ available_users.length === 0 ? 'Close' : 'Cancel' }}
                            </button>
                            <button v-if="available_users.length > 0" type="submit" class="btn-primary"
                                    :disabled="addMemberForm.processing">
                                Add Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
