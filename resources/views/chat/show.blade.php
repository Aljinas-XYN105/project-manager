@extends('layouts.app')
@section('title', $conversation->type === 'direct'
    ? ($conversation->participants->firstWhere('id', '!=', auth()->id())?->name ?? 'Chat')
    : ($conversation->name ?? 'Group chat'))

@push('styles')
<style>
/* ── Chat thread ───────────────────────────────────────────── */
:root {
    --ct-bg: #f1f5f9;
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

/* Layout */
.ct-thread {
    display: flex; flex-direction: column; overflow: hidden;
    background: var(--ct-bg);
    height: calc(100vh - 7rem);
}

/* Header */
.ct-header {
    background: var(--ct-header-bg);
    border-bottom: 1px solid var(--ct-header-border);
    padding: 10px 16px;
    display: flex; align-items: center; gap: 12px;
    flex-shrink: 0;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.ct-header-title { font-weight: 700; font-size: 15px; color: var(--ct-name); }
.ct-header-sub { font-size: 12px; color: var(--ct-preview); }
.ct-header-sub.online { color: var(--ct-online); }

/* Status dot */
.ct-dot {
    width: 10px; height: 10px; border-radius: 50%;
    border: 2px solid var(--ct-header-bg);
    position: absolute; bottom: 0; right: 0;
}
.ct-dot.online { background: var(--ct-online); }
.ct-dot.offline { background: var(--ct-offline); }
.ct-dot.busy { background: #f59e0b; }

/* Messages area */
.ct-messages {
    flex: 1; overflow-y: auto; padding: 16px 16px 8px;
    display: flex; flex-direction: column; gap: 2px;
    scroll-behavior: smooth;
}

/* Date separator */
.ct-sep {
    display: flex; align-items: center; gap: 10px; margin: 10px 0;
}
.ct-sep::before, .ct-sep::after {
    content: ''; flex: 1; height: 1px; background: var(--ct-sep-line);
}
.ct-sep span {
    font-size: 11px; font-weight: 600; color: var(--ct-sep-text);
    padding: 2px 10px; white-space: nowrap;
    background: var(--ct-bg); border-radius: 10px;
    border: 1px solid var(--ct-sep-line);
}

/* Message rows */
.ct-row { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 1px; }
.ct-row.out { flex-direction: row-reverse; }

/* Bubbles */
.ct-bubble {
    max-width: 68%; padding: 10px 13px 7px; line-height: 1.5;
    font-size: 14px; word-break: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
    position: relative;
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

/* Sender name in group */
.ct-sender-name {
    font-size: 11px; font-weight: 700; margin-bottom: 2px; margin-left: 2px;
}

/* Compose */
.ct-compose {
    background: var(--ct-compose-bg);
    border-top: 1px solid var(--ct-compose-border);
    padding: 10px 12px 12px;
    flex-shrink: 0;
}
.ct-input-wrap {
    display: flex; align-items: flex-end; gap: 8px;
    background: var(--ct-input-bg);
    border: 1.5px solid var(--ct-input-border);
    border-radius: 16px; padding: 6px 6px 6px 14px;
    transition: border-color .15s;
}
.ct-input-wrap:focus-within { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ct-textarea {
    flex: 1; background: none; border: none; outline: none; resize: none;
    font-size: 14px; line-height: 1.5; min-height: 24px; max-height: 120px;
    overflow-y: auto; color: var(--ct-name);
    font-family: inherit;
    field-sizing: content;
}
.ct-textarea::placeholder { color: var(--ct-time); }
.ct-btn-icon {
    width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--ct-action-btn); background: none;
    font-size: 16px; flex-shrink: 0; transition: color .15s, background .15s;
}
.ct-btn-icon:hover { color: var(--ct-action-hover); background: rgba(124,58,237,.08); }
.ct-send-btn {
    width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    background: var(--ct-send-bg); color: white; font-size: 15px; flex-shrink: 0;
    transition: transform .12s, box-shadow .12s, opacity .15s;
    box-shadow: 0 2px 8px rgba(124,58,237,.4);
}
.ct-send-btn:hover:not(:disabled) { transform: scale(1.08); box-shadow: 0 4px 14px rgba(124,58,237,.5); }
.ct-send-btn:disabled { opacity: .4; cursor: not-allowed; }

/* Attachment pill */
.ct-attach-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--ct-chip-bg); color: var(--ct-chip-fg);
    border: 1px solid var(--ct-chip-border);
    border-radius: 20px; font-size: 11.5px; padding: 2px 10px;
}
.ct-attach-pill.out { background: rgba(255,255,255,.18); color: white; border-color: rgba(255,255,255,.3); }

/* Task mention chip */
.ct-task-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: var(--ct-task-chip-bg); color: var(--ct-task-chip-fg);
    border: 1px solid var(--ct-task-chip-border);
    border-radius: 6px; padding: 1px 7px; font-size: 12px; font-weight: 500;
    text-decoration: none; cursor: pointer;
    transition: opacity .12s;
}
.ct-task-chip:hover { opacity: .8; }
.ct-bubble.out .ct-task-chip {
    background: rgba(255,255,255,.2); color: white; border-color: rgba(255,255,255,.35);
}

/* Mention popup */
.ct-mention-popup {
    position: absolute; bottom: calc(100% + 4px); left: 0; right: 0;
    background: var(--ct-mention-bg);
    border: 1px solid var(--ct-mention-border);
    border-radius: 14px; overflow: hidden; z-index: 100;
    max-height: 220px; overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0,0,0,.14);
}
.ct-mention-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; cursor: pointer; font-size: 13px;
    transition: background .1s;
}
.ct-mention-item:hover, .ct-mention-item.active { background: var(--ct-mention-hover); }

