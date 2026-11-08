<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    conversations: Array,
    users: Array,
})

const showNew = ref(false)

function startDm(userId) {
    showNew.value = false
    router.get(route('chat.direct', userId))
}

function availabilityDot(avail) {
    const dots = {
        available: 'bg-emerald-400',
        busy:      'bg-amber-400',
        away:      'bg-orange-400',
        offline:   'bg-slate-300',
    }
    return dots[avail] ?? 'bg-slate-300'
}

function otherParticipant(conv) {
    return conv.participants[0]
}
</script>

<template>
    <AppLayout title="Chat">
        <div class="page-header">
            <h1 class="page-title">Messages</h1>
            <button @click="showNew = true" class="btn-primary"><i class="bi bi-chat-plus"></i> New message</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Conversations list -->
            <div class="md:col-span-1 card overflow-hidden self-start">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-semibold text-slate-900 dark:text-white text-sm">Conversations</h2>
                </div>
                <div v-if="conversations.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
                    No conversations yet. Start one!
                </div>
                <Link v-for="conv in conversations" :key="conv.id" :href="conv.show_url"
                      class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-50 dark:border-slate-800 last:border-b-0 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <div class="relative flex-shrink-0">
                        <img v-if="!conv.is_group" :src="otherParticipant(conv)?.avatar_url" class="w-10 h-10 rounded-full" alt="">
                        <div v-else class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-full flex items-center justify-center">
                            <i class="bi bi-people-fill text-violet-600"></i>
                        </div>
                        <span v-if="!conv.is_group" class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900"
                              :class="availabilityDot(otherParticipant(conv)?.availability)"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                {{ conv.name ?? otherParticipant(conv)?.name }}
                            </p>
                            <span v-if="conv.unread_count > 0"
                                  class="ml-2 w-5 h-5 rounded-full bg-violet-600 text-white text-[10px] flex items-center justify-center flex-shrink-0">
                                {{ conv.unread_count }}
                            </span>
                        </div>
                        <p v-if="conv.latest_message" class="text-xs text-slate-400 mt-0.5 truncate">
                            <template v-if="conv.latest_message.user">{{ conv.latest_message.user.name }}: </template>
                            {{ conv.latest_message.body }}
                        </p>
                    </div>
                </Link>
            </div>

            <!-- Right side hint -->
            <div class="hidden md:flex md:col-span-2 card items-center justify-center py-20 text-center">
                <div>
                    <i class="bi bi-chat-dots text-5xl text-slate-200 dark:text-slate-700 block mb-4"></i>
                    <p class="text-slate-400 text-sm">Select a conversation to start messaging.</p>
                </div>
            </div>
        </div>

        <!-- New message modal -->
        <Teleport to="body">
            <div v-if="showNew" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape="showNew = false">
                <div class="absolute inset-0 backdrop-blur-sm bg-slate-950/60" @click="showNew = false"></div>
                <div class="relative card w-full max-w-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-semibold text-slate-900 dark:text-white">New conversation</h3>
                        <button @click="showNew = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="px-5 py-4 max-h-80 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800">
                        <button v-for="user in users" :key="user.id" @click="startDm(user.id)"
                                class="w-full flex items-center gap-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 rounded-lg px-2 text-left">
                            <div class="relative">
                                <img :src="user.avatar_url" class="w-9 h-9 rounded-full" alt="">
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-slate-900"
                                      :class="availabilityDot(user.availability)"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ user.name }}</p>
                                <p v-if="user.job_title" class="text-xs text-slate-400 truncate">{{ user.job_title }}</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
