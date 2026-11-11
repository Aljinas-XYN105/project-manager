<script setup>
import { ref, computed, onMounted } from 'vue'
import SearchSelect from '@/Components/SearchSelect.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    users: Array,
    ownerId: Number,
    initial: { type: Array, default: () => [] },
})

const search = ref('')
const selected = ref({})

onMounted(() => {
    if (props.initial && Array.isArray(props.initial)) {
        props.initial.forEach(m => {
            selected.value[m.id] = { ...m }
        })
    }
    const owner = props.users.find(u => u.id === props.ownerId)
    if (owner && !selected.value[props.ownerId]) {
        selected.value[props.ownerId] = { ...owner, role: 'manager' }
    }
    if (selected.value[props.ownerId]) {
        selected.value[props.ownerId].role = 'manager'
    }
})

const filteredUsers = computed(() => {
    const q = search.value.toLowerCase().trim()
    if (!q) return props.users
    return props.users.filter(u =>
        u.name.toLowerCase().includes(q) ||
        (u.job_title || '').toLowerCase().includes(q)
    )
})

const selectedList = computed(() => Object.values(selected.value))

function isSelected(id) {
    return id in selected.value
}

function toggle(user) {
    if (user.id === props.ownerId) return
    if (isSelected(user.id)) {
        remove(user.id)
    } else {
        selected.value = { ...selected.value, [user.id]: { ...user, role: 'developer' } }
    }
}

function remove(id) {
    if (id === props.ownerId) return
    const copy = { ...selected.value }
    delete copy[id]
    selected.value = copy
}
</script>

<template>
    <div class="sm:col-span-2 border-t border-slate-100 dark:border-slate-700 pt-5">
        <div class="flex items-center justify-between mb-3">
            <label class="form-label mb-0">Team Members</label>
            <span class="text-xs text-slate-400">{{ Object.keys(selected).length }} selected</span>
        </div>

        <!-- Search -->
        <div class="relative mb-3">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            <input v-model="search" type="text" placeholder="Search members…" class="form-input pl-8 py-2 text-sm">
        </div>

        <!-- User grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto px-1 mb-4">
            <button v-for="user in filteredUsers" :key="user.id"
                    type="button"
                    @click="toggle(user)"
                    :disabled="user.id === ownerId"
                    :class="user.id === ownerId
                        ? 'ring-2 ring-slate-300 dark:ring-slate-600 bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 cursor-default'
                        : (isSelected(user.id)
                            ? 'ring-2 ring-violet-500 bg-violet-50 dark:bg-violet-900/20 border-violet-300 dark:border-violet-700'
                            : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600')"
                    class="flex items-center gap-2.5 p-2.5 rounded-lg border text-left transition-all">
                <div class="relative flex-shrink-0">
                    <UserAvatar :src="user.avatar ?? ''" :name="user.name" size="md" />
                    <span v-if="user.id === ownerId"
                          class="absolute -top-1 -right-1 w-4 h-4 bg-slate-400 dark:bg-slate-500 rounded-full flex items-center justify-center">
                        <i class="bi bi-lock-fill text-white" style="font-size:7px"></i>
                    </span>
                    <span v-else-if="isSelected(user.id)"
                          class="absolute -top-1 -right-1 w-4 h-4 bg-violet-500 rounded-full flex items-center justify-center">
                        <i class="bi bi-check text-white" style="font-size:9px"></i>
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ user.name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ user.job_title || 'Team Member' }}</p>
                </div>
                <span v-if="user.id === ownerId" class="text-xs text-slate-500 dark:text-slate-400 font-medium flex-shrink-0">Owner</span>
            </button>
            <p v-if="filteredUsers.length === 0" class="text-sm text-slate-400 py-4 text-center sm:col-span-2">No members found</p>
        </div>

        <!-- Selected members with roles -->
        <div v-if="selectedList.length > 0">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Roles</p>
            <div class="space-y-2">
                <div v-for="member in selectedList" :key="member.id"
                     class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                    <UserAvatar :src="member.avatar ?? ''" :name="member.name" size="sm" />
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 flex-1 truncate">{{ member.name }}</span>
                    <div class="w-28 flex-shrink-0" :class="member.id === ownerId ? 'opacity-60 pointer-events-none' : ''">
                        <input type="hidden" :name="`member_roles[${member.id}]`" :value="selected[member.id].role">
                        <SearchSelect v-model="selected[member.id].role" size="sm"
                            :options="[{value:'developer',label:'Developer'},{value:'manager',label:'Manager'},{value:'viewer',label:'Viewer'}]"
                            placeholder="Role" />
                    </div>
                    <button v-if="member.id !== ownerId" type="button" @click="remove(member.id)"
                            class="text-slate-400 hover:text-rose-500 transition-colors flex-shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden inputs for form submission -->
        <template v-for="member in selectedList" :key="`input-${member.id}`">
            <input type="hidden" name="member_ids[]" :value="member.id">
        </template>
    </div>
</template>
