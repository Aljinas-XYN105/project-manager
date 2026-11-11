<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    conversation: Object,
    messages:     Object,
    users:        Array,
    back_url:     String,
})

const page   = usePage()
const authId = computed(() => page.props.auth?.user?.id)

// ── Message list — API returns oldest-first; newest is last in the array (bottom)
const messages = ref(
    (props.messages.data ?? [])
        .map(m => ({ ...m, deleted: !!(m.deleted_at || m.is_deleted) }))
)

// ── Compose state ─────────────────────────────────────────────
const compose      = ref(null)      // contenteditable ref
const draft        = ref('')
const pendingFiles = ref([])
const sending      = ref(false)

// ── Mention state ─────────────────────────────────────────────
const mentionOpen    = ref(false)
const mentionResults = ref([])
const mentionIndex   = ref(0)
const allMentions    = ref([])

// ── Read receipts ─────────────────────────────────────────────
const otherLastReadAt = ref(props.conversation.other_last_read_at ? new Date(props.conversation.other_last_read_at) : null)

function isRead(msg) {
    if (!isMine(msg) || props.conversation.is_group) return false
    if (msg.optimistic) return false
    if (!otherLastReadAt.value) return false
    const ts = msg.created_at_iso ? new Date(msg.created_at_iso) : (msg.created_at ? new Date(msg.created_at) : null)
    return ts && ts <= otherLastReadAt.value
}

function callMarkRead() {
    if (!props.conversation.mark_read_url) return
    navigator.sendBeacon
        ? navigator.sendBeacon(props.conversation.mark_read_url, (() => { const f = new FormData(); f.append('_token', csrfMeta()); return f })())
        : fetch(props.conversation.mark_read_url, { method:'POST', headers:{'X-CSRF-TOKEN':csrfMeta()} })
}

// ── Typing state ──────────────────────────────────────────────
const typingUsers  = ref([])
const typingTimers = {}
let   whisperTimer = null

// ── Group info modal state ─────────────────────────────────────
const groupInfoOpen  = ref(false)
const groupMembers   = ref([...(props.conversation.participants ?? [])])
const groupName      = ref(props.conversation.name ?? 'Group chat')
const groupNameDraft = ref('')
const editingName    = ref(false)
const nameInput      = ref(null)
const addSearch      = ref('')
const addOpen        = ref(false)

const addResults = computed(() => {
    const q   = addSearch.value.toLowerCase().trim()
    const ids = new Set(groupMembers.value.map(m => m.id))
    return (props.users ?? [])
        .filter(u => !ids.has(u.id) && (!q || u.name.toLowerCase().includes(q) || (u.job_title ?? '').toLowerCase().includes(q)))
        .slice(0, 6)
})

function convTitle() {
    if (props.conversation.is_group) return groupName.value || props.conversation.name || 'Group chat'
    return props.conversation.other?.name ?? props.conversation.participants?.[0]?.name ?? 'Chat'
}

// ── Helpers ───────────────────────────────────────────────────
function csrfToken() {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')
}

function csrfMeta() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? csrfToken()
}

const SENDER_COLORS = ['#7c3aed','#0891b2','#059669','#d97706','#dc2626','#c026d3','#2563eb']
const colorMap = {}
function senderColor(uid) {
    if (!colorMap[uid]) colorMap[uid] = SENDER_COLORS[Object.keys(colorMap).length % SENDER_COLORS.length]
    return colorMap[uid]
}

function isMine(msg) { return msg.is_mine || msg.user?.id === authId.value }

function isDifferentDay(a, b) {
    if (!a || !b) return false
    // created_date is "YYYY-MM-DD" for initial page messages
    if (a.created_date && b.created_date) return a.created_date !== b.created_date
    // realtime messages carry ISO created_at
    const dA = msgDate(a), dB = msgDate(b)
    if (!dA || !dB) return false
    return dA.toDateString() !== dB.toDateString()
}

function msgDate(msg) {
    if (msg.created_date) return new Date(msg.created_date)
    if (!msg.created_at) return null
    const d = new Date(msg.created_at)
    return isNaN(d.getTime()) ? null : d
}

function formatDateSep(msg) {
    const d = msgDate(msg)
    if (!d) return msg.created_date ?? ''
    const now = new Date()
    const ys  = new Date(now); ys.setDate(now.getDate() - 1)
    if (d.toDateString() === now.toDateString()) return 'Today'
    if (d.toDateString() === ys.toDateString())  return 'Yesterday'
    return d.toLocaleDateString([], { weekday:'short', month:'short', day:'numeric' })
}

function formatTime(val) {
    if (!val) return ''
    const d = new Date(val)
    if (isNaN(d.getTime())) return val   // already a formatted string like "10:30 AM"
    return d.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' })
}

function renderBody(body) {
    if (!body) return ''
    const esc = body
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\n/g,'<br>')
    let out = esc.replace(/@\[([^\]]+)\]\(task:(\d+)\)/g,
        (_, title, id) => `<a href="/tasks/${id}" target="_blank" class="ct-chip task"><i class="bi bi-check-square-fill" style="font-size:10px"></i>${title}</a>`)
    out = out.replace(/@\[([^\]]+)\]\(subtask:(\d+)\)/g,
        (_, title, id) => `<a href="/tasks/${id}" target="_blank" class="ct-chip subtask"><i class="bi bi-diagram-3-fill" style="font-size:10px"></i>${title}</a>`)
    out = out.replace(/@\[([^\]]+)\]\(member:(\d+)\)/g,
        (_, name) => `<span class="ct-chip member"><i class="bi bi-person-fill" style="font-size:10px"></i>${name}</span>`)
    return out
}

