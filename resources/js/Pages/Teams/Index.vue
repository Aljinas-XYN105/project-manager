<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    teams: Array,
    can_create: Boolean,
})
</script>

<template>
    <AppLayout title="Teams">
        <div class="page-header">
            <h1 class="page-title">Teams</h1>
            <Link v-if="can_create" :href="route('teams.create')" class="btn-primary">
                <i class="bi bi-plus-lg"></i> New Team
            </Link>
        </div>

        <div v-if="teams.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="team in teams" :key="team.id" class="card-hover p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-xl flex items-center justify-center">
                            <i class="bi bi-people-fill text-violet-600 dark:text-violet-400"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ team.name }}</h3>
                            <p class="text-xs text-slate-500">{{ team.member_count }} {{ team.member_count === 1 ? 'member' : 'members' }}</p>
                        </div>
                    </div>
                    <Link v-if="team.can_edit" :href="team.edit_url" class="text-slate-300 hover:text-slate-600 dark:hover:text-slate-200">
                        <i class="bi bi-pencil text-sm"></i>
                    </Link>
                </div>
                <p v-if="team.description" class="text-xs text-slate-500 mb-4 line-clamp-2">{{ team.description }}</p>
                <div class="flex items-center justify-between">
                    <div class="flex">
                        <img v-for="u in team.users" :key="u.id" :src="u.avatar_url" :title="u.name"
                             class="w-7 h-7 rounded-full border-2 border-white dark:border-slate-800 -ml-1.5 first:ml-0">
                        <span v-if="team.extra_users > 0"
                              class="w-7 h-7 rounded-full bg-slate-200 text-xs flex items-center justify-center -ml-1.5 border-2 border-white dark:border-slate-800">
                            +{{ team.extra_users }}
                        </span>
                    </div>
                    <Link :href="team.show_url" class="btn-secondary btn-sm">View</Link>
                </div>
            </div>
        </div>

        <div v-else class="card p-16 text-center">
            <i class="bi bi-people text-4xl text-slate-300 dark:text-slate-600 block mb-4"></i>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-4">No teams yet</h3>
            <p class="text-sm text-slate-500 mt-2 mb-6">Create a team to collaborate with others</p>
            <Link v-if="can_create" :href="route('teams.create')" class="btn-primary">
                <i class="bi bi-plus-lg"></i> Create Team
            </Link>
        </div>
    </AppLayout>
</template>
