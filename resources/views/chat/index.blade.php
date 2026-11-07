@extends('layouts.app')
@section('title', 'Chat')

@push('styles')
<style>
/* WhatsApp-inspired chat list */
.chat-list-wrap {
    background: #fff;
}
.dark .chat-list-wrap { background: #111b21; }

.conv-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    cursor: pointer; border: none; background: none; width: 100%; text-align: left;
    text-decoration: none; color: inherit;
    transition: background .1s;
    border-bottom: 1px solid #f0f2f5;
}
.dark .conv-row { border-bottom-color: #1f2c33; }
.conv-row:hover, .conv-row.active { background: #f0f2f5; }
.dark .conv-row:hover, .dark .conv-row.active { background: #2a3942; }

.conv-name {
    font-weight: 600; font-size: 15px; color: #111827; line-height: 1.2;
}
.dark .conv-name { color: #e9edef; }
.conv-preview {
    font-size: 13px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dark .conv-preview { color: #8696a0; }
.conv-time {
    font-size: 11px; color: #667781; white-space: nowrap;
}
.dark .conv-time { color: #8696a0; }
.conv-badge {
    min-width: 20px; height: 20px; background: #00a884; color: white;
    border-radius: 10px; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 5px;
}

/* Search bar */
.search-bar-wa {
    background: #f0f2f5;
    border: none; border-radius: 8px;
    padding: 8px 12px 8px 36px;
    font-size: 14px; color: #111827; outline: none; width: 100%;
}
.dark .search-bar-wa {
    background: #2a3942; color: #d1d7db;
}
.search-bar-wa::placeholder { color: #8696a0; }

/* New group modal */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 200;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(2px);
}
.modal-card {
    background: white; border-radius: 16px; padding: 28px 24px;
    width: min(480px, calc(100vw - 32px)); max-height: 80vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.dark .modal-card { background: #1e2d35; }

/* Member chips */
.member-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: #e0f2fe; color: #0369a1;
    border-radius: 20px; padding: 3px 10px;
    font-size: 12px; font-weight: 500;
}
.dark .member-chip { background: #1a3a4a; color: #7dd3fc; }
.member-chip button { line-height: 1; color: inherit; opacity: .7; }
.member-chip button:hover { opacity: 1; }

/* Tabs */
.tab-btn {
    flex: 1; padding: 10px 0; font-size: 13px; font-weight: 600;
    text-align: center; border-bottom: 2px solid transparent;
    color: #667781; background: none; border-top: none; border-left: none; border-right: none;
    cursor: pointer; transition: color .15s, border-color .15s;
}
.dark .tab-btn { color: #8696a0; }
.tab-btn.active { color: #008069; border-bottom-color: #008069; }
.dark .tab-btn.active { color: #00a884; border-bottom-color: #00a884; }

/* Empty state */
.empty-chat {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-height: 300px; text-align: center; padding: 32px;
}
</style>
@endpush

@section('content')
@php
    $authUser = auth()->user();
    $dmConvs    = $conversations->where('type', 'direct');
    $groupConvs = $conversations->where('type', 'group');
@endphp

<div class="-mt-6 -mx-4 lg:-mx-5 flex flex-col chat-list-wrap"
     style="min-height: calc(100vh - 7rem);"
     x-data="chatIndex()">

    {{-- Header bar --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10 bg-white dark:bg-[#111b21]">
        <h1 class="text-lg font-bold text-slate-800 dark:text-white">Chats</h1>
        <div class="flex items-center gap-2">
            <button @click="showGroupModal = true"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-[#008069] hover:bg-[#017261] text-white text-xs font-semibold rounded-full transition-colors">
                <i class="bi bi-people-fill text-xs"></i>
                New Group
            </button>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition-colors">
                    <i class="bi bi-pencil-square text-sm"></i>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-1 w-60 bg-white dark:bg-[#1e2d35] rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden z-50">
                    <div class="px-3 pt-3 pb-2 border-b border-slate-100 dark:border-slate-700/50">
                        <div class="relative">
                            <i class="bi bi-search absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" placeholder="Search people…" x-model="dmSearch"
                                   class="search-bar-wa text-xs py-2">
                        </div>
                    </div>
                    <div class="max-h-52 overflow-y-auto py-1">
                        @foreach($users as $u)
                        <a href="{{ route('chat.direct', $u) }}"
                           class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                           x-show="!dmSearch || '{{ strtolower($u->name) }}'.includes(dmSearch.toLowerCase())">
                            <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover" alt="">
                            <div>
                                <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $u->name }}</div>
                                @if($u->job_title)
                                <div class="text-xs text-slate-500">{{ $u->job_title }}</div>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="px-4 py-2 bg-white dark:bg-[#111b21]">
        <div class="relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" x-model="search" placeholder="Search or start new chat"
                   class="search-bar-wa">
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111b21]">
        <button class="tab-btn" :class="tab === 'all' ? 'active' : ''" @click="tab = 'all'">
            All
            @if($conversations->count() > 0)
            <span class="ml-1 text-xs opacity-70">({{ $conversations->count() }})</span>
            @endif
        </button>
        <button class="tab-btn" :class="tab === 'direct' ? 'active' : ''" @click="tab = 'direct'">
            Direct <span class="ml-1 text-xs opacity-70">({{ $dmConvs->count() }})</span>
        </button>
        <button class="tab-btn" :class="tab === 'groups' ? 'active' : ''" @click="tab = 'groups'">
            Groups <span class="ml-1 text-xs opacity-70">({{ $groupConvs->count() }})</span>
        </button>
    </div>

    {{-- Conversation list --}}
    <div class="flex-1">
        @if($conversations->isEmpty())
        <div class="empty-chat">
            <div class="w-16 h-16 rounded-2xl bg-[#008069]/10 flex items-center justify-center mb-4">
                <i class="bi bi-chat-dots text-3xl text-[#008069]"></i>
            </div>
            <h3 class="font-semibold text-slate-800 dark:text-white mb-1">No conversations yet</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Start a new message or create a group chat.</p>
        </div>
        @else
        @foreach($conversations as $conv)
        @php
            $other = $conv->type === 'direct'
                ? $conv->participants->firstWhere('id', '!=', $authUser->id)
                : null;
            $convTitle = $other?->name ?? $conv->name ?? 'Group chat';
            $convType  = $conv->type;
            $lastMsg   = $conv->latestMessage;
            $preview   = $lastMsg ? (($lastMsg->user_id === $authUser->id ? 'You: ' : '') . \Illuminate\Support\Str::limit(strip_tags($lastMsg->body ?? ''), 48)) : 'No messages yet';
            $time      = $lastMsg?->created_at?->diffForHumans(null, true, true) ?? '';
            $unread    = $conv->unread_count ?? 0;
            $memberCount = $conv->participants->count();
        @endphp
        <a href="{{ route('chat.show', $conv) }}"
           class="conv-row {{ request()->route('conversation')?->id === $conv->id ? 'active' : '' }}"
           x-show="(tab === 'all' || tab === '{{ $convType }}') &&
                   (!search || '{{ strtolower($convTitle) }}'.includes(search.toLowerCase()))">

            {{-- Avatar --}}
            <div class="flex-shrink-0 relative">
                @if($other)
                <img src="{{ $other->avatar_url }}" class="w-12 h-12 rounded-full object-cover" alt="">
                @else
                <div class="w-12 h-12 rounded-full bg-[#008069]/20 flex items-center justify-center">
                    <i class="bi bi-people-fill text-[#008069] text-lg"></i>
                </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="conv-name truncate">{{ $convTitle }}</span>
                    <span class="conv-time flex-shrink-0">{{ $time }}</span>
                </div>
                <div class="flex items-center justify-between gap-2 mt-0.5">
                    <span class="conv-preview flex-1 min-w-0">
                        @if($conv->type === 'group')
                        <i class="bi bi-people-fill text-[10px] mr-0.5 text-slate-400"></i>
                        {{ $memberCount }} members ·
                        @endif
                        {{ $preview }}
                    </span>
                    @if($unread > 0)
                    <span class="conv-badge flex-shrink-0">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
        @endif
    </div>
</div>

{{-- New Group Modal --}}
<div x-data="groupModal()" @open-group-modal.window="open = true">
    <div x-show="showGroupModal || open" x-cloak class="modal-overlay"
         @click.self="showGroupModal = false; open = false"
         x-bind:class="{'hidden': !showGroupModal && !open}">
        <div class="modal-card" @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-slate-800 dark:text-white">New Group</h2>
                <button @click="showGroupModal = false; open = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl">
                    <i class="bi bi-x"></i>
                </button>
            </div>

            <form @submit.prevent="createGroup">
                {{-- Group name --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Group name</label>
                    <input type="text" x-model="groupName" required maxlength="100"
                           placeholder="e.g. Frontend team, Sprint #12…"
                           class="form-input w-full">
                </div>

                {{-- Member search --}}
                <div class="mb-3">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Add members
                        <span x-show="selected.length > 0" class="font-normal text-slate-500 ml-1" x-text="`(${selected.length} selected)`"></span>
                    </label>
                    <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" x-model="memberSearch" placeholder="Search members…"
                               class="search-bar-wa pl-9 !rounded-lg border border-slate-200 dark:border-slate-700 py-2">
                    </div>
                </div>

                {{-- Selected chips --}}
                <div x-show="selected.length > 0" class="flex flex-wrap gap-1.5 mb-3">
                    <template x-for="m in selected" :key="m.id">
                        <span class="member-chip">
                            <img :src="m.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(m.name)}&size=24`"
                                 class="w-4 h-4 rounded-full object-cover">
                            <span x-text="m.name.split(' ')[0]"></span>
                            <button type="button" @click="deselect(m)">
                                <i class="bi bi-x text-xs"></i>
                            </button>
                        </span>
                    </template>
                </div>

                {{-- Member list --}}
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden mb-5 max-h-52 overflow-y-auto">
                    @foreach($users as $u)
                    <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0"
                           x-show="!memberSearch || '{{ strtolower($u->name) }}'.includes(memberSearch.toLowerCase())"
                           @click.prevent="toggle({{ json_encode(['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]) }})">
                        <img src="{{ $u->avatar_url }}" class="w-9 h-9 rounded-full flex-shrink-0 object-cover" alt="">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $u->name }}</div>
                            @if($u->job_title)
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $u->job_title }}</div>
                            @endif
                        </div>
                        <div class="flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                             :class="isSelected({{ $u->id }}) ? 'bg-[#00a884] border-[#00a884]' : 'border-slate-300 dark:border-slate-600'">
                            <i x-show="isSelected({{ $u->id }})" class="bi bi-check text-white text-xs font-bold"></i>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Submit --}}
                <div class="flex gap-3">
                    <button type="button"
                            @click="showGroupModal = false; open = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="!groupName.trim() || selected.length < 2 || loading"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-[#008069] text-white text-sm font-semibold hover:bg-[#017261] disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!loading">Create group</span>
                        <span x-show="loading">Creating…</span>
                    </button>
                </div>
                <p x-show="selected.length < 2 && selected.length > 0" class="text-xs text-amber-600 mt-2 text-center">
                    Add at least 2 members to create a group.
                </p>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function chatIndex() {
    return {
        tab: 'all',
        search: '',
        dmSearch: '',
        showGroupModal: false,
    };
}

function groupModal() {
    return {
        open: false,
        groupName: '',
        memberSearch: '',
        selected: [],
        loading: false,

        toggle(member) {
            const idx = this.selected.findIndex(m => m.id === member.id);
            if (idx === -1) this.selected.push(member);
            else this.selected.splice(idx, 1);
        },
        deselect(member) {
            this.selected = this.selected.filter(m => m.id !== member.id);
        },
        isSelected(id) {
            return this.selected.some(m => m.id === id);
        },
        async createGroup() {
            if (!this.groupName.trim() || this.selected.length < 2) return;
            this.loading = true;
            try {
                const resp = await fetch('/chat/groups', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.groupName.trim(),
                        member_ids: this.selected.map(m => m.id),
                    }),
                });
                const data = await resp.json();
                if (data.id) {
                    window.location.href = `/chat/${data.id}`;
                } else {
                    alert(data.message || 'Failed to create group.');
                }
            } catch (e) {
                alert('Something went wrong. Please try again.');
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
