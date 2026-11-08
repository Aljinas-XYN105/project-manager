<script setup>
import { ref, nextTick, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    conversation: Object,
    messages: Object,
    users: Array,
    back_url: String,
})

const page = usePage()
const authId = computed(() => page.props.auth?.user?.id)

const body = ref('')
const thread = ref(null)
const sending = ref(false)

const msgList = computed(() => [...(props.messages.data ?? [])].reverse())

function convTitle() {
    if (props.conversation.name) return props.conversation.name
    return props.conversation.other?.name ?? props.conversation.participants[0]?.name ?? 'Chat'
}

function senderColorPalette() {
    return ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#be185d']
}
function senderColor(userId) {
    const pal = senderColorPalette()
    return pal[(userId ?? 0) % pal.length]
}

function isDifferentDay(a, b) {
    return a?.created_date !== b?.created_date
}

function formatDateSep(msg) {
    if (!msg.created_date) return ''
    const d = new Date(msg.created_date + 'T12:00:00')
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(today.getDate() - 1)
    if (d.toDateString() === today.toDateString()) return 'Today'
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday'
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
}

function csrfToken() {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '')
}

async function sendMessage() {
    if (!body.value.trim() || sending.value) return
    sending.value = true
    try {
        await fetch(props.conversation.store_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ body: body.value }),
        })
        body.value = ''
        scrollToBottom()
    } catch (e) {}
    finally { sending.value = false }
}

async function deleteMessage(msg) {
    if (!confirm('Delete this message?')) return
    try {
        await fetch(msg.destroy_url, { method: 'DELETE', headers: { 'X-XSRF-TOKEN': csrfToken() } })
        // Reload to reflect deletion
        window.location.reload()
    } catch (e) {}
}

function scrollToBottom() {
    nextTick(() => { if (thread.value) thread.value.scrollTop = thread.value.scrollHeight })
}

onMounted(() => scrollToBottom())
</script>

