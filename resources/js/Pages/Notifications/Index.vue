<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    notifications: Object,
    unread_count: Number,
})

const typeConfig = {
    task_assigned:       { icon: 'bi-person-plus-fill',       bg: 'bg-violet-100 dark:bg-violet-900/30',  color: 'text-violet-600 dark:text-violet-400' },
    task_commented:      { icon: 'bi-chat-left-text-fill',    bg: 'bg-sky-100 dark:bg-sky-900/30',        color: 'text-sky-600 dark:text-sky-400' },
    task_status_changed: { icon: 'bi-arrow-right-circle-fill',bg: 'bg-emerald-100 dark:bg-emerald-900/30',color: 'text-emerald-600 dark:text-emerald-400' },
    project_invite:      { icon: 'bi-folder-plus-fill',       bg: 'bg-amber-100 dark:bg-amber-900/30',   color: 'text-amber-600 dark:text-amber-400' },
    leave_requested:     { icon: 'bi-calendar-minus-fill',    bg: 'bg-orange-100 dark:bg-orange-900/30', color: 'text-orange-600 dark:text-orange-400' },
    leave_reviewed:      { icon: 'bi-calendar-check-fill',    bg: 'bg-teal-100 dark:bg-teal-900/30',    color: 'text-teal-600 dark:text-teal-400' },
    announcement:        { icon: 'bi-megaphone-fill',         bg: 'bg-rose-100 dark:bg-rose-900/30',    color: 'text-rose-600 dark:text-rose-400' },
    _default:            { icon: 'bi-bell-fill',              bg: 'bg-slate-100 dark:bg-slate-800',      color: 'text-slate-500 dark:text-slate-400' },
}

function cfgFor(n) {
    const type = n.data?.type ?? '_default'
    return typeConfig[type] ?? typeConfig._default
}

function markRead(n) {
    if (!n.is_unread) return
    router.patch(n.read_url, {}, { preserveScroll: true })
}

function markAllRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true })
}

function deleteNotification(n) {
    router.delete(n.destroy_url, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Notifications">
        <div class="max-w-2xl">
            <div class="page-header">
                <div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <i class="bi bi-bell-fill text-violet-600 dark:text-violet-400"></i>
                        </span>
                        Notifications
                        <span v-if="unread_count > 0" class="text-sm font-normal text-violet-500 ml-1">{{ unread_count }} unread</span>
                    </h1>
                </div>
                <button v-if="unread_count > 0" @click="markAllRead" class="btn-secondary btn-sm">
                    <i class="bi bi-check2-all"></i> Mark all read
                </button>
            </div>

            <div class="card overflow-hidden">
                <div v-if="notifications.data.length === 0" class="py-20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-bell-slash text-3xl text-slate-300 dark:text-slate-600"></i>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">You're all caught up!</h3>
                    <p class="text-sm text-slate-400 mt-1">No notifications right now.</p>
                </div>

                <div v-for="n in notifications.data" :key="n.id"
                     class="flex items-start gap-4 px-5 py-4 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                     :class="!n.is_unread ? 'opacity-70' : ''">

                    <!-- Icon -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                         :class="cfgFor(n).bg">
                        <i class="bi text-sm" :class="[cfgFor(n).icon, cfgFor(n).color]"></i>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm leading-snug"
                           :class="n.is_unread ? 'font-medium text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'">
                            {{ n.data?.message ?? 'New notification' }}
                        </p>
                        <p v-if="n.data?.task_title" class="text-xs text-slate-400 mt-0.5 truncate">
                            <i class="bi bi-check-square text-[10px] mr-1"></i>{{ n.data.task_title }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ n.created_at }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 flex-shrink-0 mt-0.5">
                        <button v-if="n.is_unread" @click="markRead(n)"
                                class="text-[11px] text-violet-500 hover:text-violet-700 dark:hover:text-violet-300 font-medium whitespace-nowrap">
                            Mark read
                        </button>
                        <a v-if="n.data?.url" :href="n.data.url"
                           class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
                           title="View">
                            <i class="bi bi-arrow-right text-sm"></i>
                        </a>
                        <button @click="deleteNotification(n)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 text-slate-300 dark:text-slate-600 hover:text-rose-500 transition-colors"
                                title="Dismiss">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>

                    <!-- Unread dot -->
                    <div v-if="n.is_unread" class="w-2 h-2 rounded-full bg-violet-500 flex-shrink-0 mt-2"></div>
                </div>
            </div>

            <div v-if="notifications.last_page > 1" class="mt-4 flex gap-1 justify-center">
                <template v-for="link in notifications.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs"
                          :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100'" v-html="link.label" />
                    <span v-else class="px-3 py-1 text-xs text-slate-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
