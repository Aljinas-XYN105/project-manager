@extends('layouts.app')
@section('title', $conversation->type === 'direct'
    ? ($conversation->participants->firstWhere('id', '!=', auth()->id())?->name ?? 'Chat')
    : ($conversation->name ?? 'Group chat'))

@push('styles')
<style>
/* WhatsApp-inspired chat */
.chat-bg {
    background-color: #f0ece6;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c8b9a2' fill-opacity='0.18'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.dark .chat-bg {
    background-color: #0b141a;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231a2a35' fill-opacity='0.6'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Bubble tails */
.bubble-out {
    background: #dcf8c6;
    color: #111827;
    border-radius: 12px 12px 4px 12px;
    position: relative;
}
.dark .bubble-out {
    background: #005c4b;
    color: #e9edef;
}
.bubble-out::after {
    content: '';
    position: absolute;
    bottom: 0; right: -6px;
    border: 6px solid transparent;
    border-left-color: #dcf8c6;
    border-bottom-color: #dcf8c6;
}
.dark .bubble-out::after {
    border-left-color: #005c4b;
    border-bottom-color: #005c4b;
}
.bubble-in {
    background: #ffffff;
    color: #111827;
    border-radius: 12px 12px 12px 4px;
    position: relative;
}
.dark .bubble-in {
    background: #202c33;
    color: #e9edef;
}
.bubble-in::after {
    content: '';
    position: absolute;
    bottom: 0; left: -6px;
    border: 6px solid transparent;
    border-right-color: #ffffff;
    border-bottom-color: #ffffff;
}
.dark .bubble-in::after {
    border-right-color: #202c33;
    border-bottom-color: #202c33;
}
.bubble-time {
    font-size: 10px;
    color: #667781;
    white-space: nowrap;
}
.dark .bubble-time { color: #8696a0; }

/* Compose bar */
.compose-wrap {
    background: #f0f2f5;
    border-top: none;
}
.dark .compose-wrap { background: #1f2c33; }
.compose-input {
    background: #ffffff;
    border: none;
    border-radius: 24px;
    resize: none;
    outline: none;
    font-size: 14.5px;
    line-height: 1.4;
    color: #111827;
    padding: 10px 16px;
}
.dark .compose-input {
    background: #2a3942;
    color: #d1d7db;
}
.compose-input::placeholder { color: #8696a0; }
.compose-action-btn {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #54656f;
    background: none; border: none; cursor: pointer;
    font-size: 18px;
    transition: background .15s, color .15s;
}
.dark .compose-action-btn { color: #aebac1; }
.compose-action-btn:hover { background: #e9edef; color: #3b4a54; }
.dark .compose-action-btn:hover { background: #374045; color: #d1d7db; }
.send-fab {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: #00a884;
    color: white;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    transition: background .15s;
    flex-shrink: 0;
}
.send-fab:hover { background: #017f63; }
.send-fab:disabled { background: #c0c0c0; cursor: not-allowed; }

/* Chat header */
.chat-header-wa {
    background: #008069;
    color: white;
    padding: 10px 16px;
    display: flex; align-items: center; gap: 12px;
}
.dark .chat-header-wa { background: #202c33; }

/* Task mention chip */
.task-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #e8f5e9; color: #2e7d32;
    border: 1px solid #a5d6a7;
    border-radius: 6px; padding: 1px 7px;
    font-size: 12px; font-weight: 500;
    cursor: pointer; text-decoration: none;
}
.dark .task-chip { background: #1b3a2a; color: #81c784; border-color: #2e5f38; }
.task-chip:hover { background: #c8e6c9; }
.dark .task-chip:hover { background: #22482f; }

/* Mention popup */
.mention-popup {
    position: absolute;
    bottom: calc(100% + 6px);
    left: 0; right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,.18);
    overflow: hidden;
    z-index: 100;
    max-height: 220px;
    overflow-y: auto;
}
.dark .mention-popup {
    background: #233138;
    box-shadow: 0 4px 20px rgba(0,0,0,.5);
}
.mention-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; cursor: pointer;
    font-size: 13px;
    transition: background .1s;
}
.mention-item:hover, .mention-item.active { background: #f0f2f5; }
.dark .mention-item:hover, .dark .mention-item.active { background: #2a3942; }

/* Attachment pill */
.attach-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: #e1f5fe; color: #01579b;
    border: 1px solid #b3e5fc; border-radius: 20px;
    font-size: 11px; padding: 2px 10px;
}
.dark .attach-pill { background: #1a2f3d; color: #81d4fa; border-color: #1e4a62; }

/* Typing dots */
.typing-dots span { animation: tdot 1.2s infinite; display: inline-block; }
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes tdot { 0%,60%,100%{transform:translateY(0);opacity:.4} 30%{transform:translateY(-4px);opacity:1} }

/* Deleted bubble */
.bubble-deleted { font-style: italic; color: #8696a0 !important; }
</style>
@endpush

@section('content')
@php
    $authUser   = auth()->user();
    $other      = $conversation->type === 'direct'
        ? $conversation->participants->firstWhere('id', '!=', $authUser->id)
        : null;
    $title      = $other?->name ?? $conversation->name ?? 'Group chat';
    $memberCount = $conversation->participants->count();
    $memberNames = $conversation->participants->pluck('name')->join(', ');
@endphp

<div class="flex flex-col -mt-6 -mx-4 lg:-mx-5 overflow-hidden rounded-none"
     style="height: calc(100vh - 7rem);"
     x-data="chatThread({{ $conversation->id }}, {{ $authUser->id }}, @js($messages->items()))">

    {{-- WhatsApp-style header --}}
    <div class="chat-header-wa flex-shrink-0">
        <a href="{{ route('chat.index') }}" class="text-white/80 hover:text-white mr-1">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>

        @if($other)
        <img src="{{ $other->avatar_url }}" class="w-9 h-9 rounded-full ring-2 ring-white/30 flex-shrink-0 object-cover" alt="">
        @else
        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-people-fill text-white text-sm"></i>
        </div>
        @endif

        <div class="flex-1 min-w-0">
            <p class="font-semibold text-white text-sm leading-tight">{{ $title }}</p>
            <div x-show="typingUsers.length === 0">
                <p class="text-[11px] text-white/70">
                    @if($other)
                    {{ $other->job_title ?? $other->email }}
                    @else
                    {{ $memberCount }} members · {{ $memberNames }}
                    @endif
                </p>
            </div>
            <div x-show="typingUsers.length > 0" class="text-[11px] text-green-200">
                <span class="typing-dots">
                    <span class="inline-block w-1 h-1 rounded-full bg-green-300"></span>
                    <span class="inline-block w-1 h-1 rounded-full bg-green-300"></span>
                    <span class="inline-block w-1 h-1 rounded-full bg-green-300"></span>
                </span>
                <span x-text="typingUsers.map(u=>u.name.split(' ')[0]).join(', ') + ' typing…'" class="ml-1"></span>
            </div>
        </div>

        @if($conversation->type === 'group')
        <button @click="$dispatch('open-group-info')"
                class="text-white/80 hover:text-white ml-1">
            <i class="bi bi-info-circle text-lg"></i>
        </button>
        @endif
    </div>

    {{-- Message thread --}}
    <div class="flex-1 overflow-y-auto px-3 py-3 space-y-1 chat-bg"
         x-ref="thread"
         id="chat-thread">

        @if($messages->hasMorePages())
        <div class="text-center py-2">
            <a href="{{ $messages->previousPageUrl() }}"
               class="text-xs bg-white/80 dark:bg-black/40 text-slate-600 dark:text-white/60 px-3 py-1.5 rounded-full shadow-sm hover:bg-white dark:hover:bg-black/60">
                Load earlier messages
            </a>
        </div>
        @endif

        <template x-for="(msg, idx) in messages" :key="msg.id">
            <div>
                {{-- Date separator (new day) --}}
                <template x-if="idx === 0 || isDifferentDay(messages[idx-1]?.created_at, msg.created_at)">
                    <div class="flex items-center justify-center my-2">
                        <span class="text-[11px] text-slate-600 dark:text-slate-300 bg-amber-50/90 dark:bg-black/40 shadow-sm px-3 py-1 rounded-full"
                              x-text="formatDateSep(msg.created_at)"></span>
                    </div>
                </template>

                <div class="flex gap-1.5"
                     :class="msg.user.id === authUserId ? 'flex-row-reverse' : 'flex-row'">

                    {{-- Avatar (others only, group) --}}
                    <template x-if="msg.user.id !== authUserId && {{ $conversation->type === 'group' ? 'true' : 'false' }}">
                        <div class="flex flex-col justify-end flex-shrink-0">
                            <img :src="msg.user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user.name)}&color=7F9CF5&background=EBF4FF&bold=true`"
                                 class="w-7 h-7 rounded-full object-cover" alt="">
                        </div>
                    </template>

                    <div class="max-w-[72%] sm:max-w-[60%] flex flex-col"
                         :class="msg.user.id === authUserId ? 'items-end' : 'items-start'">

                        {{-- Sender name in group --}}
                        @if($conversation->type === 'group')
                        <template x-if="msg.user.id !== authUserId">
                            <span class="text-[11px] font-semibold mb-0.5 ml-3"
                                  :style="'color:' + senderColor(msg.user.id)"
                                  x-text="msg.user.name"></span>
                        </template>
                        @endif

                        {{-- Bubble --}}
                        <div :class="msg.user.id === authUserId ? 'bubble-out' : 'bubble-in'"
                             class="px-3 py-2 text-sm leading-relaxed break-words shadow-sm max-w-full">

                            <template x-if="!msg.deleted">
                                <div>
                                    {{-- Render message body parsing task mentions --}}
                                    <span x-html="renderBody(msg.body)"></span>
                                    {{-- Attachments --}}
                                    <template x-if="msg.attachments && msg.attachments.length">
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            <template x-for="att in msg.attachments" :key="att.id">
                                                <div>
                                                    <template x-if="att.is_image">
                                                        <a :href="att.url" target="_blank" class="block">
                                                            <img :src="att.url" class="max-w-[200px] max-h-[160px] rounded-lg object-cover" alt="">
                                                        </a>
                                                    </template>
                                                    <template x-if="!att.is_image">
                                                        <a :href="att.url" target="_blank"
                                                           class="attach-pill">
                                                            <i class="bi bi-paperclip text-xs"></i>
                                                            <span x-text="att.original_name" class="max-w-[140px] truncate"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="msg.deleted">
                                <span class="bubble-deleted flex items-center gap-1.5">
                                    <i class="bi bi-slash-circle text-xs"></i>
                                    This message was deleted
                                </span>
                            </template>

                            {{-- Time + delete --}}
                            <div class="flex items-center justify-end gap-2 mt-0.5">
                                <span class="bubble-time" x-text="formatTime(msg.created_at)"></span>
                                <template x-if="msg.user.id === authUserId && !msg.deleted">
                                    <button @click="deleteMessage(msg)"
                                            class="bubble-time hover:text-rose-500 transition-colors">
                                        <i class="bi bi-trash3 text-[9px]"></i>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-ref="bottom"></div>
    </div>

    {{-- Compose bar --}}
    <div class="compose-wrap flex-shrink-0 px-2 py-2">
        {{-- Attachment previews --}}
        <div x-show="pendingFiles.length > 0" class="flex flex-wrap gap-1.5 mb-2 px-2">
            <template x-for="(f, i) in pendingFiles" :key="i">
                <span class="attach-pill">
                    <i class="bi bi-paperclip"></i>
                    <span x-text="f.name" class="max-w-[120px] truncate"></span>
                    <button @click="pendingFiles.splice(i,1)" class="ml-1 hover:text-red-500">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </span>
            </template>
        </div>

        {{-- Mention popup --}}
        <div class="relative">
            <div x-show="mentionOpen && mentionResults.length > 0" x-cloak class="mention-popup">
                <template x-for="(t, i) in mentionResults" :key="t.id">
                    <div class="mention-item" :class="i === mentionIndex ? 'active' : ''"
                         @click="insertMention(t)">
                        <span class="w-6 h-6 rounded flex items-center justify-center text-violet-600 bg-violet-100 dark:bg-violet-900/40 flex-shrink-0">
                            <i class="bi bi-check-square-fill text-xs"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="font-medium truncate" x-text="t.title"></div>
                            <div class="text-xs opacity-60 truncate" x-text="t.project_name"></div>
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                {{-- Attach --}}
                <label class="compose-action-btn flex-shrink-0">
                    <i class="bi bi-paperclip"></i>
                    <input type="file" class="hidden" multiple @change="pickFiles($event)">
                </label>

                {{-- Textarea --}}
                <textarea
                    x-model="draft"
                    @keydown.enter.exact.prevent="sendMessage"
                    @keydown.arrow-up.prevent="if (mentionOpen) { mentionIndex = Math.max(0, mentionIndex-1); }"
                    @keydown.arrow-down.prevent="if (mentionOpen) { mentionIndex = Math.min(mentionResults.length-1, mentionIndex+1); }"
                    @keydown.escape="mentionOpen = false"
                    @keydown.tab.prevent="if (mentionOpen && mentionResults[mentionIndex]) { insertMention(mentionResults[mentionIndex]); }"
                    @input="onInput"
                    @keydown="onKeydown"
                    x-ref="compose"
                    rows="1"
                    placeholder="Message"
                    class="compose-input flex-1 min-h-[40px] max-h-[120px]"
                    style="overflow-y: auto; field-sizing: content;"
                ></textarea>

                {{-- Send --}}
                <button type="submit"
                        :disabled="!draft.trim() && pendingFiles.length === 0"
                        class="send-fab flex-shrink-0">
                    <i class="bi bi-send-fill text-sm"></i>
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function chatThread(conversationId, authUserId, initialMessages) {
    return {
        conversationId,
        authUserId,
        messages: initialMessages.map(m => ({
            ...m,
            deleted: !!(m.deleted_at),
        })).reverse(),
        draft: '',
        pendingFiles: [],
        typingUsers: [],
        typingTimers: {},
        sending: false,
        _whisperTimer: null,

        // Task mention
        mentionOpen: false,
        mentionQuery: '',
        mentionResults: [],
        mentionIndex: 0,
        mentionStart: -1,
        allTasks: [],

        // Sender colours for group chat
        _colorMap: {},
        _colors: ['#e53935','#8e24aa','#1e88e5','#00897b','#f4511e','#6d4c41','#546e7a'],
        senderColor(uid) {
            if (!this._colorMap[uid]) {
                const keys = Object.keys(this._colorMap).length;
                this._colorMap[uid] = this._colors[keys % this._colors.length];
            }
            return this._colorMap[uid];
        },

        init() {
            this.$nextTick(() => this.scrollToBottom());

            // Load tasks for @ mention
            fetch('/api/tasks/brief', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(r => r.ok ? r.json() : []).then(d => { this.allTasks = d; }).catch(() => {});

            window.Echo.private(`conversation.${this.conversationId}`)
                .listen('.message.sent', (data) => {
                    if (data.user.id === this.authUserId) return; // own optimistic already added
                    this.messages.push({ ...data, deleted: false });
                    this.$nextTick(() => this.scrollToBottom());
                })
                .listen('.message.deleted', (data) => {
                    const msg = this.messages.find(m => m.id === data.id);
                    if (msg) { msg.body = ''; msg.deleted = true; }
                })
                .listenForWhisper('typing', (data) => {
                    if (!this.typingUsers.find(u => u.id === data.id)) {
                        this.typingUsers.push(data);
                    }
                    clearTimeout(this.typingTimers[data.id]);
                    this.typingTimers[data.id] = setTimeout(() => {
                        this.typingUsers = this.typingUsers.filter(u => u.id !== data.id);
                    }, 3000);
                });
        },

        onKeydown() {
            clearTimeout(this._whisperTimer);
            this._whisperTimer = setTimeout(() => {
                window.Echo.private(`conversation.${this.conversationId}`)
                    .whisper('typing', {
                        id: this.authUserId,
                        name: document.querySelector('meta[name="user-name"]')?.content ?? '',
                        avatar_url: document.querySelector('meta[name="user-avatar"]')?.content ?? '',
                    });
            }, 500);
        },

        onInput(e) {
            const ta = e.target;
            const val = ta.value;
            const pos = ta.selectionStart;

            // Find @ trigger
            const before = val.slice(0, pos);
            const match = before.match(/@(\w*)$/);
            if (match) {
                this.mentionQuery = match[1].toLowerCase();
                this.mentionStart = before.lastIndexOf('@');
                this.mentionResults = this.allTasks.filter(t =>
                    t.title.toLowerCase().includes(this.mentionQuery) ||
                    (t.project_name || '').toLowerCase().includes(this.mentionQuery)
                ).slice(0, 8);
                this.mentionOpen = this.mentionResults.length > 0;
                this.mentionIndex = 0;
            } else {
                this.mentionOpen = false;
            }
        },

        insertMention(task) {
            const ta = this.$refs.compose;
            const before = this.draft.slice(0, this.mentionStart);
            const after  = this.draft.slice(ta.selectionStart);
            this.draft = before + `@[${task.title}](task:${task.id}) ` + after;
            this.mentionOpen = false;
            this.$nextTick(() => {
                ta.focus();
                const pos = before.length + task.title.length + 10;
                ta.setSelectionRange(pos, pos);
            });
        },

        renderBody(body) {
            if (!body) return '';
            // Escape HTML first
            const escaped = body
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            // Replace @[Task Title](task:123) with chips
            return escaped.replace(/@\[([^\]]+)\]\(task:(\d+)\)/g, (_, title, id) => {
                return `<a href="/tasks/${id}" class="task-chip" target="_blank"><i class="bi bi-check-square-fill" style="font-size:10px"></i>${title}</a>`;
            });
        },

        pickFiles(e) {
            this.pendingFiles = [...this.pendingFiles, ...Array.from(e.target.files)];
            e.target.value = '';
        },

        async sendMessage() {
            if (!this.draft.trim() && this.pendingFiles.length === 0) return;
            if (this.sending) return;
            this.sending = true;
            this.mentionOpen = false;

            const form = new FormData();
            if (this.draft.trim()) form.append('body', this.draft.trim());
            this.pendingFiles.forEach(f => form.append('attachments[]', f));

            const optimistic = {
                id: Date.now(),
                body: this.draft.trim(),
                created_at: new Date().toISOString(),
                user: {
                    id: this.authUserId,
                    name: document.querySelector('meta[name="user-name"]')?.content ?? 'You',
                    avatar_url: document.querySelector('meta[name="user-avatar"]')?.content ?? '',
                },
                attachments: [],
                deleted: false,
                optimistic: true,
            };
            this.messages.push(optimistic);
            this.draft = '';
            this.pendingFiles = [];
            this.$nextTick(() => this.scrollToBottom());

            try {
                const resp = await fetch(`/chat/${this.conversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: form,
                });
                const data = await resp.json();
                const idx = this.messages.findIndex(m => m.optimistic && m.id === optimistic.id);
                if (idx !== -1) this.messages[idx] = { ...data, deleted: false };
            } catch (e) {
                this.messages = this.messages.filter(m => m.id !== optimistic.id);
            } finally {
                this.sending = false;
            }
        },

        async deleteMessage(msg) {
            if (!confirm('Delete this message?')) return;
            await fetch(`/chat/messages/${msg.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            msg.body = ''; msg.deleted = true;
        },

        scrollToBottom() {
            this.$refs.bottom?.scrollIntoView({ behavior: 'smooth' });
        },

        isDifferentDay(isoA, isoB) {
            if (!isoA || !isoB) return false;
            return new Date(isoA).toDateString() !== new Date(isoB).toDateString();
        },

        formatDateSep(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            const now = new Date();
            const yesterday = new Date(now); yesterday.setDate(now.getDate() - 1);
            if (d.toDateString() === now.toDateString()) return 'Today';
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
        },

        formatTime(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
@endpush