/* Deleted */
.ct-deleted { font-style: italic; opacity: .5; font-size: 13px; }
.ct-deleted i { font-size: 11px; margin-right: 4px; }

/* Typing indicator */
.ct-typing {
    display: flex; align-items: center; gap: 3px; padding: 8px 14px;
    background: var(--ct-bubble-in-bg); border-radius: 18px 18px 18px 4px;
    width: fit-content; border: 1px solid var(--ct-bubble-in-border);
}
.ct-typing span {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--ct-time);
    animation: ct-bounce .9s infinite;
    display: inline-block;
}
.ct-typing span:nth-child(2) { animation-delay: .15s; }
.ct-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes ct-bounce { 0%,60%,100%{transform:translateY(0);opacity:.4} 30%{transform:translateY(-5px);opacity:1} }

/* Reply-to / hover delete */
.ct-row:hover .ct-delete-btn { opacity: 1; }
.ct-delete-btn {
    opacity: 0; background: none; border: none; cursor: pointer;
    color: var(--ct-time); font-size: 12px; padding: 2px 4px;
    border-radius: 4px; transition: opacity .15s, color .15s;
}
.ct-delete-btn:hover { color: #ef4444; }
</style>
@endpush

@section('content')
@php
    $authUser    = auth()->user();
    $other       = $conversation->type === 'direct'
        ? $conversation->participants->firstWhere('id', '!=', $authUser->id)
        : null;
    $title       = $other?->name ?? $conversation->name ?? 'Group chat';
    $memberCount = $conversation->participants->count();
    $memberNames = $conversation->participants->pluck('name')->implode(', ');

    // last seen / status for DMs
    $statusLabel = '';
    if ($other) {
        if ($other->is_online) {
            $statusLabel = ucfirst($other->availability ?? 'Online');
        } elseif ($other->last_seen_at) {
            $statusLabel = 'Last seen ' . $other->last_seen_at->diffForHumans();
        } else {
            $statusLabel = 'Offline';
        }
    }
@endphp

<div class="ct-thread -mt-6 -mx-4 lg:-mx-5"
     x-data="chatThread({{ $conversation->id }}, {{ $authUser->id }}, @js($messages->items()))">

    {{-- Header --}}
    <div class="ct-header">
        <a href="{{ route('chat.index') }}"
           class="ct-btn-icon flex-shrink-0" title="Back" style="margin-right:-4px">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div class="relative flex-shrink-0">
            @if($other)
            <img src="{{ $other->avatar_url }}" class="w-10 h-10 rounded-full object-cover" alt="">
            <span class="ct-dot {{ $other->is_online ? 'online' : 'offline' }}"
                  id="ct-status-dot"></span>
            @else
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center"
                 style="background: linear-gradient(135deg,#7c3aed,#6d28d9)">
                <i class="bi bi-people-fill text-white text-sm"></i>
            </div>
            @endif
        </div>

        <div class="flex-1 min-w-0">
            <p class="ct-header-title truncate">{{ $title }}</p>
            <p class="ct-header-sub {{ ($other?->is_online) ? 'online' : '' }}"
               id="ct-status-label">
                @if($other)
                {{ $statusLabel }}
                @else
                <span x-show="typingUsers.length === 0">{{ $memberCount }} members</span>
                @endif
                <span x-show="typingUsers.length > 0" class="online">
                    <span x-text="typingUsers.map(u=>u.name.split(' ')[0]).join(', ')"></span> typing…
                </span>
            </p>
        </div>

        @if($conversation->type === 'group')
        <button @click="showInfo = !showInfo" class="ct-btn-icon flex-shrink-0" title="Group info">
            <i class="bi bi-info-circle"></i>
        </button>
        @endif
    </div>

    {{-- Group info flyout --}}
    @if($conversation->type === 'group')
    <div x-show="showInfo" x-cloak
         class="flex-shrink-0 px-4 py-3 text-sm"
         style="background:var(--ct-header-bg); border-bottom:1px solid var(--ct-header-border)">
        <div class="font-semibold mb-1" style="color:var(--ct-name)">Members</div>
        <div class="flex flex-wrap gap-2">
            @foreach($conversation->participants as $p)
            <div class="flex items-center gap-1.5">
                <div class="relative">
                    <img src="{{ $p->avatar_url }}" class="w-6 h-6 rounded-full object-cover" alt="">
                    <span class="ct-dot {{ $p->is_online ? 'online' : 'offline' }}" style="width:7px;height:7px"></span>
                </div>
                <span style="color:var(--ct-preview)">{{ $p->name }}</span>
                @if($p->id === $authUser->id)
                <span class="text-xs px-1.5 py-0.5 rounded-full" style="background:var(--ct-chip-bg);color:var(--ct-chip-fg)">You</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Messages --}}
    <div class="ct-messages" x-ref="thread">
        @if($messages->hasMorePages())
        <div class="text-center mb-3">
            <a href="{{ $messages->previousPageUrl() }}"
               class="text-xs px-3 py-1.5 rounded-full inline-flex items-center gap-1.5 transition-colors"
               style="background:var(--ct-bubble-in-bg);border:1px solid var(--ct-bubble-in-border);color:var(--ct-preview)">
                <i class="bi bi-arrow-up"></i> Load earlier messages
            </a>
        </div>
        @endif

        <template x-for="(msg, idx) in messages" :key="msg.id">
            <div>
                {{-- Date separator --}}
                <template x-if="idx === 0 || isDifferentDay(messages[idx-1]?.created_at, msg.created_at)">
                    <div class="ct-sep my-3">
                        <span x-text="formatDateSep(msg.created_at)"></span>
                    </div>
                </template>

                {{-- Group consecutive messages --}}
                <div class="ct-row" :class="msg.user?.id === authUserId ? 'out' : 'in'">

                    {{-- Avatar (others, group, only last in a run) --}}
                    <div class="flex flex-col justify-end flex-shrink-0 w-7">
                        <template x-if="msg.user?.id !== authUserId &&
                                        (idx === messages.length-1 || messages[idx+1]?.user?.id !== msg.user?.id)">
                            <img :src="msg.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user?.name ?? 'U')}&size=28`"
                                 class="w-7 h-7 rounded-full object-cover" alt="">
                        </template>
                    </div>

                    <div class="flex flex-col" :class="msg.user?.id === authUserId ? 'items-end' : 'items-start'"
                         style="max-width:72%">

                        {{-- Sender name (group, first in run) --}}
                        @if($conversation->type === 'group')
                        <template x-if="msg.user?.id !== authUserId &&
                                        (idx === 0 || messages[idx-1]?.user?.id !== msg.user?.id)">
                            <div class="ct-sender-name mb-1" :style="'color:' + senderColor(msg.user?.id)"
                                 x-text="msg.user?.name"></div>
                        </template>
                        @endif

                        <div class="flex items-end gap-1" :class="msg.user?.id === authUserId ? 'flex-row-reverse' : ''">
                            <div class="ct-bubble" :class="msg.user?.id === authUserId ? 'out' : 'in'">
                                <template x-if="!msg.deleted">
                                    <div>
                                        <span x-html="renderBody(msg.body)"></span>
                                        <template x-if="msg.attachments && msg.attachments.length">
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                <template x-for="att in msg.attachments" :key="att.id">
                                                    <div>
                                                        <template x-if="att.is_image">
                                                            <a :href="att.url" target="_blank" class="block">
                                                                <img :src="att.url" class="max-w-[200px] rounded-xl object-cover" alt="">
                                                            </a>
                                                        </template>
                                                        <template x-if="!att.is_image">
                                                            <a :href="att.url" target="_blank"
                                                               class="ct-attach-pill"
                                                               :class="msg.user?.id === authUserId ? 'out' : ''">
                                                                <i class="bi bi-file-earmark text-xs"></i>
                                                                <span x-text="att.original_name" class="max-w-[140px] truncate"></span>
                                                            </a>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <div class="ct-bubble-time">
                                            <span x-text="formatTime(msg.created_at)"></span>
                                            <template x-if="msg.user?.id === authUserId">
                                                <i class="bi bi-check2" style="font-size:11px"></i>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="msg.deleted">
                                    <span class="ct-deleted">
                                        <i class="bi bi-slash-circle"></i>Message deleted
                                    </span>
                                </template>
                            </div>

                            {{-- Delete button --}}
                            <template x-if="msg.user?.id === authUserId && !msg.deleted">
                                <button @click="deleteMessage(msg)" class="ct-delete-btn" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Typing indicator --}}
        <div x-show="typingUsers.length > 0" class="ct-row in" style="margin-top:4px">
            <div class="w-7 flex-shrink-0">
                <img :src="typingUsers[0]?.avatar_url || `https://ui-avatars.com/api/?name=U`"
                     class="w-7 h-7 rounded-full object-cover" alt="">
            </div>
            <div class="ct-typing">
                <span></span><span></span><span></span>
            </div>
        </div>

        <div x-ref="bottom"></div>
    </div>

    {{-- Compose --}}
    <div class="ct-compose">
        {{-- Pending file pills --}}
        <div x-show="pendingFiles.length > 0" class="flex flex-wrap gap-1.5 mb-2">
            <template x-for="(f, i) in pendingFiles" :key="i">
                <span class="ct-attach-pill">
                    <i class="bi bi-paperclip text-xs"></i>
                    <span x-text="f.name" class="max-w-[120px] truncate"></span>
                    <button @click="pendingFiles.splice(i,1)" type="button"
                            class="ml-1 opacity-60 hover:opacity-100">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </span>
            </template>
        </div>

        {{-- Mention popup --}}
        <div class="relative">
            <div x-show="mentionOpen && mentionResults.length > 0" x-cloak class="ct-mention-popup">
                <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide"
                     style="color:var(--ct-time);border-bottom:1px solid var(--ct-mention-border)">
                    Tasks
                </div>
                <template x-for="(t, i) in mentionResults" :key="t.id">
                    <div class="ct-mention-item" :class="i === mentionIndex ? 'active' : ''"
                         @click="insertMention(t)">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                              style="background:var(--ct-task-chip-bg)">
                            <i class="bi bi-check-square-fill text-xs" style="color:var(--ct-task-chip-fg)"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="font-medium truncate" style="color:var(--ct-name)" x-text="t.title"></div>
                            <div class="text-xs opacity-60" style="color:var(--ct-preview)" x-text="t.project_name"></div>
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="sendMessage">
                <div class="ct-input-wrap">
                    <label class="ct-btn-icon" title="Attach files" style="margin-bottom:1px">
                        <i class="bi bi-paperclip"></i>
                        <input type="file" class="hidden" multiple @change="pickFiles($event)">
                    </label>

                    <textarea
                        x-model="draft"
                        @keydown.enter.exact.prevent="sendMessage"
                        @keydown.shift.enter="null"
                        @keydown.arrow-up.prevent="if (mentionOpen) mentionIndex = Math.max(0, mentionIndex-1)"
                        @keydown.arrow-down.prevent="if (mentionOpen) mentionIndex = Math.min(mentionResults.length-1, mentionIndex+1)"
                        @keydown.escape="mentionOpen = false"
                        @keydown.tab.prevent="if (mentionOpen && mentionResults[mentionIndex]) insertMention(mentionResults[mentionIndex])"
                        @input="onInput"
                        @keydown="onKeydown"
                        x-ref="compose"
                        x-init="$nextTick(() => $el.focus())"
                        rows="1"
                        placeholder="Type a message… (@ to mention a task)"
                        class="ct-textarea"
                    ></textarea>

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
@endsection

