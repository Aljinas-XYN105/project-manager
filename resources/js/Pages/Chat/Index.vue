<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    conversations: Array,
    users: Array,
    popup_url: String,
    group_store_url: String,
})

const page = usePage()
const authId = computed(() => page.props.auth?.user?.id)

const search = ref('')
const showGroupModal = ref(false)
const groupName = ref('')
const memberSearch = ref('')
const selected = ref([])
const loading = ref(false)

const peopleResults = computed(() => {
    const q = search.value.toLowerCase().trim()
    if (!q) return []
    return props.users
        .filter(u => u.name.toLowerCase().includes(q) || (u.job_title || '').toLowerCase().includes(q))
        .slice(0, 6)
})

function convTitle(conv) {
    if (conv.name) return conv.name
    const other = conv.participants.find(p => p.id !== authId.value)
    return other?.name ?? 'Chat'
}

function convOther(conv) {
    return conv.participants.find(p => p.id !== authId.value)
}

function convPreview(conv) {
    if (!conv.latest_message) return ''
    const prefix = conv.latest_message.is_mine ? 'You: ' : ''
    const body = (conv.latest_message.body ?? '').substring(0, 50)
    return prefix + body
}

function convVisible(conv) {
    const q = search.value.toLowerCase().trim()
    if (!q) return true
    return convTitle(conv).toLowerCase().includes(q)
}

function ucFirst(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''
}

// Group modal helpers
function toggleMember(m) {
    const i = selected.value.findIndex(x => x.id === m.id)
    if (i === -1) selected.value.push(m)
    else selected.value.splice(i, 1)
}
function deselectMember(m) {
    selected.value = selected.value.filter(x => x.id !== m.id)
}
function isMemberSelected(id) {
    return selected.value.some(x => x.id === id)
}

function filteredMembers() {
    const q = memberSearch.value.toLowerCase().trim()
    if (!q) return props.users
    return props.users.filter(u => u.name.toLowerCase().includes(q))
}

function csrfToken() {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '')
}

async function createGroup() {
    if (!groupName.value.trim() || selected.value.length < 2) return
    loading.value = true
    try {
        const r = await fetch(props.group_store_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ name: groupName.value.trim(), member_ids: selected.value.map(m => m.id) }),
        })
        const d = await r.json()
        if (d.id) window.location.href = `/chat/${d.id}`
        else alert(d.message || 'Failed.')
    } catch { alert('Something went wrong.') }
    finally { loading.value = false }
}
</script>