// ── Scroll ────────────────────────────────────────────────────
const thread = ref(null)
const bottom = ref(null)
function scrollToBottom(smooth = true) {
    nextTick(() => {
        const el = thread.value
        if (!el) return
        if (smooth) {
            el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' })
        } else {
            el.scrollTop = el.scrollHeight
        }
    })
}

// ── Message notification sound ────────────────────────────────
function playMsgSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        ;[[520,0,0.08],[660,0.09,0.1]].forEach(([freq,start,dur]) => {
            const o = ctx.createOscillator(), g = ctx.createGain()
            o.connect(g); g.connect(ctx.destination)
            o.type = 'sine'; o.frequency.value = freq
            g.gain.setValueAtTime(0, ctx.currentTime + start)
            g.gain.linearRampToValueAtTime(0.15, ctx.currentTime + start + 0.02)
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + dur)
            o.start(ctx.currentTime + start)
            o.stop(ctx.currentTime + start + dur + 0.05)
        })
    } catch(e) {}
}

// ── contenteditable helpers ───────────────────────────────────
function extractText() {
    const el = compose.value
    if (!el) return ''
    function walk(node) {
        let s = ''
        for (const child of node.childNodes) {
            if (child.nodeType === Node.TEXT_NODE) {
                s += child.textContent.replace(/ /g,' ')
            } else if (child.nodeName === 'BR') {
                s += '\n'
            } else if (child.nodeName === 'SPAN' && child.dataset.mention) {
                const [kind, id] = child.dataset.mention.split(':')
                s += `@[${child.dataset.mentionName}](${kind}:${id})`
            } else if (child.nodeName === 'DIV') {
                s += '\n' + walk(child)
            } else {
                s += walk(child)
            }
        }
        return s
    }
    return walk(el).replace(/\n+$/, '')
}

function onInput() {
    draft.value = extractText()
    const sel = window.getSelection()
    if (!sel || !sel.rangeCount) { mentionOpen.value = false; return }
    const focusNode = sel.focusNode
    if (!focusNode || focusNode.nodeType !== Node.TEXT_NODE) { mentionOpen.value = false; return }
    const textInNode = focusNode.textContent.slice(0, sel.focusOffset)
    const match = textInNode.match(/@([\w ]*)$/)
    if (match) {
        const q = match[1].toLowerCase().trim()
        mentionResults.value = allMentions.value.filter(item => {
            if (!q) return true
            return item.title.toLowerCase().includes(q) ||
                (item.project_name||'').toLowerCase().includes(q) ||
                (item.parent_name ||'').toLowerCase().includes(q) ||
                (item.subtitle    ||'').toLowerCase().includes(q)
        }).slice(0, 8)
        mentionOpen.value  = mentionResults.value.length > 0
        mentionIndex.value = 0
    } else {
        mentionOpen.value = false
    }
}

function onKeydownCompose(e) {
    if (mentionOpen.value) {
        if (e.key === 'ArrowUp')   { e.preventDefault(); mentionIndex.value = Math.max(0, mentionIndex.value - 1); return }
        if (e.key === 'ArrowDown') { e.preventDefault(); mentionIndex.value = Math.min(mentionResults.value.length - 1, mentionIndex.value + 1); return }
        if (e.key === 'Tab' || e.key === 'Enter') {
            const item = mentionResults.value[mentionIndex.value]
            if (item) { e.preventDefault(); insertMention(item); return }
        }
        if (e.key === 'Escape') { mentionOpen.value = false; return }
    }
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
        return
    }
    // Echo typing whisper
    if (window.Echo) {
        clearTimeout(whisperTimer)
        whisperTimer = setTimeout(() => {
            window.Echo.private(`conversation.${props.conversation.id}`)
                .whisper('typing', {
                    id: authId.value,
                    name: document.querySelector('meta[name="user-name"]')?.content ?? '',
                    avatar_url: document.querySelector('meta[name="user-avatar"]')?.content ?? '',
                })
        }, 500)
    }
}

function onPaste(e) {
    e.preventDefault()
    const text = (e.clipboardData || window.clipboardData).getData('text/plain')
    document.execCommand('insertText', false, text)
    draft.value = extractText()
}

function onBlurCompose() {
    nextTick(() => { mentionOpen.value = false })
}

