@extends('layouts.app')
@section('title', $conversation->type === 'direct'
    ? ($conversation->participants->firstWhere('id', '!=', auth()->id())?->name ?? 'Chat')
    : ($conversation->name ?? 'Group chat'))

@push('styles')
<style>
    #chat-thread { scroll-behavior: smooth; }
    .msg-bubble { max-width: 72%; }
    @media (max-width: 640px) { .msg-bubble { max-width: 90%; } }
    .typing-dots span { animation: bounce 1s infinite; display: inline-block; }
    .typing-dots span:nth-child(2) { animation-delay: .15s; }
    .typing-dots span:nth-child(3) { animation-delay: .3s; }
    @keyframes bounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-4px)} }
</style>
@endpush

@section('content')
@php
    $authUser   = auth()->user();
    $other      = $conversation->type === 'direct'
        ? $conversation->participants->firstWhere('id', '!=', $authUser->id)
        : null;
    $title      = $other?->name ?? $conversation->name ?? 'Group chat';
@endphp

<div class="flex flex-col h-[calc(100vh-7rem)] -mt-6 -mx-6 overflow-hidden rounded-none sm:rounded-2xl sm:mx-0 sm:mt-0 sm:h-[calc(100vh-9rem)]"
     x-data="chatThread({{ $conversation->id }}, {{ $authUser->id }}, @js($messages->items()))">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 flex-shrink-0">
        @if($other)
        <img src="{{ $other->avatar_url }}" class="w-9 h-9 rounded-full ring-2 ring-violet-200 dark:ring-violet-800 flex-shrink-0" alt="">
        @else
        <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-people-fill text-violet-500 text-sm"></i>
        </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-slate-900 dark:text-white text-sm leading-tight">{{ $title }}</p>
            @if($other)
            <p class="text-xs text-slate-400 mt-0.5">{{ $other->job_title ?? $other->email }}</p>
            @else
            <p class="text-xs text-slate-400 mt-0.5">{{ $conversation->participants->count() }} members</p>
            @endif
        </div>
    </div>

    {{-- Message thread --}}
    <div id="chat-thread"
         class="flex-1 overflow-y-auto px-5 py-4 space-y-1 bg-slate-50 dark:bg-slate-950"
         x-ref="thread"
         @scroll="onScroll">

        {{-- Load more --}}
        @if($messages->hasMorePages())
        <div class="text-center py-2">
            <a href="{{ $messages->previousPageUrl() }}"
               class="text-xs text-violet-500 hover:underline">Load earlier messages</a>
        </div>
        @endif

        <template x-for="msg in messages" :key="msg.id">
            <div class="flex gap-2.5"
                 :class="msg.user.id === authUserId ? 'flex-row-reverse' : ''">

                {{-- Avatar --}}
                <img :src="msg.user.avatar_url" class="w-8 h-8 rounded-full flex-shrink-0 self-end" alt="">

                <div class="msg-bubble flex flex-col"
                     :class="msg.user.id === authUserId ? 'items-end' : 'items-start'">

                    {{-- Name + time --}}
                    <div class="flex items-center gap-2 mb-0.5 px-1">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400"
                              x-show="msg.user.id !== authUserId" x-text="msg.user.name"></span>
                        <span class="text-xs text-slate-400 dark:text-slate-600"
                              x-text="formatTime(msg.created_at)"></span>
                    </div>

                    {{-- Bubble --}}
                    <div class="rounded-2xl px-3.5 py-2 text-sm leading-relaxed break-words"
                         :class="msg.user.id === authUserId
                            ? 'bg-violet-600 text-white rounded-br-sm'
                            : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-bl-sm'">
                        <template x-if="!msg.deleted">
                            <span x-text="msg.body"></span>
                        </template>
                        <template x-if="msg.deleted">
                            <span class="italic opacity-60">Message deleted</span>
                        </template>
                    </div>

                    {{-- Attachments --}}
                    <template x-if="msg.attachments && msg.attachments.length">
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <template x-for="att in msg.attachments" :key="att.id">
                                <div>
                                    <template x-if="att.is_image">
                                        <a :href="att.url" target="_blank" class="block">
                                            <img :src="att.url" class="max-w-[220px] max-h-[180px] rounded-xl object-cover border border-slate-200 dark:border-slate-700" alt="">
                                        </a>
                                    </template>
                                    <template x-if="!att.is_image">
                                        <a :href="att.url" target="_blank"
                                           class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-violet-600 hover:border-violet-400 transition-colors">
                                            <i class="bi bi-paperclip"></i>
                                            <span x-text="att.original_name" class="truncate max-w-[160px]"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Delete button (own messages only) --}}
                    <button x-show="msg.user.id === authUserId && !msg.deleted"
                            @click="deleteMessage(msg)"
                            class="mt-0.5 text-[10px] text-slate-400 hover:text-rose-500 transition-colors px-1 self-end">
                        Delete
                    </button>
                </div>
            </div>
        </template>

        {{-- Typing indicator --}}
        <div x-show="typingUsers.length > 0" class="flex items-center gap-2.5 pl-1">
            <div class="flex -space-x-1.5">
                <template x-for="u in typingUsers.slice(0,3)" :key="u.id">
                    <img :src="u.avatar_url" class="w-6 h-6 rounded-full ring-2 ring-slate-50 dark:ring-slate-950">
                </template>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <span class="typing-dots">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                </span>
                <span x-text="typingUsers.map(u=>u.name.split(' ')[0]).join(', ') + ' is typing…'"></span>
            </div>
        </div>

        <div x-ref="bottom"></div>
    </div>

    {{-- Compose bar --}}
    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 flex-shrink-0">
        {{-- Attachment previews --}}
        <div x-show="pendingFiles.length > 0" class="flex flex-wrap gap-2 mb-2">
            <template x-for="(f, i) in pendingFiles" :key="i">
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 text-xs text-violet-700 dark:text-violet-300">
                    <i class="bi bi-paperclip text-xs"></i>
                    <span x-text="f.name" class="max-w-[120px] truncate"></span>
                    <button @click="pendingFiles.splice(i,1)" class="text-violet-400 hover:text-violet-700 ml-0.5">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </template>
        </div>

        <form @submit.prevent="sendMessage" class="flex items-end gap-2">
            {{-- File picker --}}
            <label class="flex-shrink-0 cursor-pointer p-2 rounded-xl text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-paperclip text-lg"></i>
                <input type="file" class="hidden" multiple @change="pickFiles($event)">
            </label>

            {{-- Text input --}}
            <textarea
                x-model="draft"
                @keydown.enter.exact.prevent="sendMessage"
                @keydown="onKeydown"
                x-ref="compose"
                rows="1"
                placeholder="Message…"
                class="flex-1 form-input resize-none py-2 text-sm leading-relaxed min-h-[40px] max-h-[120px]"
                style="overflow-y: auto; field-sizing: content;"
            ></textarea>

            {{-- Send button --}}
            <button type="submit"
                    :disabled="!draft.trim() && pendingFiles.length === 0"
                    class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-40 disabled:cursor-not-allowed text-white transition-colors">
                <i class="bi bi-send-fill text-sm"></i>
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function chatThread(conversationId, authUserId, initialMessages) {
    return {
        conversationId,
        authUserId,
        messages: initialMessages.map(m => ({ ...m, deleted: !m.body && m.deleted_at })).reverse(),
        draft: '',
        pendingFiles: [],
        typingUsers: [],
        typingTimers: {},
        sending: false,

        init() {
            this.$nextTick(() => this.scrollToBottom());

            window.Echo.private(`conversation.${this.conversationId}`)
                .listen('.message.sent', (data) => {
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

        onScroll() {},

        onKeydown: Alpine.$debounce(function() {
            window.Echo.private(`conversation.${this.conversationId}`)
                .whisper('typing', {
                    id: this.authUserId,
                    name: document.querySelector('meta[name="user-name"]')?.content ?? '',
                    avatar_url: document.querySelector('meta[name="user-avatar"]')?.content ?? '',
                });
        }, 500),

        pickFiles(e) {
            this.pendingFiles = [...this.pendingFiles, ...Array.from(e.target.files)];
            e.target.value = '';
        },

        async sendMessage() {
            if (!this.draft.trim() && this.pendingFiles.length === 0) return;
            if (this.sending) return;
            this.sending = true;

            const form = new FormData();
            if (this.draft.trim()) form.append('body', this.draft.trim());
            this.pendingFiles.forEach(f => form.append('attachments[]', f));

            const optimistic = {
                id: Date.now(),
                body: this.draft.trim(),
                created_at: new Date().toISOString(),
                user: { id: this.authUserId },
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

        formatTime(iso) {
            const d = new Date(iso);
            const now = new Date();
            if (d.toDateString() === now.toDateString()) {
                return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' +
                   d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
@endpush
