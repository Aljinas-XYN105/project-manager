<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    notifications: Object,
    unread_count: Number,
})

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

function notifTitle(n) {
    return n.data?.title ?? n.type.replace(/([A-Z])/g, ' $1').trim()
}

function notifBody(n) {
    return n.data?.message ?? n.data?.body ?? ''
}

function notifUrl(n) {
    return n.data?.url ?? n.data?.action_url ?? null
}
</script>

<template>
    <AppLayout title="Notifications">
        <div class="page-header">
            <div>
                <h1 class="page-title">Notifications</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ unread_count > 0 ? `${unread_count} unread` : 'All caught up!' }}
                </p>
            </div>
            <button v-if="unread_count > 0" @click="markAllRead" class="btn-ghost">
                <i class="bi bi-check-all"></i> Mark all read
            </button>
        </div>

        <div class="card overflow-hidden">
            <div v-if="notifications.data.length === 0" class="py-14 text-center">
                <i class="bi bi-bell-slash text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                <p class="text-sm text-slate-500">No notifications yet.</p>
            </div>
            <div v-for="n in notifications.data" :key="n.id"
                 class="flex items-start gap-4 px-5 py-4 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40"
                 :class="n.is_unread ? 'bg-violet-50/40 dark:bg-violet-900/5' : ''">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center"
                         :class="n.is_unread ? 'bg-violet-100 dark:bg-violet-900/30' : 'bg-slate-100 dark:bg-slate-800'">
                        <i class="bi bi-bell text-sm"
                           :class="n.is_unread ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400'"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ notifTitle(n) }}</p>
                    <p v-if="notifBody(n)" class="text-sm text-slate-500 mt-0.5">{{ notifBody(n) }}</p>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-xs text-slate-400">{{ n.created_at }}</span>
                        <span v-if="n.read_at" class="text-xs text-slate-400">· Read {{ n.read_at }}</span>
                        <a v-if="notifUrl(n)" :href="notifUrl(n)" class="text-xs text-violet-600 hover:text-violet-700">View →</a>
                    </div>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button v-if="n.is_unread" @click="markRead(n)"
                            class="btn-ghost btn-sm" title="Mark read">
                        <i class="bi bi-check"></i>
                    </button>
                    <button @click="deleteNotification(n)"
                            class="btn-ghost btn-sm text-slate-400 hover:text-rose-500" title="Delete">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="notifications.last_page > 1" class="mt-4 flex gap-1 justify-center">
            <template v-for="link in notifications.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs"
                      :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100'" v-html="link.label" />
                <span v-else class="px-3 py-1 text-xs text-slate-300" v-html="link.label" />
            </template>
        </div>
    </AppLayout>
</template>