function insertMention(item) {
    const el = compose.value
    if (!el) return
    el.focus()
    const sel = window.getSelection()
    if (!sel || !sel.rangeCount) { mentionOpen.value = false; return }
    const focusNode = sel.focusNode
    if (!focusNode || focusNode.nodeType !== Node.TEXT_NODE) { mentionOpen.value = false; return }
    const nodeText   = focusNode.textContent
    const focusOffset = sel.focusOffset
    const atIdx = nodeText.lastIndexOf('@', focusOffset - 1)
    if (atIdx === -1) { mentionOpen.value = false; return }
    const before = nodeText.slice(0, atIdx)
    const after  = nodeText.slice(focusOffset)

    const chip = document.createElement('span')
    chip.className = `ct-chip ${item.kind}`
    chip.contentEditable = 'false'
    chip.dataset.mention = `${item.kind}:${item.id}`
    chip.dataset.mentionName = item.title
    chip.style.userSelect = 'none'
    const icon = document.createElement('i')
    icon.className = item.kind === 'member'  ? 'bi bi-person-fill' :
                     item.kind === 'subtask' ? 'bi bi-diagram-3-fill' :
                                               'bi bi-check-square-fill'
    icon.style.cssText = 'font-size:10px;margin-right:3px'
    chip.appendChild(icon)
    chip.appendChild(document.createTextNode(item.title))

    const parent = focusNode.parentNode
    focusNode.textContent = before
    parent.insertBefore(chip, focusNode.nextSibling)
    const spaceNode = document.createTextNode(' ' + after)
    parent.insertBefore(spaceNode, chip.nextSibling)

    const range = document.createRange()
    range.setStart(spaceNode, 1)
    range.collapse(true)
    sel.removeAllRanges()
    sel.addRange(range)

    mentionOpen.value = false
    draft.value = extractText()
}

function pickFiles(e) {
    pendingFiles.value = [...pendingFiles.value, ...Array.from(e.target.files)]
    e.target.value = ''
}

// ── Send message ──────────────────────────────────────────────
async function sendMessage() {
    const bodyText = extractText().trim()
    if (!bodyText && pendingFiles.value.length === 0) return
    if (sending.value) return
    sending.value = true
    mentionOpen.value = false

    const form = new FormData()
    if (bodyText) form.append('body', bodyText)
    pendingFiles.value.forEach(f => form.append('attachments[]', f))

    const optimistic = {
        id: `opt_${Date.now()}`,
        body: bodyText,
        created_at: new Date().toISOString(),
        created_date: new Date().toDateString(),
        is_mine: true,
        user: {
            id: authId.value,
            name: document.querySelector('meta[name="user-name"]')?.content ?? 'You',
            avatar_url: document.querySelector('meta[name="user-avatar"]')?.content ?? '',
        },
        attachments: [],
        deleted: false,
        optimistic: true,
    }
    messages.value.push(optimistic)
    if (compose.value) compose.value.innerHTML = ''
    draft.value = ''
    pendingFiles.value = []
    scrollToBottom(true)

    try {
        const resp = await fetch(`/chat/${props.conversation.id}/messages`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfMeta(), 'Accept': 'application/json' },
            body: form,
        })
        const data = await resp.json()
        const idx = messages.value.findIndex(m => m.id === optimistic.id)
        if (idx !== -1) messages.value[idx] = { ...data, deleted: false }
    } catch(e) {
        messages.value = messages.value.filter(m => m.id !== optimistic.id)
    } finally {
        sending.value = false
        nextTick(() => compose.value?.focus())
    }
}

async function deleteMessage(msg) {
    if (!confirm('Delete this message for everyone?')) return
    await fetch(`/chat/messages/${msg.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfMeta() },
    })
    msg.body = ''; msg.deleted = true
}

// ── Group info modal ──────────────────────────────────────────
function openGroupInfo() { if (props.conversation.is_group) groupInfoOpen.value = true }

function startEditName() {
    groupNameDraft.value = groupName.value
    editingName.value = true
    nextTick(() => nameInput.value?.focus())
}

async function saveName() {
    const name = groupNameDraft.value.trim()
    if (!name || name === groupName.value) { editingName.value = false; return }
    const resp = await fetch(`/chat/${props.conversation.id}/name`, {
        method: 'PATCH',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrfMeta(), 'Accept':'application/json' },
        body: JSON.stringify({ name }),
    })
    if (resp.ok) {
        const data = await resp.json()
        groupName.value = data.name
    }
    editingName.value = false
}

async function addMember(user) {
    addSearch.value = ''; addOpen.value = false
    const resp = await fetch(`/chat/${props.conversation.id}/members`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrfMeta(), 'Accept':'application/json' },
        body: JSON.stringify({ user_id: user.id }),
    })
    if (resp.ok) {
        const conv = await resp.json()
        groupMembers.value = (conv.participants ?? []).map(p => ({
            id: p.id, name: p.name, avatar_url: p.avatar_url,
            is_online: p.is_online ?? false, job_title: p.job_title ?? '',
        }))
    }
}

async function removeMember(user) {
    if (!confirm(`Remove ${user.name} from the group?`)) return
    const resp = await fetch(`/chat/${props.conversation.id}/members/${user.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfMeta(), 'Accept': 'application/json' },
    })
    if (resp.ok) groupMembers.value = groupMembers.value.filter(m => m.id !== user.id)
}

// ── Escape key to close group modal ───────────────────────────
function handleDocKeydown(e) {
    if (e.key === 'Escape') groupInfoOpen.value = false
}