@push('scripts')
<script>
/* ── Notification sound (Web Audio API) ──────────────────── */
function playNotifSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        // Two-tone ding
        [[880, 0, 0.12], [1100, 0.1, 0.1]].forEach(([freq, start, dur]) => {
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.type = 'sine'; o.frequency.value = freq;
            g.gain.setValueAtTime(0, ctx.currentTime + start);
            g.gain.linearRampToValueAtTime(0.18, ctx.currentTime + start + 0.02);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + dur);
            o.start(ctx.currentTime + start);
            o.stop(ctx.currentTime + start + dur + 0.05);
        });
    } catch (e) {}
}

/* ── Color palette for group senders ─────────────────────── */
const SENDER_COLORS = ['#7c3aed','#0891b2','#059669','#d97706','#dc2626','#c026d3','#2563eb'];

/* ── Main component ──────────────────────────────────────── */
function chatThread(conversationId, authUserId, initialMessages) {
    return {
        conversationId, authUserId,
        messages: [...initialMessages].reverse().map(m => ({ ...m, deleted: !!(m.deleted_at) })),
        draft: '',
        pendingFiles: [],
        typingUsers: [],
        typingTimers: {},
        sending: false,
        _whisperTimer: null,
        showInfo: false,

        // Task mention
        mentionOpen: false,
        mentionQuery: '',
        mentionResults: [],
        mentionIndex: 0,
        mentionStart: -1,
        allTasks: [],

        // Sender colours
        _colorMap: {},
        senderColor(uid) {
            if (!this._colorMap[uid]) {
                this._colorMap[uid] = SENDER_COLORS[Object.keys(this._colorMap).length % SENDER_COLORS.length];
            }
            return this._colorMap[uid];
        },

        init() {
            // scroll to bottom immediately
            this.$nextTick(() => {
                this.scrollToBottom(false);
                this.$refs.compose?.focus();
            });

            // Load tasks for @ mention
            fetch('/api/tasks/brief', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(r => r.ok ? r.json() : []).then(d => { this.allTasks = d; }).catch(() => {});

            // Echo
            window.Echo.private(`conversation.${this.conversationId}`)
                .listen('.message.sent', (data) => {
                    // avoid duplicate if own optimistic already added
                    if (data.user.id === this.authUserId) return;
                    this.messages.push({ ...data, deleted: false });
                    playNotifSound();
                    this.$nextTick(() => this.scrollToBottom(true));
                })
                .listen('.message.deleted', (data) => {
                    const msg = this.messages.find(m => m.id === data.id);
                    if (msg) { msg.body = ''; msg.deleted = true; }
                })
                .listenForWhisper('typing', (data) => {
                    if (data.id === this.authUserId) return;
                    if (!this.typingUsers.find(u => u.id === data.id)) this.typingUsers.push(data);
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
            const val = e.target.value;
            const pos = e.target.selectionStart;
            const before = val.slice(0, pos);
            const match = before.match(/@(\w*)$/);
            if (match) {
                this.mentionQuery = match[1].toLowerCase();
                this.mentionStart = before.lastIndexOf('@');
                this.mentionResults = this.allTasks.filter(t =>
                    t.title.toLowerCase().includes(this.mentionQuery) ||
                    (t.project_name || '').toLowerCase().includes(this.mentionQuery)
                ).slice(0, 7);
                this.mentionOpen = this.mentionResults.length > 0;
                this.mentionIndex = 0;
            } else {
                this.mentionOpen = false;
            }
        },

        insertMention(task) {
            const ta = this.$refs.compose;
            const pos = ta.selectionStart;
            this.draft = this.draft.slice(0, this.mentionStart) +
                `@[${task.title}](task:${task.id}) ` +
                this.draft.slice(pos);
            this.mentionOpen = false;
            this.$nextTick(() => {
                ta.focus();
                const np = this.mentionStart + task.title.length + 10;
                ta.setSelectionRange(np, np);
            });
        },

        renderBody(body) {
            if (!body) return '';
            const esc = body
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/\n/g, '<br>');
            return esc.replace(/@\[([^\]]+)\]\(task:(\d+)\)/g,
                (_, title, id) => `<a href="/tasks/${id}" target="_blank" class="ct-task-chip"><i class="bi bi-check-square-fill" style="font-size:10px"></i>${title}</a>`
            );
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
                id: `opt_${Date.now()}`,
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
            this.$nextTick(() => this.scrollToBottom(true));

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
                const idx = this.messages.findIndex(m => m.id === optimistic.id);
                if (idx !== -1) this.messages[idx] = { ...data, deleted: false };
            } catch (e) {
                this.messages = this.messages.filter(m => m.id !== optimistic.id);
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.compose?.focus());
            }
        },

        async deleteMessage(msg) {
            if (!confirm('Delete this message for everyone?')) return;
            await fetch(`/chat/messages/${msg.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            msg.body = ''; msg.deleted = true;
        },

        scrollToBottom(smooth = true) {
            this.$refs.bottom?.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant' });
        },

        isDifferentDay(isoA, isoB) {
            if (!isoA || !isoB) return false;
            return new Date(isoA).toDateString() !== new Date(isoB).toDateString();
        },

        formatDateSep(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            const now = new Date();
            const yesterday = new Date(now); yesterday.setDate(now.getDate()-1);
            if (d.toDateString() === now.toDateString()) return 'Today';
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return d.toLocaleDateString([], { weekday:'short', month:'short', day:'numeric' });
        },

        formatTime(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
        },
    };
}
</script>
@endpush
