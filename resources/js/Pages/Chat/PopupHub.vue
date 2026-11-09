<script setup>
import { ref, computed } from 'vue'
import ChatPopupLayout from '@/Layouts/ChatPopupLayout.vue'

const props = defineProps({
    conversations: Array,
    users: Array,
    open_conv: Object,
    chat_index_url: String,
    groups_store_url: String,
})

const search = ref('')
const activeId = ref(props.open_conv?.id ?? null)
const dmSearch = ref('')
const dmDropdown = ref(false)
const groupModal = ref(false)
const groupName = ref('')
const memberSearch = ref('')
const selectedMembers = ref([])
const creating = ref(false)
const iframeUrl = ref(props.open_conv?.window_url ?? null)

const filteredConvs = computed(() =>
    props.conversations.filter(c => !search.value || convTitle(c).toLowerCase().includes(search.value.toLowerCase()))
)
const filteredUsers = computed(() =>
    props.users.filter(u => !dmSearch.value || u.name.toLowerCase().includes(dmSearch.value.toLowerCase()))
)
const filteredUsersByMember = computed(() =>
    props.users.filter(u => !memberSearch.value || u.name.toLowerCase().includes(memberSearch.value.toLowerCase()))
)

function convTitle(c) {
    if (c.name) return c.name
    const other = c.participants.find(p => p.id !== (window.__auth_id ?? 0))
    return other?.name ?? 'Direct Message'
}

function convAvatar(c) {
    if (!c.is_group) {
        const other = c.participants[0]
        return other?.avatar_url ?? null
    }
    return null
}

function isOnline(c) {
    if (c.is_group) return false
    const other = c.participants[0]
    return other?.availability === 'available' || other?.availability === 'busy'
}

function openConv(c) {
    activeId.value = c.id
    iframeUrl.value = c.window_url
}

function popIn() {
    if (window.opener) window.opener.location.href = props.chat_index_url
    if (window.opener) window.close()
    else window.location.href = props.chat_index_url
}

function toggleMember(user) {
    const idx = selectedMembers.value.findIndex(m => m.id === user.id)
    if (idx >= 0) selectedMembers.value.splice(idx, 1)
    else selectedMembers.value.push(user)
}

function isSelected(user) {
    return selectedMembers.value.some(m => m.id === user.id)
}

async function createGroup() {
    if (creating.value || !groupName.value.trim() || selectedMembers.value.length < 2) return
    creating.value = true
    try {
        const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
        const r = await fetch(props.groups_store_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(csrfToken || ''),
            },
            body: JSON.stringify({ name: groupName.value, member_ids: selectedMembers.value.map(m => m.id) }),
        })
        const conv = await r.json()
        iframeUrl.value = `/chat/${conv.id}/window`
        groupModal.value = false
        groupName.value = ''
        selectedMembers.value = []
    } catch (e) {}
    creating.value = false
}
</script>