// ── Lifecycle ─────────────────────────────────────────────────
onMounted(() => {
    scrollToBottom(false)
    setTimeout(() => scrollToBottom(false), 80)
    nextTick(() => compose.value?.focus())

    // Load mention targets
    fetch('/api/tasks/brief', {
        headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': csrfMeta() }
    }).then(r => r.ok ? r.json() : {}).then(d => {
        allMentions.value = [...(d.members||[]), ...(d.tasks||[]), ...(d.subtasks||[])]
    }).catch(() => {})

    // Echo real-time
    if (window.Echo) {
        window.Echo.private(`conversation.${props.conversation.id}`)
            .listen('.message.sent', (data) => {
                if (data.user?.id === authId.value) return
                messages.value.push({ ...data, deleted: false })
                playMsgSound()
                scrollToBottom(true)
                // Mark read since we're looking at the conversation
                callMarkRead()
            })
            .listen('.message.deleted', (data) => {
                const msg = messages.value.find(m => m.id === data.id)
                if (msg) { msg.body = ''; msg.deleted = true }
            })
            .listen('.conversation.read', (data) => {
                // Other person read our messages — update their last_read_at
                if (data.user_id !== authId.value) {
                    otherLastReadAt.value = new Date(data.last_read_at)
                }
            })
            .listenForWhisper('typing', (data) => {
                if (data.id === authId.value) return
                if (!typingUsers.value.find(u => u.id === data.id)) typingUsers.value.push(data)
                clearTimeout(typingTimers[data.id])
                typingTimers[data.id] = setTimeout(() => {
                    typingUsers.value = typingUsers.value.filter(u => u.id !== data.id)
                }, 3000)
            })
    }

    document.addEventListener('keydown', handleDocKeydown)
})

function openChatPopup() {
    window.open(`${props.conversation.popup_url}?open=${props.conversation.id}`, 'chatpopup', 'width=820,height=680,resizable=yes,menubar=no,toolbar=no')
}

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleDocKeydown)
    if (window.Echo) {
        try { window.Echo.leave(`conversation.${props.conversation.id}`) } catch(e) {}
    }
})
</script>

