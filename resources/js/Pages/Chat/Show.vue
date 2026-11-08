<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    conversation: Object,
    messages: Object,
    users: Array,
    back_url: String,
})

const messageForm = useForm({ body: '' })
const messagesEl  = ref(null)

function sendMessage() {
    if (!messageForm.body.trim()) return
    messageForm.post(props.conversation.store_url, {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset()
            nextTick(() => { messagesEl.value?.scrollTo({ top: 999999, behavior: 'smooth' }) })
        },
    })
}

function convTitle() {
    if (props.conversation.name) return props.conversation.name
    return props.conversation.participants[0]?.name ?? 'Conversation'
}

onMounted(() => {
    messagesEl.value?.scrollTo({ top: 999999 })
})
</script>

<template>
    <AppLayout :title="convTitle()">
        <div class="page-header">
            <div class="flex items-center gap-3">
                <Link :href="back_url" class="btn-ghost btn-sm"><i class="bi bi-arrow-left"></i></Link>
                <div class="flex -space-x-1.5">
                    <img v-for="p in conversation.participants.slice(0,3)" :key="p.id"
                         :src="p.avatar_url" class="w-8 h-8 rounded-full ring-2 ring-white dark:ring-slate-900" alt="">
                </div>
                <div>
                    <h1 class="page-title mb-0">{{ convTitle() }}</h1>
                    <p class="text-xs text-slate-400">{{ conversation.participants.length }} {{ conversation.participants.length === 1 ? 'person' : 'people' }}</p>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div ref="messagesEl" class="card overflow-y-auto max-h-[60vh] mb-4 p-4 space-y-4">
            <!-- Pagination -->
            <div v-if="messages.last_page > 1" class="flex gap-1 justify-center mb-2">
                <template v-for="link in messages.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-2 py-0.5 rounded text-xs"
                          :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-400 hover:bg-slate-100'" v-html="link.label" />
                </template>
            </div>

            <div v-if="messages.data.length === 0" class="py-10 text-center text-sm text-slate-400">
                No messages yet. Send the first one!
            </div>
            <div v-for="msg in [...messages.data].reverse()" :key="msg.id" class="flex items-start gap-3">
                <img v-if="msg.user" :src="msg.user.avatar_url" class="w-8 h-8 rounded-full flex-shrink-0 mt-0.5" alt="">
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ msg.user?.name ?? 'Deleted user' }}</span>
                        <span class="text-[10px] text-slate-400">{{ msg.created_at }}</span>
                    </div>
                    <p v-if="!msg.deleted_at" class="text-sm text-slate-700 dark:text-slate-300 mt-0.5">{{ msg.body }}</p>
                    <p v-else class="text-xs text-slate-400 italic mt-0.5">Message deleted {{ msg.deleted_at }}</p>
                </div>
            </div>
        </div>

        <!-- Compose -->
        <form @submit.prevent="sendMessage" class="card p-3 flex gap-2">
            <input v-model="messageForm.body" type="text" class="form-input flex-1" placeholder="Type a message…"
                   @keydown.enter.prevent="sendMessage">
            <button type="submit" class="btn-primary" :disabled="messageForm.processing || !messageForm.body.trim()">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </AppLayout>
</template>