<template>
    <ChatPopupLayout>
        <div class="flex" style="height:100vh;overflow:hidden;background:var(--ci-bg);">

            <!-- Left sidebar -->
            <div style="width:280px;min-width:280px;max-width:280px;display:flex;flex-direction:column;background:var(--ci-bg);border-right:1px solid var(--ci-header-border);overflow:hidden;">

                <!-- Header -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px 10px;background:var(--ci-header-bg);border-bottom:1px solid var(--ci-header-border);flex-shrink:0;">
                    <span style="font-size:15px;font-weight:700;color:var(--ci-name);">Messages</span>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <!-- Pop-in button -->
                        <button @click="popIn" style="width:30px;height:30px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ci-preview);background:none;font-size:14px;" title="Open in main app">
                            <i class="bi bi-arrows-angle-contract"></i>
                        </button>
                        <!-- New DM -->
                        <div style="position:relative;">
                            <button @click="dmDropdown = !dmDropdown" style="width:30px;height:30px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ci-preview);background:none;font-size:14px;">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <div v-if="dmDropdown" v-click-outside="() => dmDropdown = false"
                                 style="position:absolute;right:0;top:36px;width:224px;border-radius:16px;overflow:hidden;z-index:50;background:var(--ci-modal-bg);border:1px solid var(--ci-header-border);box-shadow:0 8px 32px rgba(0,0,0,.18);">
                                <div style="padding:8px 10px;border-bottom:1px solid var(--ci-sep);">
                                    <div style="position:relative;">
                                        <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--ci-time);"></i>
                                        <input v-model="dmSearch" placeholder="Search people…"
                                               style="width:100%;background:var(--ci-search-bg);border:1px solid var(--ci-search-border);border-radius:8px;padding:5px 8px 5px 24px;font-size:12px;color:var(--ci-name);outline:none;">
                                    </div>
                                </div>
                                <div style="max-height:192px;overflow-y:auto;padding:4px 0;">
                                    <a v-for="u in filteredUsers" :key="u.id"
                                       :href="u.direct_url" target="chat-frame"
                                       @click="activeId = null; dmDropdown = false"
                                       style="display:flex;align-items:center;gap:8px;padding:7px 12px;text-decoration:none;cursor:pointer;"
                                       onmouseover="this.style.background='var(--ci-row-hover)'" onmouseout="this.style.background=''">
                                        <div style="position:relative;flex-shrink:0;">
                                            <img :src="u.avatar_url" style="width:28px;height:28px;border-radius:50%;object-fit:cover;" alt="">
                                            <span :style="`position:absolute;bottom:0;right:0;width:8px;height:8px;border-radius:50%;background:${u.is_online ? 'var(--ci-online)' : 'var(--ci-offline)'};border:2px solid var(--ci-header-bg);`"></span>
                                        </div>
                                        <div>
                                            <div style="font-size:12px;font-weight:500;color:var(--ci-name);">{{ u.name }}</div>
                                            <div style="font-size:11px;color:var(--ci-time);">{{ u.is_online ? 'Online' : 'Offline' }}</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- New group -->
                        <button @click="groupModal = true" style="width:30px;height:30px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ci-preview);background:none;font-size:14px;">
                            <i class="bi bi-people-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div style="padding:8px 12px;background:var(--ci-header-bg);border-bottom:1px solid var(--ci-sep);flex-shrink:0;">
                    <div style="position:relative;">
                        <i class="bi bi-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--ci-time);"></i>
                        <input v-model="search" placeholder="Search…"
                               style="width:100%;background:var(--ci-search-bg);border:1px solid var(--ci-search-border);border-radius:8px;padding:6px 10px 6px 28px;font-size:13px;color:var(--ci-name);outline:none;">
                    </div>
                </div>

                <!-- Conversation list -->
                <div style="flex:1;overflow-y:auto;">
                    <template v-if="filteredConvs.length">
                        <template v-for="c in filteredConvs" :key="c.id">
                            <a :href="c.window_url" target="chat-frame" @click="openConv(c)"
                               style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;color:inherit;transition:background .1s;"
                               :style="`border-left: 3px solid ${activeId === c.id ? 'var(--ci-row-active-border)' : 'transparent'}; background: ${activeId === c.id ? 'var(--ci-row-active)' : ''};`"
                               onmouseover="if(!this.classList.contains('active'))this.style.background='var(--ci-row-hover)'" onmouseout="if(this.getAttribute('data-active') !== 'true')this.style.background=activeId === c.id ? 'var(--ci-row-active)' : ''">
                                <div style="position:relative;flex-shrink:0;">
                                    <img v-if="!c.is_group" :src="c.participants[0]?.avatar_url" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" alt="">
                                    <div v-else style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--ci-avatar-bg);">
                                        <i class="bi bi-people-fill" style="color:var(--ci-avatar-fg);font-size:14px;"></i>
                                    </div>
                                    <span v-if="!c.is_group"
                                          :style="`position:absolute;bottom:0;right:0;width:9px;height:9px;border-radius:50%;background:${isOnline(c) ? 'var(--ci-online)' : 'var(--ci-offline)'};border:2px solid var(--ci-header-bg);`"></span>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:2px;">
                                        <span style="font-weight:600;font-size:13.5px;color:var(--ci-name);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ convTitle(c) }}</span>
                                        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                            <span v-if="c.unread_count > 0"
                                                  style="min-width:17px;height:17px;background:var(--ci-badge-bg);color:var(--ci-badge-fg);border-radius:9px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;">
                                                {{ c.unread_count > 9 ? '9+' : c.unread_count }}
                                            </span>
                                            <span style="font-size:11px;color:var(--ci-time);white-space:nowrap;">{{ c.latest_message?.sent_at }}</span>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <i v-if="c.is_group" class="bi bi-people" style="font-size:11px;color:var(--ci-time);flex-shrink:0;"></i>
                                        <span :style="`font-size:12px;color:${c.unread_count > 0 ? 'var(--ci-name)' : 'var(--ci-preview)'};font-weight:${c.unread_count > 0 ? '600' : '400'};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;`">
                                            {{ c.latest_message ? (c.latest_message.is_mine ? 'You: ' : '') + c.latest_message.body?.slice(0, 40) : 'No messages yet' }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <div style="height:1px;background:var(--ci-sep);margin:0 14px;"></div>
                        </template>
                    </template>
                    <div v-else style="display:flex;flex-direction:column;align-items:center;padding:48px 16px;text-align:center;">
                        <i class="bi bi-chat-dots" style="font-size:28px;margin-bottom:8px;color:var(--ci-avatar-fg);"></i>
                        <p style="font-size:14px;font-weight:500;color:var(--ci-name);">No conversations yet</p>
                    </div>
                </div>
            </div>

            <!-- Right pane (iframe) -->
            <iframe v-if="iframeUrl" name="chat-frame" :src="iframeUrl" style="flex:1;border:none;height:100vh;background:var(--ci-bg);"></iframe>
            <div v-else style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--ci-preview);">
                <div style="width:64px;height:64px;border-radius:20px;display:flex;align-items:center;justify-content:center;background:var(--ci-avatar-bg);font-size:28px;color:var(--ci-avatar-fg);">
                    <i class="bi bi-chat-text"></i>
                </div>
                <p style="font-weight:600;font-size:14px;color:var(--ci-name);">Select a conversation</p>
                <p style="font-size:12px;color:var(--ci-time);">Pick one from the sidebar to start chatting</p>
            </div>
        </div>

        <!-- Group modal -->
        <Teleport to="body">
            <div v-if="groupModal" style="position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:200;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);" @click.self="groupModal = false">
                <div style="background:var(--ci-modal-bg);border-radius:20px;padding:24px 20px;width:min(460px,calc(100vw - 32px));max-height:85vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);border:1px solid rgba(124,58,237,.2);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <div>
                            <h2 style="font-size:16px;font-weight:700;color:var(--ci-name);">New Group</h2>
                            <p style="font-size:12px;margin-top:2px;color:var(--ci-preview);">Create a group with 2 or more members</p>
                        </div>
                        <button @click="groupModal = false" style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;background:none;color:var(--ci-preview);font-size:20px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="createGroup">
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;color:var(--ci-preview);">Group name</label>
                            <input v-model="groupName" required maxlength="100" placeholder="e.g. Frontend team…"
                                   style="width:100%;background:var(--ci-search-bg);border:1px solid var(--ci-search-border);border-radius:10px;padding:7px 12px;font-size:13.5px;color:var(--ci-name);outline:none;">
                        </div>
                        <div style="margin-bottom:8px;">
                            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;color:var(--ci-preview);">
                                Add members
                                <span v-if="selectedMembers.length > 0" style="font-weight:400;text-transform:none;margin-left:4px;">— {{ selectedMembers.length }} selected</span>
                            </label>
                            <div style="position:relative;">
                                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--ci-time);"></i>
                                <input v-model="memberSearch" placeholder="Search members…"
                                       style="width:100%;background:var(--ci-search-bg);border:1px solid var(--ci-search-border);border-radius:10px;padding:7px 12px 7px 34px;font-size:13.5px;color:var(--ci-name);outline:none;">
                            </div>
                        </div>
                        <div v-if="selectedMembers.length > 0" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
                            <span v-for="m in selectedMembers" :key="m.id"
                                  style="display:inline-flex;align-items:center;gap:5px;background:var(--ci-chip-bg);color:var(--ci-chip-fg);border-radius:20px;padding:3px 10px;font-size:12px;font-weight:500;">
                                <img :src="m.avatar_url" style="width:16px;height:16px;border-radius:50%;object-fit:cover;" alt="">
                                <span>{{ m.name.split(' ')[0] }}</span>
                                <button type="button" @click="toggleMember(m)" style="opacity:.6;cursor:pointer;border:none;background:none;padding:0;"><i class="bi bi-x text-xs"></i></button>
                            </span>
                        </div>
                        <div style="border-radius:10px;overflow:hidden;margin-bottom:16px;max-height:176px;overflow-y:auto;border:1px solid var(--ci-search-border);">
                            <label v-for="u in filteredUsersByMember" :key="u.id"
                                   style="display:flex;align-items:center;gap:12px;padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--ci-sep);"
                                   onmouseover="this.style.background='var(--ci-row-hover)'" onmouseout="this.style.background=''"
                                   @click.prevent="toggleMember(u)">
                                <img :src="u.avatar_url" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:14px;font-weight:500;color:var(--ci-name);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ u.name }}</div>
                                    <div style="font-size:12px;color:var(--ci-time);">{{ u.job_title || '' }}</div>
                                </div>
                                <div :style="`width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#7c3aed;opacity:${isSelected(u) ? '1' : '0'};transition:opacity .15s;`">
                                    <i class="bi bi-check text-white" style="font-size:11px;"></i>
                                </div>
                            </label>
                        </div>
                        <button type="submit" :disabled="creating || !groupName.trim() || selectedMembers.length < 2"
                                style="width:100%;padding:10px;border-radius:10px;font-size:14px;font-weight:600;color:white;border:none;cursor:pointer;background:linear-gradient(135deg,#7c3aed,#6d28d9);"
                                :style="(creating || !groupName.trim() || selectedMembers.length < 2) ? 'opacity:.5;cursor:not-allowed' : ''">
                            {{ creating ? 'Creating…' : 'Create Group' }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>
    </ChatPopupLayout>
</template>

<style>
:root {
    --ci-bg: #f8fafc; --ci-header-bg: #ffffff; --ci-header-border: #e2e8f0;
    --ci-row-hover: #f1f5f9; --ci-row-active: #ede9fe; --ci-row-active-border: #7c3aed;
    --ci-name: #1e293b; --ci-preview: #64748b; --ci-time: #94a3b8;
    --ci-avatar-bg: #ede9fe; --ci-avatar-fg: #7c3aed;
    --ci-badge-bg: #7c3aed; --ci-badge-fg: #ffffff;
    --ci-search-bg: #f1f5f9; --ci-search-border: #e2e8f0;
    --ci-sep: #f1f5f9; --ci-modal-bg: #ffffff;
    --ci-chip-bg: #ede9fe; --ci-chip-fg: #5b21b6;
    --ci-online: #22c55e; --ci-offline: #94a3b8;
}
.dark {
    --ci-bg: #0f172a; --ci-header-bg: #1e293b; --ci-header-border: #334155;
    --ci-row-hover: #1e293b; --ci-row-active: #2e1065; --ci-row-active-border: #7c3aed;
    --ci-name: #f1f5f9; --ci-preview: #94a3b8; --ci-time: #64748b;
    --ci-avatar-bg: #2e1065; --ci-avatar-fg: #a78bfa;
    --ci-badge-bg: #7c3aed; --ci-badge-fg: #ffffff;
    --ci-search-bg: #1e293b; --ci-search-border: #334155;
    --ci-sep: #1e293b; --ci-modal-bg: #1e293b;
    --ci-chip-bg: #2e1065; --ci-chip-fg: #c4b5fd;
    --ci-online: #22c55e; --ci-offline: #475569;
}
html, body { height: 100%; overflow: hidden; }
</style>
