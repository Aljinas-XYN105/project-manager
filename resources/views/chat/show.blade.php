@extends(($popup ?? false) ? 'layouts.chat-popup' : 'layouts.app')
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
    display: flex; align-items: center; gap: 8px;
    background: var(--ct-input-bg);
    border: 1.5px solid var(--ct-input-border);
    border-radius: 16px; padding: 6px 6px 6px 14px;
    transition: border-color .15s;
}
.ct-input-wrap:focus-within { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ct-textarea {
    flex: 1; background: none; border: none; outline: none;
    font-size: 14px; line-height: 1.5; min-height: 24px; max-height: 120px;
    overflow-y: auto; color: var(--ct-name);
    font-family: inherit; word-break: break-word; white-space: pre-wrap;
}
/* contenteditable placeholder */
.ct-textarea:empty::before {
    content: attr(data-placeholder);
    color: var(--ct-time); pointer-events: none;
}
.ct-textarea:focus { outline: none; }
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

/* Mention chips */
.ct-chip {
    display: inline-flex; align-items: center; gap: 4px;
    border-radius: 6px; padding: 1px 7px; font-size: 12px; font-weight: 500;
    text-decoration: none; cursor: pointer; transition: opacity .12s;
}
.ct-chip:hover { opacity: .8; }
.ct-chip.task { background: var(--ct-task-chip-bg); color: var(--ct-task-chip-fg); border: 1px solid var(--ct-task-chip-border); }
.ct-chip.subtask { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.dark .ct-chip.subtask { background: #052e16; color: #86efac; border-color: #14532d; }
.ct-chip.member { background: var(--ct-chip-bg); color: var(--ct-chip-fg); border: 1px solid var(--ct-chip-border); }
.ct-bubble.out .ct-chip { background: rgba(255,255,255,.2); color: white; border-color: rgba(255,255,255,.35); }

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

<div class="ct-thread {{ ($popup ?? false) ? '' : '-mt-6 -mx-4 lg:-mx-5' }}"
     style="height:calc(100vh - {{ ($popup ?? false) ? '0px' : '7rem' }})"
     x-data="chatThread({{ $conversation->id }}, {{ $authUser->id }}, @js($messages->items()))">

    {{-- Header --}}
    <div class="ct-header">
        @if(!($popup ?? false))
        <a href="{{ route('chat.index') }}"
           class="ct-btn-icon flex-shrink-0" title="Back" style="margin-right:-4px">
            <i class="bi bi-arrow-left"></i>
        </a>
        @endif

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

        <div class="flex-1 min-w-0 {{ $conversation->type === 'group' ? 'cursor-pointer select-none' : '' }}"
             @if($conversation->type === 'group') @click="$dispatch('open-group-info')" @endif>
            <p class="ct-header-title truncate {{ $conversation->type === 'group' ? 'hover:underline' : '' }}">{{ $title }}</p>
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

        @if(!($popup ?? false) && $conversation->type === 'direct')
        <button type="button"
                onclick="window.open('{{ route('chat.popup') }}?open={{ $conversation->id }}', 'chatpopup', 'width=820,height=680,resizable=yes,menubar=no,toolbar=no,location=no,status=no')"
                class="ct-btn-icon flex-shrink-0" title="Open in window">
            <i class="bi bi-box-arrow-up-right" style="font-size:13px"></i>
        </button>
        @endif
    </div>

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
                    Mention
                </div>
                <template x-for="(item, i) in mentionResults" :key="item.kind + '_' + item.id">
                    <div class="ct-mention-item" :class="i === mentionIndex ? 'active' : ''"
                         @mousedown.prevent="insertMention(item)">

                        {{-- Avatar/icon per kind --}}
                        <template x-if="item.kind === 'member' && item.avatar_url">
                            <img :src="item.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" alt="">
                        </template>
                        <template x-if="item.kind === 'member' && !item.avatar_url">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0"
                                  style="background:var(--ct-chip-bg)">
                                <i class="bi bi-person-fill text-xs" style="color:var(--ct-chip-fg)"></i>
                            </span>
                        </template>
                        <template x-if="item.kind === 'task'">
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                  style="background:var(--ct-task-chip-bg)">
                                <i class="bi bi-check-square-fill text-xs" style="color:var(--ct-task-chip-fg)"></i>
                            </span>
                        </template>
                        <template x-if="item.kind === 'subtask'">
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                  style="background:#f0fdf4">
                                <i class="bi bi-diagram-3-fill text-xs" style="color:#166534"></i>
                            </span>
                        </template>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium truncate" style="color:var(--ct-name)" x-text="item.title"></span>
                                <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0 capitalize"
                                      :style="item.kind === 'member'  ? 'background:var(--ct-chip-bg);color:var(--ct-chip-fg)' :
                                              item.kind === 'subtask' ? 'background:#f0fdf4;color:#166534' :
                                                                        'background:var(--ct-task-chip-bg);color:var(--ct-task-chip-fg)'"
                                      x-text="item.kind"></span>
                            </div>
                            <div class="text-xs opacity-60 truncate" style="color:var(--ct-preview)"
                                 x-text="item.subtitle || item.project_name || item.parent_name || ''"></div>
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

                    <div
                        contenteditable="true"
                        x-ref="compose"
                        x-init="$nextTick(() => $el.focus())"
                        @keydown.enter.exact.prevent="sendMessage"
                        @keydown.arrow-up.prevent="if (mentionOpen) mentionIndex = Math.max(0, mentionIndex-1)"
                        @keydown.arrow-down.prevent="if (mentionOpen) mentionIndex = Math.min(mentionResults.length-1, mentionIndex+1)"
                        @keydown.escape="mentionOpen = false"
                        @keydown.tab.prevent="if (mentionOpen && mentionResults[mentionIndex]) insertMention(mentionResults[mentionIndex])"
                        @input="onInput"
                        @keydown="onKeydown"
                        @paste.prevent="onPaste($event)"
                        @blur="$nextTick(() => { mentionOpen = false })"
                        data-placeholder="Type a message… (@ to mention task, subtask or member)"
                        class="ct-textarea"
                        role="textbox"
                        aria-multiline="true"
                        spellcheck="true"
                    ></div>

                    <button type="submit"
                            :disabled="!draft && pendingFiles.length === 0"
                            class="ct-send-btn">
                        <i class="bi bi-send-fill" style="font-size:13px;margin-left:1px"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($conversation->type === 'group')
{{-- Group info modal --}}
<div x-data="groupInfoModal({{ $conversation->id }}, @js($conversation->participants->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'avatar_url'=>$p->avatar_url,'is_online'=>$p->is_online,'job_title'=>$p->job_title??''])->values()), @js($users->map(fn($u)=>['id'=>$u->id,'name'=>$u->name,'avatar_url'=>$u->avatar_url,'job_title'=>$u->job_title??''])->values()))"
     x-init="groupName = {{ json_encode($conversation->name ?? 'Group chat') }}"
     @open-group-info.document="open = true">
    <template x-teleport="body">
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(15,23,42,.6);backdrop-filter:blur(4px)"
             @click.self="open = false">
            <div class="w-full max-w-md rounded-2xl overflow-hidden flex flex-col"
                 style="background:var(--ct-header-bg);border:1px solid var(--ct-header-border);box-shadow:0 25px 60px rgba(0,0,0,.3);max-height:85vh"
                 @click.stop>

                {{-- Modal header --}}
                <div class="flex items-center justify-between px-5 py-4 flex-shrink-0"
                     style="border-bottom:1px solid var(--ct-header-border)">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center"
                             style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                            <i class="bi bi-people-fill text-white text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5" x-show="!editingName" @click="startEditName()">
                                <h2 class="font-bold text-sm truncate" style="color:var(--ct-name)" x-text="groupName"></h2>
                                <i class="bi bi-pencil flex-shrink-0" style="font-size:10px;color:var(--ct-preview)"></i>
                            </div>
                            <form x-show="editingName" @submit.prevent="saveName()" class="flex items-center gap-1.5">
                                <input type="text" x-model="groupNameDraft" x-ref="nameInput"
                                       maxlength="100"
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
                            <p class="text-xs" style="color:var(--ct-preview)" x-text="`${members.length} members`"></p>
                        </div>
                    </div>
                    <button @click="open = false"
                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                            style="color:var(--ct-preview)"
                            onmouseover="this.style.background='var(--ct-bubble-in-bg)'"
                            onmouseout="this.style.background=''">
                        <i class="bi bi-x text-xl"></i>
                    </button>
                </div>

                {{-- Add member search --}}
                <div class="px-4 py-3 flex-shrink-0" style="border-bottom:1px solid var(--ct-header-border)">
                    <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:var(--ct-preview)">Add member</p>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ct-preview)"></i>
                            <input type="text" x-model="addSearch" placeholder="Search people to add…"
                                   class="w-full text-sm rounded-lg px-3 py-2 outline-none"
                                   style="background:var(--ct-input-bg);border:1.5px solid var(--ct-input-border);color:var(--ct-name);padding-left:28px"
                                   @focus="addOpen=true" @blur="$nextTick(()=>{ addOpen=false })">
                            <div x-show="addOpen && addResults.length > 0" x-cloak
                                 class="absolute left-0 right-0 top-full mt-1 rounded-xl overflow-hidden z-10"
                                 style="background:var(--ct-header-bg);border:1px solid var(--ct-header-border);box-shadow:0 8px 24px rgba(0,0,0,.18)">
                                <template x-for="u in addResults" :key="u.id">
                                    <div class="flex items-center gap-2.5 px-3 py-2 cursor-pointer transition-colors"
                                         style="border-bottom:1px solid var(--ct-header-border)"
                                         onmouseover="this.style.background='var(--ct-bubble-in-bg)'"
                                         onmouseout="this.style.background=''"
                                         @mousedown.prevent="addMember(u)">
                                        <img :src="u.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium truncate" style="color:var(--ct-name)" x-text="u.name"></div>
                                            <div class="text-xs truncate" style="color:var(--ct-preview)" x-text="u.job_title"></div>
                                        </div>
                                        <i class="bi bi-plus-circle text-sm flex-shrink-0" style="color:#7c3aed"></i>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Members list --}}
                <div class="overflow-y-auto flex-1 py-1">
                    <p class="text-xs font-semibold uppercase tracking-wide px-4 pt-2 pb-1" style="color:var(--ct-preview)">Members</p>
                    <template x-for="m in members" :key="m.id">
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <div class="relative flex-shrink-0">
                                <img :src="m.avatar_url" class="w-9 h-9 rounded-full object-cover">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2"
                                      :style="`background:${m.is_online ? '#22c55e' : 'var(--ct-preview)'};border-color:var(--ct-header-bg)`"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate" style="color:var(--ct-name)" x-text="m.name"></div>
                                <div class="text-xs truncate" style="color:var(--ct-preview)" x-text="m.job_title || (m.is_online ? 'Online' : 'Offline')"></div>
                            </div>
                            <template x-if="m.id === {{ $authUser->id }}">
                                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                      style="background:var(--ct-chip-bg);color:var(--ct-chip-fg)">You</span>
                            </template>
                            <template x-if="m.id !== {{ $authUser->id }}">
                                <button @click="removeMember(m)"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                                        style="color:var(--ct-preview)"
                                        title="Remove from group"
                                        onmouseover="this.style.color='#ef4444';this.style.background='#fee2e2'"
                                        onmouseout="this.style.color='var(--ct-preview)';this.style.background=''">
                                    <i class="bi bi-person-dash text-sm"></i>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
@endif

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

        // @ mention
        mentionOpen: false,
        mentionQuery: '',
        mentionResults: [],
        mentionIndex: 0,
        mentionStart: -1,
        allMentions: [],

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

            // Load all mention targets (members + tasks + subtasks)
            fetch('/api/tasks/brief', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(r => r.ok ? r.json() : {}).then(d => {
                this.allMentions = [
                    ...(d.members  || []),
                    ...(d.tasks    || []),
                    ...(d.subtasks || []),
                ];
            }).catch(() => {});

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

        // Extract markdown token string from contenteditable DOM
        extractText() {
            const el = this.$refs.compose;
            if (!el) return '';
            function walk(node) {
                let s = '';
                for (const child of node.childNodes) {
                    if (child.nodeType === Node.TEXT_NODE) {
                        s += child.textContent.replace(/ /g, ' ');
                    } else if (child.nodeName === 'BR') {
                        s += '\n';
                    } else if (child.nodeName === 'SPAN' && child.dataset.mention) {
                        const [kind, id] = child.dataset.mention.split(':');
                        s += `@[${child.dataset.mentionName}](${kind}:${id})`;
                    } else if (child.nodeName === 'DIV') {
                        s += '\n' + walk(child);
                    } else {
                        s += walk(child);
                    }
                }
                return s;
            }
            return walk(el).replace(/\n+$/, '');
        },

        onInput() {
            this.draft = this.extractText();

            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) { this.mentionOpen = false; return; }
            const focusNode = sel.focusNode;
            if (!focusNode || focusNode.nodeType !== Node.TEXT_NODE) { this.mentionOpen = false; return; }

            const textInNode = focusNode.textContent.slice(0, sel.focusOffset);
            const match = textInNode.match(/@([\w ]*)$/);
            if (match) {
                this.mentionQuery = match[1].toLowerCase().trim();
                this.mentionResults = this.allMentions.filter(item => {
                    const q = this.mentionQuery;
                    if (!q) return true;
                    return item.title.toLowerCase().includes(q) ||
                        (item.project_name || '').toLowerCase().includes(q) ||
                        (item.parent_name  || '').toLowerCase().includes(q) ||
                        (item.subtitle     || '').toLowerCase().includes(q);
                }).slice(0, 8);
                this.mentionOpen = this.mentionResults.length > 0;
                this.mentionIndex = 0;
            } else {
                this.mentionOpen = false;
            }
        },

        insertMention(item) {
            const el = this.$refs.compose;
            el.focus();
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) { this.mentionOpen = false; return; }

            const focusNode = sel.focusNode;
            if (!focusNode || focusNode.nodeType !== Node.TEXT_NODE) { this.mentionOpen = false; return; }

            const nodeText = focusNode.textContent;
            const focusOffset = sel.focusOffset;
            const atIdx = nodeText.lastIndexOf('@', focusOffset - 1);
            if (atIdx === -1) { this.mentionOpen = false; return; }

            const before = nodeText.slice(0, atIdx);
            const after  = nodeText.slice(focusOffset);

            // Build inline chip
            const chip = document.createElement('span');
            chip.className = `ct-chip ${item.kind}`;
            chip.contentEditable = 'false';
            chip.dataset.mention = `${item.kind}:${item.id}`;
            chip.dataset.mentionName = item.title;
            chip.style.userSelect = 'none';
            const icon = document.createElement('i');
            icon.className = item.kind === 'member'  ? 'bi bi-person-fill' :
                             item.kind === 'subtask' ? 'bi bi-diagram-3-fill' :
                                                       'bi bi-check-square-fill';
            icon.style.cssText = 'font-size:10px;margin-right:3px';
            chip.appendChild(icon);
            chip.appendChild(document.createTextNode(item.title));

            // Replace text node: before + chip + space+after
            const parent = focusNode.parentNode;
            focusNode.textContent = before;
            parent.insertBefore(chip, focusNode.nextSibling);
            const spaceNode = document.createTextNode(' ' + after);
            parent.insertBefore(spaceNode, chip.nextSibling);

            // Position cursor after the space
            const range = document.createRange();
            range.setStart(spaceNode, 1);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);

            this.mentionOpen = false;
            this.draft = this.extractText();
        },

        onPaste(e) {
            const text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
            this.draft = this.extractText();
        },

        mentionIcon(item) {
            if (item.kind === 'member')  return '<i class="bi bi-person-fill" style="font-size:10px"></i>';
            if (item.kind === 'subtask') return '<i class="bi bi-diagram-3-fill" style="font-size:10px"></i>';
            return '<i class="bi bi-check-square-fill" style="font-size:10px"></i>';
        },

        renderBody(body) {
            if (!body) return '';
            const esc = body
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/\n/g, '<br>');
            // task chips (amber)
            let out = esc.replace(/@\[([^\]]+)\]\(task:(\d+)\)/g,
                (_, title, id) => `<a href="/tasks/${id}" target="_blank" class="ct-chip task"><i class="bi bi-check-square-fill" style="font-size:10px"></i>${title}</a>`
            );
            // subtask chips (green)
            out = out.replace(/@\[([^\]]+)\]\(subtask:(\d+)\)/g,
                (_, title, id) => `<a href="/tasks/${id}" target="_blank" class="ct-chip subtask"><i class="bi bi-diagram-3-fill" style="font-size:10px"></i>${title}</a>`
            );
            // member chips (violet)
            out = out.replace(/@\[([^\]]+)\]\(member:(\d+)\)/g,
                (_, name) => `<span class="ct-chip member"><i class="bi bi-person-fill" style="font-size:10px"></i>${name}</span>`
            );
            return out;
        },

        pickFiles(e) {
            this.pendingFiles = [...this.pendingFiles, ...Array.from(e.target.files)];
            e.target.value = '';
        },

        async sendMessage() {
            const bodyText = this.extractText().trim();
            if (!bodyText && this.pendingFiles.length === 0) return;
            if (this.sending) return;
            this.sending = true;
            this.mentionOpen = false;

            const form = new FormData();
            if (bodyText) form.append('body', bodyText);
            this.pendingFiles.forEach(f => form.append('attachments[]', f));

            const optimistic = {
                id: `opt_${Date.now()}`,
                body: bodyText,
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
            // Clear contenteditable
            if (this.$refs.compose) this.$refs.compose.innerHTML = '';
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

function groupInfoModal(conversationId, initialMembers, allUsers) {
    return {
        open: false,
        members: initialMembers,
        allUsers: allUsers,
        addSearch: '',
        addOpen: false,
        groupName: document.querySelector('[data-group-name]')?.dataset.groupName || '',
        groupNameDraft: '',
        editingName: false,

        startEditName() {
            this.groupNameDraft = this.groupName;
            this.editingName = true;
            this.$nextTick(() => this.$refs.nameInput?.focus());
        },

        async saveName() {
            const name = this.groupNameDraft.trim();
            if (!name || name === this.groupName) { this.editingName = false; return; }
            const resp = await fetch(`/chat/${conversationId}/name`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name }),
            });
            if (resp.ok) {
                const data = await resp.json();
                this.groupName = data.name;
                // Update the page header title too
                const headerTitle = document.querySelector('.ct-header-title');
                if (headerTitle) headerTitle.textContent = data.name;
            }
            this.editingName = false;
        },

        get addResults() {
            const q = this.addSearch.toLowerCase().trim();
            const memberIds = new Set(this.members.map(m => m.id));
            return this.allUsers
                .filter(u => !memberIds.has(u.id) && (!q || u.name.toLowerCase().includes(q) || (u.job_title||'').toLowerCase().includes(q)))
                .slice(0, 6);
        },

        async addMember(user) {
            this.addSearch = '';
            this.addOpen = false;
            const resp = await fetch(`/chat/${conversationId}/members`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ user_id: user.id }),
            });
            if (resp.ok) {
                const conv = await resp.json();
                this.members = conv.participants.map(p => ({
                    id: p.id, name: p.name, avatar_url: p.avatar_url,
                    is_online: p.is_online ?? false, job_title: p.job_title ?? '',
                }));
            }
        },

        async removeMember(user) {
            if (!confirm(`Remove ${user.name} from the group?`)) return;
            const resp = await fetch(`/chat/${conversationId}/members/${user.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            if (resp.ok) {
                this.members = this.members.filter(m => m.id !== user.id);
            }
        },
    };
}
</script>
@endpush
