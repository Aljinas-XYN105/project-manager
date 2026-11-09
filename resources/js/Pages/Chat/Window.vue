<script setup>
import { ref, nextTick, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ChatPopupLayout from '@/Layouts/ChatPopupLayout.vue'

const props = defineProps({
    conversation: Object,
    messages: Object,
    users: Array,
})

const page = usePage()
const authId = computed(() => page.props.auth?.user?.id)
const body = ref('')
const msgList = ref(null)
const sending = ref(false)

onMounted(() => scrollToBottom())

function scrollToBottom() {
    nextTick(() => { if (msgList.value) msgList.value.scrollTop = msgList.value.scrollHeight })
}

function convTitle() {
    if (props.conversation.name) return props.conversation.name
    return props.conversation.participants[0]?.name ?? 'Chat'
}

function convOther() {
    return props.conversation.participants.find(p => p.id !== authId.value) ?? props.conversation.participants[0]
}

async function sendMessage() {
    if (!body.value.trim() || sending.value) return
    sending.value = true
    const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
    try {
        await fetch(props.conversation.store_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(csrfToken || ''),
            },
            body: JSON.stringify({ body: body.value }),
        })
        body.value = ''
        scrollToBottom()
    } catch (e) {}
    sending.value = false
}

function popOut() {
    const url = window.location.href
    window.open(url, 'chat-window', 'width=680,height=680,toolbar=0,menubar=0')
}
</script>

<template>
    <ChatPopupLayout>
        <div style="display:flex;flex-direction:column;height:100vh;overflow:hidden;background:var(--ct-bg);">

            <!-- Header -->
            <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--ct-header-bg);border-bottom:1px solid var(--ct-header-border);flex-shrink:0;">
                <div style="position:relative;flex-shrink:0;">
                    <img v-if="!conversation.is_group" :src="convOther()?.avatar_url" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="">
                    <div v-else style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--ci-avatar-bg,#ede9fe);">
                        <i class="bi bi-people-fill" style="color:var(--ci-avatar-fg,#7c3aed);font-size:14px;"></i>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:600;color:var(--ct-name);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ convTitle() }}</p>
                    <p v-if="conversation.is_group" style="font-size:11px;color:var(--ct-time);">{{ conversation.participants.length }} members</p>
                    <p v-else style="font-size:11px;color:var(--ct-time);">{{ convOther()?.availability || 'offline' }}</p>
                </div>
                <button @click="popOut" style="border:none;background:none;cursor:pointer;padding:4px;color:var(--ct-action-btn);" title="Open in full window">
                    <i class="bi bi-box-arrow-up-right" style="font-size:14px;"></i>
                </button>
            </div>

            <!-- Messages -->
            <div ref="msgList" style="flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:6px;">
                <template v-if="messages?.data?.length">
                    <div v-for="m in [...messages.data].reverse()" :key="m.id"
                         :style="`display:flex;gap:8px;${m.user?.id === authId ? 'flex-direction:row-reverse' : ''}`">
                        <img v-if="m.user && m.user.id !== authId" :src="m.user.avatar_url"
                             style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;align-self:flex-end;" alt="">
                        <div :style="`max-width:70%;`">
                            <div v-if="!m.deleted_at"
                                 :style="`padding:7px 11px;border-radius:14px;font-size:13.5px;word-break:break-word;
                                 ${m.user?.id === authId
                                     ? 'background:var(--ct-bubble-out-bg);color:var(--ct-bubble-out-fg);border-radius:14px 14px 2px 14px'
                                     : 'background:var(--ct-bubble-in-bg);color:var(--ct-bubble-in-fg);border:1px solid var(--ct-bubble-in-border);border-radius:14px 14px 14px 2px'}`">
                                {{ m.body }}
                            </div>
                            <div v-else style="font-size:12px;font-style:italic;color:var(--ct-time);">Message deleted</div>
                            <div style="font-size:10px;color:var(--ct-time);margin-top:2px;" :style="m.user?.id === authId ? 'text-align:right' : ''">{{ m.created_at }}</div>
                        </div>
                    </div>
                </template>
                <div v-else style="text-align:center;padding:32px;color:var(--ct-time);font-size:13px;">No messages yet. Say hello!</div>
            </div>

            <!-- Compose -->
            <div style="padding:10px 12px;background:var(--ct-compose-bg);border-top:1px solid var(--ct-compose-border);flex-shrink:0;">
                <div style="display:flex;gap:8px;align-items:flex-end;">
                    <textarea v-model="body" rows="1" placeholder="Type a message…" @keydown.enter.exact.prevent="sendMessage"
                              style="flex:1;background:var(--ct-input-bg);border:1px solid var(--ct-input-border);border-radius:12px;padding:8px 12px;font-size:13.5px;color:var(--ct-name);outline:none;resize:none;font-family:inherit;line-height:1.4;"></textarea>
                    <button @click="sendMessage" :disabled="sending || !body.trim()"
                            style="width:36px;height:36px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;background:var(--ct-send-bg);color:white;flex-shrink:0;"
                            :style="(sending || !body.trim()) ? 'opacity:.5;cursor:not-allowed' : ''">
                        <i class="bi bi-send-fill" style="font-size:13px;"></i>
                    </button>
                </div>
            </div>
        </div>
    </ChatPopupLayout>
</template>

<style>
:root {
    --ct-bg: #f1f5f9; --ct-header-bg: #ffffff; --ct-header-border: #e2e8f0;
    --ct-bubble-out-bg: #7c3aed; --ct-bubble-out-fg: #ffffff;
    --ct-bubble-in-bg: #ffffff; --ct-bubble-in-fg: #1e293b; --ct-bubble-in-border: #e2e8f0;
    --ct-time: #94a3b8; --ct-compose-bg: #ffffff; --ct-compose-border: #e2e8f0;
    --ct-input-bg: #f8fafc; --ct-input-border: #e2e8f0;
    --ct-action-btn: #64748b; --ct-action-hover: #7c3aed; --ct-send-bg: #7c3aed;
    --ct-name: #1e293b; --ct-preview: #64748b;
    --ct-online: #22c55e; --ct-offline: #94a3b8;
}
.dark {
    --ct-bg: #0f172a; --ct-header-bg: #1e293b; --ct-header-border: #334155;
    --ct-bubble-out-bg: #6d28d9; --ct-bubble-out-fg: #f1f5f9;
    --ct-bubble-in-bg: #1e293b; --ct-bubble-in-fg: #e2e8f0; --ct-bubble-in-border: #334155;
    --ct-time: #64748b; --ct-compose-bg: #1e293b; --ct-compose-border: #334155;
    --ct-input-bg: #0f172a; --ct-input-border: #334155;
    --ct-action-btn: #64748b; --ct-action-hover: #a78bfa; --ct-send-bg: #7c3aed;
    --ct-name: #f1f5f9; --ct-preview: #94a3b8;
    --ct-online: #22c55e; --ct-offline: #475569;
}
html, body { height: 100%; overflow: hidden; }
</style>