<template>
    <AppLayout title="Chat">
        <!-- Full-height chat wrapper (negative margins to bleed to edges) -->
        <div class="ci-index-wrap -mt-6 -mx-4 lg:-mx-5 flex flex-col">

            <!-- Header -->
            <div class="flex-shrink-0 px-4 py-3 flex items-center justify-between gap-3"
                 style="background:var(--ci-header-bg); border-bottom:1px solid var(--ci-header-border);">
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-bold" style="color:var(--ci-name)">Messages</h1>
                    <button type="button"
                            @click="() => window.open(popup_url, 'chatpopup', 'width=820,height=680,resizable=yes,menubar=no,toolbar=no,location=no,status=no')"
                            class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
                            style="color:var(--ci-time)"
                            title="Open in popup window"
                            @mouseover="$event.currentTarget.style.color='#7c3aed';$event.currentTarget.style.background='var(--ci-row-hover)'"
                            @mouseout="$event.currentTarget.style.color='var(--ci-time)';$event.currentTarget.style.background=''">
                        <i class="bi bi-arrows-angle-expand" style="font-size:11px"></i>
                    </button>
                </div>
                <button @click="showGroupModal = true"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all"
                        style="background: linear-gradient(135deg,#7c3aed,#6d28d9); box-shadow:0 2px 8px rgba(124,58,237,.35);">
                    <i class="bi bi-people-fill text-xs"></i> New Group
                </button>
            </div>

            <!-- Search -->
            <div class="flex-shrink-0 px-3 py-2.5" style="background:var(--ci-header-bg); border-bottom:1px solid var(--ci-sep)">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
                    <input v-model="search" type="text" placeholder="Search chats or people…" class="ci-search-input">
                </div>
            </div>

            <!-- Conversation list -->
            <div class="flex-1 overflow-y-auto" style="background:var(--ci-bg)">
                <!-- Empty state -->
                <div v-if="conversations.length === 0 && !search"
                     class="flex flex-col items-center justify-center py-20 px-6 text-center">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                         style="background:var(--ci-avatar-bg)">
                        <i class="bi bi-chat-dots text-3xl" style="color:var(--ci-avatar-fg)"></i>
                    </div>
                    <p class="font-semibold mb-1" style="color:var(--ci-name)">No conversations yet</p>
                    <p class="text-sm" style="color:var(--ci-preview)">Start a DM or create a group above.</p>
                </div>

                <!-- Conversation rows -->
                <template v-for="conv in conversations" :key="conv.id">
                    <a v-show="convVisible(conv)"
                       :href="conv.show_url"
                       class="conv-row-ci">

                        <div class="relative flex-shrink-0">
                            <template v-if="!conv.is_group">
                                <img :src="convOther(conv)?.avatar_url" class="w-11 h-11 rounded-full object-cover" alt="">
                                <span class="status-dot-ci" :class="convOther(conv)?.is_online ? 'online' : 'offline'"></span>
                            </template>
                            <div v-else class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                                 style="background:var(--ci-avatar-bg)">
                                <i class="bi bi-people-fill text-base" style="color:var(--ci-avatar-fg)"></i>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <span class="conv-name-ci truncate">{{ convTitle(conv) }}</span>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <span v-if="conv.unread_count > 0" class="conv-badge-ci">
                                        {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
                                    </span>
                                    <span class="conv-time-ci">{{ conv.latest_message?.time_label ?? '' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <i v-if="conv.is_group" class="bi bi-people text-xs flex-shrink-0" style="color:var(--ci-time)"></i>
                                <span class="conv-preview-ci"
                                      :class="conv.unread_count > 0 ? 'font-semibold' : ''"
                                      :style="conv.unread_count > 0 ? 'color:var(--ci-name)' : ''">
                                    {{ convPreview(conv) || 'No messages yet' }}
                                </span>
                            </div>
                        </div>
                    </a>
                    <div class="conv-sep-ci"></div>
                </template>

                <!-- People results (shown when searching) -->
                <template v-if="search.trim() && peopleResults.length > 0">
                    <div>
                        <div class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide" style="color:var(--ci-time)">
                            People
                        </div>
                        <template v-for="u in peopleResults" :key="u.id">
                            <a :href="u.direct_url" class="conv-row-ci">
                                <div class="relative flex-shrink-0">
                                    <img :src="u.avatar_url" class="w-11 h-11 rounded-full object-cover" alt="">
                                    <span class="status-dot-ci" :class="u.is_online ? 'online' : 'offline'"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-0.5">
                                        <span class="conv-name-ci truncate">{{ u.name }}</span>
                                        <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                                              style="background:var(--ci-chip-bg);color:var(--ci-chip-fg)">Message</span>
                                    </div>
                                    <span class="conv-preview-ci">{{ u.job_title || (u.is_online ? ucFirst(u.availability) : 'Offline') }}</span>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- New Group Modal -->
        <Teleport to="body">
            <div v-if="showGroupModal" class="ci-modal-overlay" @click.self="showGroupModal = false">
                <div class="ci-modal-card" @click.stop>
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-bold" style="color:var(--ci-name)">New Group</h2>
                            <p class="text-xs mt-0.5" style="color:var(--ci-preview)">Create a group with 2 or more members</p>
                        </div>
                        <button @click="showGroupModal = false"
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                style="color:var(--ci-preview)"
                                @mouseover="$event.currentTarget.style.background='var(--ci-row-hover)'"
                                @mouseout="$event.currentTarget.style.background=''">
                            <i class="bi bi-x text-xl"></i>
                        </button>
                    </div>

                    <form @submit.prevent="createGroup">
                        <div class="mb-4">
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5"
                                   style="color:var(--ci-preview)">Group name</label>
                            <input v-model="groupName" type="text" required maxlength="100"
                                   placeholder="e.g. Frontend team, Sprint #12…"
                                   class="form-input w-full text-sm">
                        </div>

                        <div class="mb-3">
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5"
                                   style="color:var(--ci-preview)">
                                Add members
                                <span v-if="selected.length > 0" class="normal-case font-normal ml-1">
                                    — {{ selected.length }} selected
                                </span>
                            </label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
                                <input v-model="memberSearch" type="text" placeholder="Search members…" class="ci-search-input">
                            </div>
                        </div>

                        <div v-if="selected.length > 0" class="flex flex-wrap gap-1.5 mb-3">
                            <span v-for="m in selected" :key="m.id" class="member-chip-ci">
                                <img :src="m.avatar_url" class="w-4 h-4 rounded-full object-cover">
                                <span class="text-xs">{{ m.name.split(' ')[0] }}</span>
                                <button type="button" @click="deselectMember(m)" class="opacity-60 hover:opacity-100 ml-0.5">
                                    <i class="bi bi-x text-xs"></i>
                                </button>
                            </span>
                        </div>

                        <div class="rounded-xl overflow-hidden mb-5 max-h-52 overflow-y-auto"
                             style="border:1px solid var(--ci-search-border)">
                            <template v-for="u in filteredMembers()" :key="u.id">
                                <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors"
                                       style="border-bottom:1px solid var(--ci-sep)"
                                       @click.prevent="toggleMember({ id: u.id, name: u.name, avatar_url: u.avatar_url })"
                                       @mouseover="$event.currentTarget.style.background='var(--ci-row-hover)'"
                                       @mouseout="$event.currentTarget.style.background=''">
                                    <div class="relative flex-shrink-0">
                                        <img :src="u.avatar_url" class="w-9 h-9 rounded-full object-cover" alt="">
                                        <span class="status-dot-ci" :class="u.is_online ? 'online' : 'offline'"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium truncate" style="color:var(--ci-name)">{{ u.name }}</div>
                                        <div class="text-xs" style="color:var(--ci-time)">
                                            {{ u.is_online ? ucFirst(u.availability ?? 'online') : 'Offline' }}
                                        </div>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                                         :style="isMemberSelected(u.id) ? 'background:#7c3aed;border-color:#7c3aed' : 'border-color:var(--ci-search-border)'">
                                        <i v-if="isMemberSelected(u.id)" class="bi bi-check text-white text-xs font-bold"></i>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="showGroupModal = false"
                                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                                    style="border:1px solid var(--ci-search-border); color:var(--ci-preview)"
                                    @mouseover="$event.currentTarget.style.background='var(--ci-row-hover)'"
                                    @mouseout="$event.currentTarget.style.background=''">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="!groupName.trim() || selected.length < 2 || loading"
                                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-40"
                                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                                <span v-if="!loading"><i class="bi bi-people-fill mr-1.5"></i>Create group</span>
                                <span v-else>Creating…</span>
                            </button>
                        </div>
                        <p v-if="selected.length === 1" class="text-xs text-amber-500 mt-2 text-center">
                            Add at least one more member.
                        </p>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style>
:root {
    --ci-bg: #f8fafc; --ci-header-bg: #ffffff; --ci-header-border: #e2e8f0;
    --ci-row-hover: #f1f5f9; --ci-row-active: #ede9fe; --ci-row-active-border: #7c3aed;
    --ci-name: #1e293b; --ci-preview: #64748b; --ci-time: #94a3b8;
    --ci-avatar-bg: #ede9fe; --ci-avatar-fg: #7c3aed;
    --ci-badge-bg: #7c3aed; --ci-badge-fg: #ffffff;
    --ci-search-bg: #f1f5f9; --ci-search-border: #e2e8f0;
    --ci-sep: #f1f5f9; --ci-tab-active: #7c3aed; --ci-tab-inactive: #94a3b8;
    --ci-modal-bg: #ffffff; --ci-chip-bg: #ede9fe; --ci-chip-fg: #5b21b6;
    --ci-online: #22c55e; --ci-offline: #94a3b8;
}
.dark {
    --ci-bg: #0f172a; --ci-header-bg: #1e293b; --ci-header-border: #334155;
    --ci-row-hover: #1e293b; --ci-row-active: #2e1065; --ci-row-active-border: #7c3aed;
    --ci-name: #f1f5f9; --ci-preview: #94a3b8; --ci-time: #64748b;
    --ci-avatar-bg: #2e1065; --ci-avatar-fg: #a78bfa;
    --ci-badge-bg: #7c3aed; --ci-badge-fg: #ffffff;
    --ci-search-bg: #1e293b; --ci-search-border: #334155;
    --ci-sep: #1e293b; --ci-modal-bg: #1e293b; --ci-chip-bg: #2e1065; --ci-chip-fg: #c4b5fd;
    --ci-online: #22c55e; --ci-offline: #475569;
}
.ci-index-wrap { background: var(--ci-bg); height: calc(100vh - 7rem); overflow: hidden; }
.conv-row-ci {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 16px; cursor: pointer;
    text-decoration: none; color: inherit;
    transition: background .12s;
    border-left: 3px solid transparent;
    position: relative;
}
.conv-row-ci:hover { background: var(--ci-row-hover); }
.conv-row-ci.ci-active { background: var(--ci-row-active); border-left-color: var(--ci-row-active-border); }
.conv-sep-ci { height: 1px; background: var(--ci-sep); margin: 0 16px; }
.conv-name-ci { font-weight: 600; font-size: 14px; color: var(--ci-name); line-height: 1.2; }
.conv-preview-ci { font-size: 12.5px; color: var(--ci-preview); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-time-ci { font-size: 11px; color: var(--ci-time); white-space: nowrap; }
.conv-badge-ci {
    min-width: 18px; height: 18px;
    background: var(--ci-badge-bg); color: var(--ci-badge-fg);
    border-radius: 9px; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 4px;
    flex-shrink: 0;
}
.ci-search-input {
    background: var(--ci-search-bg);
    border: 1px solid var(--ci-search-border);
    border-radius: 10px;
    padding: 7px 12px 7px 34px;
    font-size: 13.5px;
    color: var(--ci-name); outline: none; width: 100%;
    transition: border-color .15s;
}
.ci-search-input:focus { border-color: #7c3aed; }
.ci-search-input::placeholder { color: var(--ci-time); }
.status-dot-ci {
    width: 9px; height: 9px; border-radius: 50%;
    border: 2px solid var(--ci-header-bg);
    position: absolute; bottom: 0; right: 0;
}
.status-dot-ci.online { background: var(--ci-online); }
.status-dot-ci.offline { background: var(--ci-offline); }
.ci-modal-overlay {
    position: fixed; inset: 0; background: rgba(15,23,42,.6);
    z-index: 200; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.ci-modal-card {
    background: var(--ci-modal-bg);
    border-radius: 20px; padding: 28px 24px;
    width: min(500px, calc(100vw - 32px));
    max-height: 85vh; overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,.25);
    border: 1px solid rgba(124,58,237,.2);
}
.member-chip-ci {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--ci-chip-bg); color: var(--ci-chip-fg);
    border-radius: 20px; padding: 3px 10px; font-size: 12px; font-weight: 500;
}
</style>