<template>
    <AppLayout :title="convTitle()">
        <style>
        .ct-thread-wrap {
            display: flex; flex-direction: column; overflow: hidden;
            background: var(--ct-bg);
        }
        .ct-header-bar {
            background: var(--ct-header-bg);
            border-bottom: 1px solid var(--ct-header-border);
            padding: 10px 16px;
            display: flex; align-items: center; gap: 12px;
            flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .ct-messages-area {
            flex: 1; overflow-y: auto; padding: 16px 16px 8px;
            display: flex; flex-direction: column; gap: 2px;
            scroll-behavior: smooth;
        }
        .ct-date-sep {
            display: flex; align-items: center; gap: 10px; margin: 10px 0;
        }
        .ct-date-sep::before, .ct-date-sep::after {
            content: ''; flex: 1; height: 1px; background: var(--ct-sep-line);
        }
        .ct-date-sep span {
            font-size: 11px; font-weight: 600; color: var(--ct-sep-text);
            padding: 2px 10px; white-space: nowrap;
            background: var(--ct-bg); border-radius: 10px;
            border: 1px solid var(--ct-sep-line);
        }
        .ct-msg-row { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 1px; }
        .ct-msg-row.out { flex-direction: row-reverse; }
        .ct-bubble-wrap {
            display: flex; flex-direction: column; max-width: 68%;
        }
        .ct-bubble-wrap.out { align-items: flex-end; }
        .ct-bubble-wrap.in { align-items: flex-start; }
        .ct-bubble {
            padding: 10px 13px 7px; line-height: 1.5;
            font-size: 14px; word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,.08); position: relative;
        }
        .ct-bubble.out {
            background: var(--ct-bubble-out-bg); color: var(--ct-bubble-out-fg);
            border-radius: 18px 18px 4px 18px;
        }
        .ct-bubble.in {
            background: var(--ct-bubble-in-bg); color: var(--ct-bubble-in-fg);
            border-radius: 18px 18px 18px 4px;
            border: 1px solid var(--ct-bubble-in-border);
        }
        .ct-bubble-time {
            font-size: 10px; opacity: .65; white-space: nowrap;
            display: flex; align-items: center; gap: 4px;
            justify-content: flex-end; margin-top: 3px;
        }
        .ct-bubble.out .ct-bubble-time { color: rgba(255,255,255,.8); }
        .ct-bubble.in .ct-bubble-time { color: var(--ct-time); }
        .ct-sender-name { font-size: 11px; font-weight: 700; margin-bottom: 2px; margin-left: 2px; }
        .ct-deleted { font-style: italic; opacity: .5; font-size: 13px; }
        .ct-btn-icon-ct {
            width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--ct-action-btn); background: none;
            font-size: 16px; flex-shrink: 0; transition: color .15s, background .15s;
        }
        .ct-btn-icon-ct:hover { color: var(--ct-action-hover); background: rgba(124,58,237,.08); }
        .ct-delete-btn-ct {
            opacity: 0; background: none; border: none; cursor: pointer;
            color: var(--ct-time); font-size: 12px; padding: 2px 4px;
            border-radius: 4px; transition: opacity .15s, color .15s;
        }
        .ct-msg-row:hover .ct-delete-btn-ct { opacity: 1; }
        .ct-delete-btn-ct:hover { color: #ef4444; }
        .ct-status-dot {
            width: 10px; height: 10px; border-radius: 50%;
            border: 2px solid var(--ct-header-bg);
            position: absolute; bottom: 0; right: 0;
        }
        .ct-status-dot.online { background: var(--ct-online); }
        .ct-status-dot.offline { background: var(--ct-offline); }
        .ct-compose-bar {
            background: var(--ct-compose-bg);
            border-top: 1px solid var(--ct-compose-border);
            padding: 10px 12px 12px;
            flex-shrink: 0;
        }
        .ct-input-wrap-bar {
            display: flex; align-items: center; gap: 8px;
            background: var(--ct-input-bg);
            border: 1.5px solid var(--ct-input-border);
            border-radius: 16px; padding: 6px 6px 6px 14px;
            transition: border-color .15s;
        }
        .ct-input-wrap-bar:focus-within { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
        .ct-textarea-bar {
            flex: 1; background: none; border: none; outline: none;
            font-size: 14px; color: var(--ct-name);
            font-family: inherit;
        }
        .ct-send-btn {
            width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            background: var(--ct-send-bg); color: white; font-size: 15px; flex-shrink: 0;
            transition: transform .12s, box-shadow .12s, opacity .15s;
            box-shadow: 0 2px 8px rgba(124,58,237,.4);
        }
        .ct-send-btn:hover:not(:disabled) { transform: scale(1.08); box-shadow: 0 4px 14px rgba(124,58,237,.5); }
        .ct-send-btn:disabled { opacity: .4; cursor: not-allowed; }
        </style>

        <!-- Full-height thread -->
        <div class="ct-thread-wrap -mt-6 -mx-4 lg:-mx-5" style="height:calc(100vh - 7rem)">

            <!-- Header -->
            <div class="ct-header-bar">
                <a :href="back_url" class="ct-btn-icon-ct flex-shrink-0" title="Back" style="margin-right:-4px">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div class="relative flex-shrink-0">
                    <template v-if="!conversation.is_group">
                        <img :src="conversation.other?.avatar_url" class="w-10 h-10 rounded-full object-cover" alt="">
                        <span class="ct-status-dot" :class="conversation.other?.is_online ? 'online' : 'offline'"></span>
                    </template>
                    <div v-else class="w-10 h-10 rounded-2xl flex items-center justify-center"
                         style="background: linear-gradient(135deg,#7c3aed,#6d28d9)">
                        <i class="bi bi-people-fill text-white text-sm"></i>
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-bold truncate" style="font-size:15px;color:var(--ct-name)">{{ convTitle() }}</p>
                    <p class="text-xs" :class="conversation.other?.is_online ? 'text-green-500' : ''"
                       style="color:var(--ct-preview)">
                        <template v-if="!conversation.is_group">{{ conversation.other?.status_label }}</template>
                        <template v-else>{{ conversation.member_count }} members</template>
                    </p>
                </div>

                <button v-if="!conversation.is_group" type="button"
                        @click="() => window.open(`${conversation.popup_url}?open=${conversation.id}`, 'chatpopup', 'width=820,height=680,resizable=yes,menubar=no,toolbar=no')"
                        class="ct-btn-icon-ct flex-shrink-0" title="Open in window">
                    <i class="bi bi-arrows-angle-expand" style="font-size:11px"></i>
                </button>
            </div>

            <!-- Messages -->
            <div ref="thread" class="ct-messages-area">
                <!-- Load earlier -->
                <div v-if="messages.last_page > 1 && messages.current_page < messages.last_page"
                     class="text-center mb-3">
                    <a :href="messages.prev_page_url"
                       class="text-xs px-3 py-1.5 rounded-full inline-flex items-center gap-1.5 transition-colors"
                       style="background:var(--ct-bubble-in-bg);border:1px solid var(--ct-bubble-in-border);color:var(--ct-preview)">
                        <i class="bi bi-arrow-up"></i> Load earlier messages
                    </a>
                </div>

                <!-- Empty state -->
                <div v-if="msgList.length === 0" class="flex-1 flex items-center justify-center py-20 text-center">
                    <div>
                        <i class="bi bi-chat-dots text-4xl block mb-3" style="color:var(--ct-time)"></i>
                        <p style="font-size:13px;color:var(--ct-time)">No messages yet. Send the first one!</p>
                    </div>
                </div>

                <!-- Message rows -->
                <template v-for="(msg, idx) in msgList" :key="msg.id">
                    <!-- Date separator -->
                    <div v-if="idx === 0 || isDifferentDay(msgList[idx-1], msg)" class="ct-date-sep my-3">
                        <span>{{ formatDateSep(msg) }}</span>
                    </div>

                    <div class="ct-msg-row" :class="msg.is_mine ? 'out' : 'in'">
                        <!-- Avatar (others only, last in a run) -->
                        <div class="flex flex-col justify-end flex-shrink-0 w-7">
                            <img v-if="!msg.is_mine && (idx === msgList.length - 1 || msgList[idx+1]?.user?.id !== msg.user?.id)"
                                 :src="msg.user?.avatar_url"
                                 class="w-7 h-7 rounded-full object-cover" alt="">
                        </div>

                        <div class="ct-bubble-wrap" :class="msg.is_mine ? 'out' : 'in'">
                            <!-- Sender name in group -->
                            <div v-if="conversation.is_group && !msg.is_mine && (idx === 0 || msgList[idx-1]?.user?.id !== msg.user?.id)"
                                 class="ct-sender-name"
                                 :style="'color:' + senderColor(msg.user?.id)">
                                {{ msg.user?.name }}
                            </div>

                            <div class="flex items-end gap-1" :class="msg.is_mine ? 'flex-row-reverse' : ''">
                                <div class="ct-bubble" :class="msg.is_mine ? 'out' : 'in'">
                                    <template v-if="!msg.is_deleted">
                                        <div>{{ msg.body }}</div>
                                        <div class="ct-bubble-time">
                                            <span>{{ msg.created_at }}</span>
                                            <i v-if="msg.is_mine" class="bi bi-check2" style="font-size:11px"></i>
                                        </div>
                                    </template>
                                    <span v-else class="ct-deleted">
                                        <i class="bi bi-slash-circle"></i> Message deleted
                                    </span>
                                </div>

                                <!-- Delete button -->
                                <button v-if="msg.is_mine && !msg.is_deleted"
                                        @click="deleteMessage(msg)"
                                        class="ct-delete-btn-ct" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Compose -->
            <div class="ct-compose-bar">
                <div class="ct-input-wrap-bar">
                    <textarea v-model="body" rows="1" placeholder="Type a message…"
                              class="ct-textarea-bar"
                              @keydown.enter.exact.prevent="sendMessage"></textarea>
                    <button @click="sendMessage" :disabled="sending || !body.trim()" class="ct-send-btn">
                        <i class="bi bi-send-fill" style="font-size:13px"></i>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
