<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    team: Object,
    available_users: Array,
})

const showAdd = ref(false)
const addForm = useForm({ user_id: '', role: 'member' })

function addMember() {
    addForm.post(props.team.add_member_url, {
        onSuccess: () => { showAdd.value = false; addForm.reset() },
    })
}

function removeMember(member) {
    if (!confirm(`Remove ${member.name} from this team?`)) return
    useForm({}).delete(member.remove_url)
}
</script>

<template>
    <AppLayout :title="team.name">
        <div class="page-header">
            <h1 class="page-title">{{ team.name }}</h1>
            <Link v-if="team.can_manage" :href="team.edit_url" class="btn-secondary btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </Link>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Members -->
            <div class="xl:col-span-2 card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Members ({{ team.members.length }})</h3>
                    <button v-if="team.can_manage" @click="showAdd = true" class="btn-primary btn-sm">
                        <i class="bi bi-person-plus"></i> Add
                    </button>
                </div>
                <div class="divide-y divide-slate-50 dark:divide-slate-700">
                    <div v-for="member in team.members" :key="member.id" class="flex items-center gap-4 px-5 py-3.5">
                        <img :src="member.avatar_url" class="w-9 h-9 rounded-full" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm text-slate-900 dark:text-white">{{ member.name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ member.email }}</p>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              :class="member.role === 'owner' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                            {{ member.role.charAt(0).toUpperCase() + member.role.slice(1) }}
                        </span>
                        <button v-if="team.can_manage && member.user_id !== team.owner_id"
                                @click="removeMember(member)"
                                class="text-slate-300 hover:text-rose-500 transition-colors">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Projects sidebar -->
            <div class="card p-5 self-start">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Projects</h3>
                <div v-if="team.projects.length" class="space-y-2">
                    <Link v-for="project in team.projects" :key="project.id" :href="project.show_url"
                          class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="`background-color: ${project.color}`"></div>
                        <span class="text-sm text-slate-700 dark:text-slate-300 flex-1 truncate">{{ project.name }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                            {{ project.status.replace(/_/g, ' ') }}
                        </span>
                    </Link>
                </div>
                <p v-else class="text-xs text-slate-400">No projects yet</p>
            </div>
        </div>

        <!-- Add member modal -->
        <Teleport to="body">
            <div v-if="showAdd" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape="showAdd = false">
                <div class="absolute inset-0 backdrop-blur-sm bg-slate-950/60" @click="showAdd = false"></div>
                <div class="relative card w-full max-w-md overflow-hidden flex flex-col max-h-[90dvh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Add member</h3>
                        <button @click="showAdd = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i class="bi bi-x-lg text-base"></i>
                        </button>
                    </div>
                    <div v-if="available_users.length === 0" class="px-6 py-8 text-center">
                        <p class="text-sm text-slate-500">All users are already in this team.</p>
                    </div>
                    <form v-else @submit.prevent="addMember" class="px-6 py-5 space-y-4 overflow-y-auto">
                        <div>
                            <label class="form-label">Member <span class="text-rose-500">*</span></label>
                            <select v-model="addForm.user_id" class="form-input" required>
                                <option value="" disabled>Select a member…</option>
                                <option v-for="u in available_users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Role</label>
                            <select v-model="addForm.role" class="form-input">
                                <option value="admin">Admin</option>
                                <option value="member">Member</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                    </form>
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex justify-end gap-2 flex-shrink-0">
                        <button type="button" @click="showAdd = false" class="btn-ghost">Cancel</button>
                        <button v-if="available_users.length > 0" @click="addMember" class="btn-primary" :disabled="addForm.processing">
                            <i class="bi bi-person-plus"></i> Add member
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