<template>
    <AppLayout :title="convTitle()">
        <!-- Full-height thread -->
        <div class="ct-thread -mt-6 -mx-4 lg:-mx-5" style="height:calc(100vh - 7rem)">

            <!-- Header -->
            <div class="ct-header">
                <a :href="back_url" class="ct-btn-icon flex-shrink-0" title="Back" style="margin-right:-4px">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div class="relative flex-shrink-0">
                    <template v-if="!conversation.is_group">
                        <img :src="conversation.other?.avatar_url" class="w-10 h-10 rounded-full object-cover" alt="">
                        <span class="ct-dot" :class="conversation.other?.is_online ? 'online' : 'offline'"></span>
                    </template>
                    <div v-else class="w-10 h-10 rounded-2xl flex items-center justify-center"
                         style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                        <i class="bi bi-people-fill text-white text-sm"></i>
                    </div>
                </div>

                <div class="flex-1 min-w-0" :class="conversation.is_group ? 'cursor-pointer select-none' : ''"
                     @click="openGroupInfo">
                    <p class="font-bold truncate" :class="conversation.is_group ? 'hover:underline' : ''"
                       style="font-size:15px;color:var(--ct-name)">{{ convTitle() }}</p>
                    <p class="text-xs" style="color:var(--ct-preview)">
                        <template v-if="!conversation.is_group">
                            <span :class="conversation.other?.is_online ? 'text-green-500' : ''">
                                {{ conversation.other?.status_label }}
                            </span>
                        </template>
                        <template v-else>
                            <span v-if="typingUsers.length === 0">{{ groupMembers.length }} members</span>
                            <span v-else class="text-green-500">
                                {{ typingUsers.map(u => u.name.split(' ')[0]).join(', ') }} typing…
                            </span>
                        </template>
                    </p>
                </div>

                <button v-if="!conversation.is_group" type="button"
                        @click="openChatPopup"
                        class="ct-btn-icon flex-shrink-0" title="Open in window">
                    <i class="bi bi-arrows-angle-expand" style="font-size:11px"></i>
                </button>
            </div>

            <!-- Messages -->
            <div ref="thread" class="ct-messages">
                <!-- Load earlier -->
                <div v-if="messages?.last_page > 1" class="text-center mb-3">
                    <a :href="messages?.prev_page_url"
                       class="text-xs px-3 py-1.5 rounded-full inline-flex items-center gap-1.5 transition-colors"
                       style="background:var(--ct-bubble-in-bg);border:1px solid var(--ct-bubble-in-border);color:var(--ct-preview)">
                        <i class="bi bi-arrow-up"></i> Load earlier messages
                    </a>
                </div>

                <!-- Empty state -->
                <div v-if="messages.length === 0" class="flex-1 flex items-center justify-center py-20 text-center">
                    <div>
                        <i class="bi bi-chat-dots text-4xl block mb-3" style="color:var(--ct-time)"></i>
                        <p style="font-size:13px;color:var(--ct-time)">No messages yet. Send the first one!</p>
                    </div>
                </div>

                <!-- Message rows -->
                <template v-for="(msg, idx) in messages" :key="msg.id">
                    <!-- Date separator -->
                    <div v-if="idx === 0 || isDifferentDay(messages[idx-1], msg)" class="ct-sep my-3">
                        <span>{{ formatDateSep(msg) }}</span>
                    </div>

                    <div class="ct-row" :class="isMine(msg) ? 'out' : 'in'">
                        <!-- Avatar (others only, last in a run) -->
                        <div class="flex flex-col justify-end flex-shrink-0 w-7">
                            <img v-if="!isMine(msg) && (idx === messages.length - 1 || messages[idx+1]?.user?.id !== msg.user?.id)"
                                 :src="msg.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user?.name ?? 'U')}&size=28`"
                                 class="w-7 h-7 rounded-full object-cover" alt="">
                        </div>

                        <div class="flex flex-col" :class="isMine(msg) ? 'items-end' : 'items-start'"
                             style="max-width:72%">
                            <!-- Sender name in group -->
                            <div v-if="conversation.is_group && !isMine(msg) && (idx === 0 || messages[idx-1]?.user?.id !== msg.user?.id)"
                                 class="ct-sender-name"
                                 :style="'color:' + senderColor(msg.user?.id)">
                                {{ msg.user?.name }}
                            </div>

                            <div class="flex items-end gap-1" :class="isMine(msg) ? 'flex-row-reverse' : ''">
                                <div class="ct-bubble" :class="isMine(msg) ? 'out' : 'in'">
                                    <template v-if="!msg.deleted">
                                        <span v-html="renderBody(msg.body)"></span>
                                        <!-- Attachments -->
                                        <div v-if="msg.attachments?.length" class="mt-2 flex flex-wrap gap-1.5">
                                            <template v-for="att in msg.attachments" :key="att.id">
                                                <a v-if="att.is_image" :href="att.url" target="_blank" class="block">
                                                    <img :src="att.url" class="max-w-[200px] rounded-xl object-cover" alt="">
                                                </a>
                                                <a v-else :href="att.url" target="_blank"
                                                   class="ct-attach-pill" :class="isMine(msg) ? 'out' : ''">
                                                    <i class="bi bi-file-earmark text-xs"></i>
                                                    <span class="max-w-[140px] truncate">{{ att.original_name }}</span>
                                                </a>
                                            </template>
                                        </div>
                                        <div class="ct-bubble-time">
                                            <span>{{ msg.created_time || formatTime(msg.created_at) }}</span>
                                            <template v-if="isMine(msg)">
                                                <!-- single tick: optimistic (not yet confirmed by server) -->
                                                <i v-if="msg.optimistic" class="bi bi-check" style="font-size:11px;opacity:.7"></i>
                                                <!-- double green: read by recipient -->
                                                <i v-else-if="isRead(msg)" class="bi bi-check2-all" style="font-size:11px;color:#4ade80"></i>
                                                <!-- double grey: delivered to server -->
                                                <i v-else class="bi bi-check2-all" style="font-size:11px;opacity:.6"></i>
                                            </template>
                                        </div>
                                    </template>
                                    <span v-else class="ct-deleted">
                                        <i class="bi bi-slash-circle"></i> Message deleted
                                    </span>
                                </div>

                                <!-- Delete button -->
                                <button v-if="isMine(msg) && !msg.deleted"
                                        @click="deleteMessage(msg)"
                                        class="ct-delete-btn" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Typing indicator -->
                <div v-if="typingUsers.length > 0" class="ct-row in" style="margin-top:4px">
                    <div class="w-7 flex-shrink-0">
                        <img :src="typingUsers[0]?.avatar_url || `https://ui-avatars.com/api/?name=U`"
                             class="w-7 h-7 rounded-full object-cover" alt="">
                    </div>
                    <div class="ct-typing">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <div ref="bottom"></div>
            </div>

            <!-- Compose -->
            <div class="ct-compose">
                <!-- Pending file pills -->
                <div v-if="pendingFiles.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                    <span v-for="(f, i) in pendingFiles" :key="i" class="ct-attach-pill">
                        <i class="bi bi-paperclip text-xs"></i>
                        <span class="max-w-[120px] truncate">{{ f.name }}</span>
                        <button @click="pendingFiles.splice(i, 1)" type="button"
                                class="ml-1 opacity-60 hover:opacity-100">
                            <i class="bi bi-x text-xs"></i>
                        </button>
                    </span>
                </div>

                <!-- Mention popup + compose form -->
                <div class="relative">
                    <!-- Mention popup -->
                    <div v-if="mentionOpen && mentionResults.length > 0" class="ct-mention-popup">
                        <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide"
                             style="color:var(--ct-time);border-bottom:1px solid var(--ct-mention-border)">
                            Mention
                        </div>
                        <div v-for="(item, i) in mentionResults" :key="item.kind + '_' + item.id"
                             class="ct-mention-item" :class="i === mentionIndex ? 'active' : ''"
                             @mousedown.prevent="insertMention(item)">

                            <img v-if="item.kind === 'member' && item.avatar_url"
                                 :src="item.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" alt="">
                            <span v-else-if="item.kind === 'member'"
                                  class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0"
                                  style="background:var(--ct-chip-bg)">
                                <i class="bi bi-person-fill text-xs" style="color:var(--ct-chip-fg)"></i>
                            </span>
                            <span v-else-if="item.kind === 'task'"
                                  class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                  style="background:var(--ct-task-chip-bg)">
                                <i class="bi bi-check-square-fill text-xs" style="color:var(--ct-task-chip-fg)"></i>
                            </span>
                            <span v-else
                                  class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                  style="background:#f0fdf4">
                                <i class="bi bi-diagram-3-fill text-xs" style="color:#166534"></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium truncate" style="color:var(--ct-name)">{{ item.title }}</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0 capitalize"
                                          :style="item.kind === 'member'  ? 'background:var(--ct-chip-bg);color:var(--ct-chip-fg)' :
                                                  item.kind === 'subtask' ? 'background:#f0fdf4;color:#166534' :
                                                                            'background:var(--ct-task-chip-bg);color:var(--ct-task-chip-fg)'">
                                        {{ item.kind }}
                                    </span>
                                </div>
                                <div class="text-xs opacity-60 truncate" style="color:var(--ct-preview)">
                                    {{ item.subtitle || item.project_name || item.parent_name || '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="sendMessage">
                        <div class="ct-input-wrap">
                            <label class="ct-btn-icon" title="Attach files" style="margin-bottom:1px">
                                <i class="bi bi-paperclip"></i>
                                <input type="file" class="hidden" multiple @change="pickFiles($event)">
                            </label>

                            <div ref="compose"
                                 contenteditable="true"
                                 data-placeholder="Type a message… (@ to mention task, subtask or member)"
                                 class="ct-textarea"
                                 role="textbox"
                                 aria-multiline="true"
                                 spellcheck="true"
                                 @input="onInput"
                                 @keydown="onKeydownCompose"
                                 @paste.prevent="onPaste"
                                 @blur="onBlurCompose">
                            </div>

                            <button type="submit"
                                    :disabled="!draft.trim() && pendingFiles.length === 0"
                                    class="ct-send-btn">
                                <i class="bi bi-send-fill" style="font-size:13px;margin-left:1px"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Group info modal -->
        <Teleport to="body">
            <div v-if="conversation.is_group && groupInfoOpen"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background:rgba(15,23,42,.6);backdrop-filter:blur(4px)"
                 @click.self="groupInfoOpen = false">
                <div class="w-full max-w-md rounded-2xl overflow-hidden flex flex-col"
                     style="background:var(--ct-header-bg);border:1px solid var(--ct-header-border);box-shadow:0 25px 60px rgba(0,0,0,.3);max-height:85vh"
                     @click.stop>

                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-5 py-4 flex-shrink-0"
                         style="border-bottom:1px solid var(--ct-header-border)">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center"
                                 style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                                <i class="bi bi-people-fill text-white text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <div v-if="!editingName" class="flex items-center gap-1.5 cursor-pointer" @click="startEditName">
                                    <h2 class="font-bold text-sm truncate" style="color:var(--ct-name)">{{ groupName }}</h2>
                                    <i class="bi bi-pencil flex-shrink-0" style="font-size:10px;color:var(--ct-preview)"></i>
                                </div>
                                <form v-else @submit.prevent="saveName" class="flex items-center gap-1.5">
                                    <input ref="nameInput" type="text" v-model="groupNameDraft" maxlength="100"
                                           class="text-sm font-bold rounded-lg px-2 py-0.5 outline-none"
                                           style="background:var(--ct-input-bg);border:1.5px solid #7c3aed;color:var(--ct-name);min-width:0;width:160px"
                                           @keydown.escape="editingName = false">
                                    <button type="submit" class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0"
                                            style="background:#7c3aed;color:white">
                                        <i class="bi bi-check" style="font-size:12px"></i>
                                    </button>
                                    <button type="button" @click="editingName = false"
                                            class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0"
                                            style="background:var(--ct-bubble-in-bg);color:var(--ct-preview)">
                                        <i class="bi bi-x" style="font-size:12px"></i>
                                    </button>
                                </form>
                                <p class="text-xs" style="color:var(--ct-preview)">{{ groupMembers.length }} members</p>
                            </div>
                        </div>
                        <button @click="groupInfoOpen = false"
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                                style="color:var(--ct-preview)">
                            <i class="bi bi-x text-xl"></i>
                        </button>
                    </div>

                    <!-- Add member search -->
                    <div class="px-4 py-3 flex-shrink-0" style="border-bottom:1px solid var(--ct-header-border)">
                        <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:var(--ct-preview)">Add member</p>
                        <div class="relative">
                            <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ct-preview)"></i>
                            <input type="text" v-model="addSearch" placeholder="Search people to add…"
                                   class="w-full text-sm rounded-lg py-2 outline-none"
                                   style="background:var(--ct-input-bg);border:1.5px solid var(--ct-input-border);color:var(--ct-name);padding-left:28px;padding-right:12px"
                                   @focus="addOpen = true" @blur="nextTick(() => { addOpen = false })">
                            <div v-if="addOpen && addResults.length > 0"
                                 class="absolute left-0 right-0 top-full mt-1 rounded-xl overflow-hidden z-10"
                                 style="background:var(--ct-header-bg);border:1px solid var(--ct-header-border);box-shadow:0 8px 24px rgba(0,0,0,.18)">
                                <div v-for="u in addResults" :key="u.id"
                                     class="flex items-center gap-2.5 px-3 py-2 cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-slate-800"
                                     style="border-bottom:1px solid var(--ct-header-border)"
                                     @mousedown.prevent="addMember(u)">
                                    <img :src="u.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium truncate" style="color:var(--ct-name)">{{ u.name }}</div>
                                        <div class="text-xs truncate" style="color:var(--ct-preview)">{{ u.job_title }}</div>
                                    </div>
                                    <i class="bi bi-plus-circle text-sm flex-shrink-0" style="color:#7c3aed"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Members list -->
                    <div class="overflow-y-auto flex-1 py-1">
                        <p class="text-xs font-semibold uppercase tracking-wide px-4 pt-2 pb-1" style="color:var(--ct-preview)">Members</p>
                        <div v-for="m in groupMembers" :key="m.id" class="flex items-center gap-3 px-4 py-2.5">
                            <div class="relative flex-shrink-0">
                                <img :src="m.avatar_url" class="w-9 h-9 rounded-full object-cover">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2"
                                      :style="`background:${m.is_online ? '#22c55e' : 'var(--ct-preview)'};border-color:var(--ct-header-bg)`"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate" style="color:var(--ct-name)">{{ m.name }}</div>
                                <div class="text-xs truncate" style="color:var(--ct-preview)">{{ m.job_title || (m.is_online ? 'Online' : 'Offline') }}</div>
                            </div>
                            <span v-if="m.id === authId" class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                  style="background:var(--ct-chip-bg);color:var(--ct-chip-fg)">You</span>
                            <button v-else @click="removeMember(m)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    style="color:var(--ct-preview)" title="Remove from group">
                                <i class="bi bi-person-dash text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style>
/* ── Chat thread ───────────────────────────────────────────── */
:root {
    --ct-bg: transparent;
    --ct-header-bg: #ffffff;
    --ct-header-border: #e2e8f0;
    --ct-bubble-out-bg: #7c3aed;
    --ct-bubble-out-fg: #ffffff;
    --ct-bubble-in-bg: #ffffff;
    --ct-bubble-in-fg: #1e293b;
    --ct-bubble-in-border: #e2e8f0;
    --ct-time: #94a3b8;
    --ct-compose-bg: #ffffff;
    --ct-compose-border: #e2e8f0;
    --ct-input-bg: #f8fafc;
    --ct-input-border: #e2e8f0;
    --ct-action-btn: #64748b;
    --ct-action-hover: #7c3aed;
    --ct-send-bg: #7c3aed;
    --ct-sep-text: #94a3b8;
    --ct-sep-line: #e2e8f0;
    --ct-mention-bg: #ffffff;
    --ct-mention-hover: #f8fafc;
    --ct-mention-border: #e2e8f0;
    --ct-chip-bg: #ede9fe;
    --ct-chip-fg: #5b21b6;
    --ct-chip-border: #c4b5fd;
    --ct-task-chip-bg: #fef3c7;
    --ct-task-chip-fg: #92400e;
    --ct-task-chip-border: #fcd34d;
    --ct-deleted-fg: #94a3b8;
    --ct-online: #22c55e;
    --ct-offline: #94a3b8;
    --ct-name: #1e293b;
    --ct-preview: #64748b;
}
.dark {
    --ct-bg: #0f172a;
    --ct-header-bg: #1e293b;
    --ct-header-border: #334155;
    --ct-bubble-out-bg: #6d28d9;
    --ct-bubble-out-fg: #f1f5f9;
    --ct-bubble-in-bg: #1e293b;
    --ct-bubble-in-fg: #e2e8f0;
    --ct-bubble-in-border: #334155;
    --ct-time: #64748b;
    --ct-compose-bg: #1e293b;
    --ct-compose-border: #334155;
    --ct-input-bg: #0f172a;
    --ct-input-border: #334155;
    --ct-action-btn: #64748b;
    --ct-action-hover: #a78bfa;
    --ct-send-bg: #7c3aed;
    --ct-sep-text: #64748b;
    --ct-sep-line: #1e293b;
    --ct-mention-bg: #1e293b;
    --ct-mention-hover: #0f172a;
    --ct-mention-border: #334155;
    --ct-chip-bg: #2e1065;
    --ct-chip-fg: #c4b5fd;
    --ct-chip-border: #4c1d95;
    --ct-task-chip-bg: #1c1403;
    --ct-task-chip-fg: #fcd34d;
    --ct-task-chip-border: #78350f;
    --ct-deleted-fg: #475569;
    --ct-online: #22c55e;
    --ct-offline: #475569;
    --ct-name: #f1f5f9;
    --ct-preview: #94a3b8;
}

.ct-thread { display: flex; flex-direction: column; overflow: hidden; background: var(--ct-bg); }
.ct-header { background: var(--ct-header-bg); border-bottom: 1px solid var(--ct-header-border); padding: 10px 16px; display: flex; align-items: center; gap: 12px; flex-shrink: 0; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.ct-header-title { font-weight: 700; font-size: 15px; color: var(--ct-name); }
.ct-header-sub { font-size: 12px; color: var(--ct-preview); }
.ct-header-sub.online { color: var(--ct-online); }
.ct-dot { width: 10px; height: 10px; border-radius: 50%; border: 2px solid var(--ct-header-bg); position: absolute; bottom: 0; right: 0; }
.ct-dot.online { background: var(--ct-online); }
.ct-dot.offline { background: var(--ct-offline); }
.ct-dot.busy { background: #f59e0b; }
.ct-messages { flex: 1; overflow-y: auto; padding: 16px 16px 8px; display: flex; flex-direction: column; gap: 2px; }
.ct-messages > :first-child { margin-top: auto; }
.ct-sep { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
.ct-sep::before, .ct-sep::after { content: ''; flex: 1; height: 1px; background: var(--ct-sep-line); }
.ct-sep span { font-size: 11px; font-weight: 600; color: var(--ct-sep-text); padding: 2px 10px; white-space: nowrap; background: var(--ct-bg); border-radius: 10px; border: 1px solid var(--ct-sep-line); }
.ct-row { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 1px; }
.ct-row.out { flex-direction: row-reverse; }
.ct-bubble { max-width: 68%; min-width: 90px; padding: 7px 13px 24px; line-height: 1.5; font-size: 14px; word-break: break-word; box-shadow: 0 1px 2px rgba(0,0,0,.08); position: relative; }
.ct-bubble.out { background: var(--ct-bubble-out-bg); color: var(--ct-bubble-out-fg); border-radius: 18px 18px 4px 18px; }
.ct-bubble.in { background: var(--ct-bubble-in-bg); color: var(--ct-bubble-in-fg); border-radius: 18px 18px 18px 4px; border: 1px solid var(--ct-bubble-in-border); }
.ct-bubble-time { font-size: 10px; opacity: .65; white-space: nowrap; display: flex; align-items: center; gap: 4px; position: absolute; bottom: 6px; right: 11px; }
.ct-bubble.out .ct-bubble-time { color: rgba(255,255,255,.8); }
.ct-bubble.in .ct-bubble-time { color: var(--ct-time); }
.ct-sender-name { font-size: 11px; font-weight: 700; margin-bottom: 2px; margin-left: 2px; }
.ct-compose { background: var(--ct-compose-bg); border-top: 1px solid var(--ct-compose-border); padding: 10px 12px 12px; flex-shrink: 0; }
.ct-input-wrap { display: flex; align-items: center; gap: 8px; background: var(--ct-input-bg); border: 1.5px solid var(--ct-input-border); border-radius: 16px; padding: 6px 6px 6px 14px; transition: border-color .15s; }
.ct-input-wrap:focus-within { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ct-textarea { flex: 1; background: none; border: none; outline: none; font-size: 14px; line-height: 1.5; min-height: 24px; max-height: 120px; overflow-y: auto; color: var(--ct-name); font-family: inherit; word-break: break-word; white-space: pre-wrap; }
.ct-textarea:empty::before { content: attr(data-placeholder); color: var(--ct-time); pointer-events: none; }
.ct-textarea:focus { outline: none; }
.ct-btn-icon { width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--ct-action-btn); background: none; font-size: 16px; flex-shrink: 0; transition: color .15s, background .15s; }
.ct-btn-icon:hover { color: var(--ct-action-hover); background: rgba(124,58,237,.08); }
.ct-send-btn { width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; background: var(--ct-send-bg); color: white; font-size: 15px; flex-shrink: 0; transition: transform .12s, box-shadow .12s, opacity .15s; box-shadow: 0 2px 8px rgba(124,58,237,.4); }
.ct-send-btn:hover:not(:disabled) { transform: scale(1.08); box-shadow: 0 4px 14px rgba(124,58,237,.5); }
.ct-send-btn:disabled { opacity: .4; cursor: not-allowed; }
.ct-attach-pill { display: inline-flex; align-items: center; gap: 5px; background: var(--ct-chip-bg); color: var(--ct-chip-fg); border: 1px solid var(--ct-chip-border); border-radius: 20px; font-size: 11.5px; padding: 2px 10px; }
.ct-attach-pill.out { background: rgba(255,255,255,.18); color: white; border-color: rgba(255,255,255,.3); }
.ct-chip { display: inline-flex; align-items: center; gap: 4px; border-radius: 6px; padding: 1px 7px; font-size: 12px; font-weight: 500; text-decoration: none; cursor: pointer; transition: opacity .12s; }
.ct-chip:hover { opacity: .8; }
.ct-chip.task { background: var(--ct-task-chip-bg); color: var(--ct-task-chip-fg); border: 1px solid var(--ct-task-chip-border); }
.ct-chip.subtask { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.dark .ct-chip.subtask { background: #052e16; color: #86efac; border-color: #14532d; }
.ct-chip.member { background: var(--ct-chip-bg); color: var(--ct-chip-fg); border: 1px solid var(--ct-chip-border); }
.ct-bubble.out .ct-chip { background: rgba(255,255,255,.2); color: white; border-color: rgba(255,255,255,.35); }
.ct-mention-popup { position: absolute; bottom: calc(100% + 4px); left: 0; right: 0; background: var(--ct-mention-bg); border: 1px solid var(--ct-mention-border); border-radius: 14px; overflow: hidden; z-index: 100; max-height: 220px; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,.14); }
.ct-mention-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; cursor: pointer; font-size: 13px; transition: background .1s; }
.ct-mention-item:hover, .ct-mention-item.active { background: var(--ct-mention-hover); }
.ct-deleted { font-style: italic; opacity: .5; font-size: 13px; }
.ct-deleted i { font-size: 11px; margin-right: 4px; }
.ct-typing { display: flex; align-items: center; gap: 3px; padding: 8px 14px; background: var(--ct-bubble-in-bg); border-radius: 18px 18px 18px 4px; width: fit-content; border: 1px solid var(--ct-bubble-in-border); }
.ct-typing span { width: 6px; height: 6px; border-radius: 50%; background: var(--ct-time); animation: ct-bounce .9s infinite; display: inline-block; }
.ct-typing span:nth-child(2) { animation-delay: .15s; }
.ct-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes ct-bounce { 0%,60%,100%{transform:translateY(0);opacity:.4} 30%{transform:translateY(-5px);opacity:1} }
.ct-row:hover .ct-delete-btn { opacity: 1; }
.ct-delete-btn { opacity: 0; background: none; border: none; cursor: pointer; color: var(--ct-time); font-size: 12px; padding: 2px 4px; border-radius: 4px; transition: opacity .15s, color .15s; }
.ct-delete-btn:hover { color: #ef4444; }
</style>
