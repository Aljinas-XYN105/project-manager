@extends('layouts.app')
@section('title', 'Chat')

@push('styles')
<style>
/* ── Chat index ─────────────────────────────────────────── */
.ci-wrap { background: var(--ci-bg); height: calc(100vh - 7rem); overflow: hidden; }

/* Light tokens */
:root {
    --ci-bg: #f8fafc;
    --ci-header-bg: #ffffff;
    --ci-header-border: #e2e8f0;
    --ci-row-hover: #f1f5f9;
    --ci-row-active: #ede9fe;
    --ci-row-active-border: #7c3aed;
    --ci-name: #1e293b;
    --ci-preview: #64748b;
    --ci-time: #94a3b8;
    --ci-avatar-bg: #ede9fe;
    --ci-avatar-fg: #7c3aed;
    --ci-badge-bg: #7c3aed;
    --ci-badge-fg: #ffffff;
    --ci-search-bg: #f1f5f9;
    --ci-search-border: #e2e8f0;
    --ci-sep: #f1f5f9;
    --ci-tab-active: #7c3aed;
    --ci-tab-inactive: #94a3b8;
    --ci-modal-bg: #ffffff;
    --ci-chip-bg: #ede9fe;
    --ci-chip-fg: #5b21b6;
    --ci-online: #22c55e;
    --ci-offline: #94a3b8;
}
/* Dark tokens */
.dark {
    --ci-bg: #0f172a;
    --ci-header-bg: #1e293b;
    --ci-header-border: #334155;
    --ci-row-hover: #1e293b;
    --ci-row-active: #2e1065;
    --ci-row-active-border: #7c3aed;
    --ci-name: #f1f5f9;
    --ci-preview: #94a3b8;
    --ci-time: #64748b;
    --ci-avatar-bg: #2e1065;
    --ci-avatar-fg: #a78bfa;
    --ci-badge-bg: #7c3aed;
    --ci-badge-fg: #ffffff;
    --ci-search-bg: #1e293b;
    --ci-search-border: #334155;
    --ci-sep: #1e293b;
    --ci-modal-bg: #1e293b;
    --ci-chip-bg: #2e1065;
    --ci-chip-fg: #c4b5fd;
    --ci-online: #22c55e;
    --ci-offline: #475569;
}

.conv-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 16px; cursor: pointer;
    text-decoration: none; color: inherit;
    transition: background .12s;
    border-left: 3px solid transparent;
    position: relative;
}
.conv-row:hover { background: var(--ci-row-hover); }
.conv-row.active {
    background: var(--ci-row-active);
    border-left-color: var(--ci-row-active-border);
}

.conv-sep {
    height: 1px;
    background: var(--ci-sep);
    margin: 0 16px;
}
.conv-name {
    font-weight: 600; font-size: 14px;
    color: var(--ci-name); line-height: 1.2;
}
.conv-preview {
    font-size: 12.5px; color: var(--ci-preview);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.conv-time { font-size: 11px; color: var(--ci-time); white-space: nowrap; }

.conv-badge {
    min-width: 18px; height: 18px;
    background: var(--ci-badge-bg); color: var(--ci-badge-fg);
    border-radius: 9px; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 4px;
    flex-shrink: 0;
}

.ci-search {
    background: var(--ci-search-bg);
    border: 1px solid var(--ci-search-border);
    border-radius: 10px;
    padding: 7px 12px 7px 34px;
    font-size: 13.5px;
    color: var(--ci-name); outline: none; width: 100%;
    transition: border-color .15s;
}
.ci-search:focus { border-color: #7c3aed; }
.ci-search::placeholder { color: var(--ci-time); }

.ci-tab {
    flex: 1; padding: 9px 0; font-size: 12.5px; font-weight: 600;
    text-align: center;
    border-bottom: 2px solid transparent;
    color: var(--ci-tab-inactive);
    background: none; border-top: none; border-left: none; border-right: none;
    cursor: pointer; transition: color .15s, border-color .15s;
}
.ci-tab.active { color: var(--ci-tab-active); border-bottom-color: var(--ci-tab-active); }

.status-dot {
    width: 9px; height: 9px; border-radius: 50%;
    border: 2px solid var(--ci-header-bg);
    position: absolute; bottom: 0; right: 0;
}
.status-dot.online { background: var(--ci-online); }
.status-dot.offline { background: var(--ci-offline); }
.status-dot.busy { background: #f59e0b; }

.modal-overlay {
    position: fixed; inset: 0; background: rgba(15,23,42,.6);
    z-index: 200; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.modal-card {
    background: var(--ci-modal-bg);
    border-radius: 20px; padding: 28px 24px;
    width: min(500px, calc(100vw - 32px));
    max-height: 85vh; overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,.25);
    border: 1px solid rgba(124,58,237,.2);
}
.member-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--ci-chip-bg); color: var(--ci-chip-fg);
    border-radius: 20px; padding: 3px 10px; font-size: 12px; font-weight: 500;
}
</style>
@endpush

@section('content')
@php
    $authUser = auth()->user();
    $curId    = request()->route('conversation')?->id;
@endphp

<div class="ci-wrap -mt-6 -mx-4 lg:-mx-5 flex flex-col"
     x-data="chatIndex({{ $curId ?? 'null' }})">

    {{-- Header --}}
    <div class="flex-shrink-0 px-4 py-3 flex items-center justify-between gap-3"
         style="background:var(--ci-header-bg); border-bottom:1px solid var(--ci-header-border);">
        <div class="flex items-center gap-2">
            <h1 class="text-base font-bold" style="color:var(--ci-name)">Messages</h1>
            <button type="button"
                    onclick="window.open('{{ route('chat.popup') }}', 'chatpopup', 'width=820,height=680,resizable=yes,menubar=no,toolbar=no,location=no,status=no')"
                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
                    style="color:var(--ci-time)"
                    title="Open in popup window"
                    onmouseover="this.style.color='#7c3aed';this.style.background='var(--ci-row-hover)'"
                    onmouseout="this.style.color='var(--ci-time)';this.style.background=''">
                <i class="bi bi-box-arrow-up-right" style="font-size:12px"></i>
            </button>
        </div>
        <div class="flex items-center gap-1.5">
            <button @click="$dispatch('open-group-modal')"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all"
                    style="background: linear-gradient(135deg,#7c3aed,#6d28d9); box-shadow:0 2px 8px rgba(124,58,237,.35);">
                <i class="bi bi-people-fill text-xs"></i> New Group
            </button>
            <div x-data="{ open:false }" class="relative">
                <button @click="open = !open"
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                        style="color:var(--ci-preview);"
                        :style="open ? 'background:var(--ci-row-hover)' : ''"
                        title="New direct message">
                    <i class="bi bi-pencil-square text-sm"></i>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-1 w-64 rounded-2xl overflow-hidden z-50"
                     style="background:var(--ci-modal-bg); border:1px solid var(--ci-header-border); box-shadow:0 8px 32px rgba(0,0,0,.18);">
                    <div class="px-3 pt-3 pb-2" style="border-bottom:1px solid var(--ci-sep)">
                        <div class="relative">
                            <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
                            <input type="text" x-model="dmSearch" placeholder="Search people…"
                                   class="ci-search text-xs py-2" style="padding-left:28px">
                        </div>
                    </div>
                    <div class="max-h-52 overflow-y-auto py-1">
                        @foreach($users as $u)
                        <a href="{{ route('chat.direct', $u) }}"
                           class="flex items-center gap-2.5 px-3 py-2 transition-colors"
                           style="text-decoration:none;"
                           x-show="!dmSearch || '{{ strtolower($u->name) }}'.includes(dmSearch.toLowerCase())"
                           onmouseover="this.style.background='var(--ci-row-hover)'"
                           onmouseout="this.style.background=''">
                            <div class="relative flex-shrink-0">
                                <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full object-cover" alt="">
                                <span class="status-dot {{ $u->is_online ? 'online' : 'offline' }}"></span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium truncate" style="color:var(--ci-name)">{{ $u->name }}</div>
                                <div class="text-xs" style="color:var(--ci-time)">
                                    {{ $u->is_online ? ucfirst($u->availability ?? 'online') : 'Offline' }}
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="flex-shrink-0 px-3 py-2.5" style="background:var(--ci-header-bg); border-bottom:1px solid var(--ci-sep)">
        <div class="relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
            <input type="text" x-model="search" placeholder="Search conversations…" class="ci-search">
        </div>
    </div>

    {{-- Conversation list --}}
    <div class="flex-1 overflow-y-auto" style="background:var(--ci-bg)">
        @if($conversations->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                 style="background:var(--ci-avatar-bg)">
                <i class="bi bi-chat-dots text-3xl" style="color:var(--ci-avatar-fg)"></i>
            </div>
            <p class="font-semibold mb-1" style="color:var(--ci-name)">No conversations yet</p>
            <p class="text-sm" style="color:var(--ci-preview)">Start a DM or create a group above.</p>
        </div>
        @else
        @foreach($conversations as $conv)
        @php
            $other = $conv->type === 'direct'
                ? $conv->participants->firstWhere('id', '!=', $authUser->id)
                : null;
            $convTitle = $other?->name ?? $conv->name ?? 'Group chat';
            $lastMsg   = $conv->latestMessage;
            $preview   = $lastMsg
                ? (($lastMsg->user_id === $authUser->id ? 'You: ' : '') . \Str::limit(strip_tags($lastMsg->body ?? 'Attachment'), 50))
                : 'No messages yet';
            $timeLabel = $lastMsg?->created_at?->diffForHumans(null, true, true) ?? '';
            $unread    = $conv->unread_count ?? 0;
            $memberCount = $conv->participants->count();
        @endphp
        <a href="{{ route('chat.show', $conv) }}"
           class="conv-row {{ $curId === $conv->id ? 'active' : '' }}"
           x-show="!search || '{{ strtolower($convTitle) }}'.includes(search.toLowerCase())">

            <div class="relative flex-shrink-0">
                @if($other)
                <img src="{{ $other->avatar_url }}" class="w-11 h-11 rounded-full object-cover" alt="">
                <span class="status-dot {{ $other->is_online ? 'online' : 'offline' }}"></span>
                @else
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:var(--ci-avatar-bg)">
                    <i class="bi bi-people-fill text-base" style="color:var(--ci-avatar-fg)"></i>
                </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1 mb-0.5">
                    <span class="conv-name truncate">{{ $convTitle }}</span>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($unread > 0)
                        <span class="conv-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                        @endif
                        <span class="conv-time">{{ $timeLabel }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    @if($conv->type === 'group')
                    <i class="bi bi-people text-xs flex-shrink-0" style="color:var(--ci-time)"></i>
                    @endif
                    <span class="conv-preview {{ $unread > 0 ? 'font-semibold' : '' }}" style="{{ $unread > 0 ? 'color:var(--ci-name)' : '' }}">{{ $preview }}</span>
                </div>
            </div>
        </a>
        <div class="conv-sep"></div>
        @endforeach
        @endif
    </div>
</div>

{{-- New Group Modal --}}
<div x-data="groupModal()" @open-group-modal.window="showGroupModal = true">
    <template x-teleport="body">
        <div x-show="showGroupModal" x-cloak class="modal-overlay" @click.self="showGroupModal = false">
            <div class="modal-card" @click.stop>
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-bold" style="color:var(--ci-name)">New Group</h2>
                        <p class="text-xs mt-0.5" style="color:var(--ci-preview)">Create a group with 2 or more members</p>
                    </div>
                    <button @click="showGroupModal = false"
                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                            style="color:var(--ci-preview)"
                            onmouseover="this.style.background='var(--ci-row-hover)'"
                            onmouseout="this.style.background=''">
                        <i class="bi bi-x text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="createGroup">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5"
                               style="color:var(--ci-preview)">Group name</label>
                        <input type="text" x-model="groupName" required maxlength="100"
                               placeholder="e.g. Frontend team, Sprint #12…"
                               class="form-input w-full text-sm">
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5"
                               style="color:var(--ci-preview)">
                            Add members
                            <span x-show="selected.length > 0" class="normal-case font-normal ml-1"
                                  x-text="`— ${selected.length} selected`"></span>
                        </label>
                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
                            <input type="text" x-model="memberSearch" placeholder="Search members…" class="ci-search">
                        </div>
                    </div>

                    <div x-show="selected.length > 0" class="flex flex-wrap gap-1.5 mb-3">
                        <template x-for="m in selected" :key="m.id">
                            <span class="member-chip">
                                <img :src="m.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(m.name)}&size=24`"
                                     class="w-4 h-4 rounded-full object-cover">
                                <span x-text="m.name.split(' ')[0]" class="text-xs"></span>
                                <button type="button" @click="deselect(m)" class="opacity-60 hover:opacity-100 ml-0.5">
                                    <i class="bi bi-x text-xs"></i>
                                </button>
                            </span>
                        </template>
                    </div>

                    <div class="rounded-xl overflow-hidden mb-5 max-h-52 overflow-y-auto"
                         style="border:1px solid var(--ci-search-border)">
                        @foreach($users as $u)
                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors"
                               style="border-bottom:1px solid var(--ci-sep)"
                               x-show="!memberSearch || '{{ strtolower($u->name) }}'.includes(memberSearch.toLowerCase())"
                               @click.prevent="toggle({{ json_encode(['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]) }})"
                               onmouseover="this.style.background='var(--ci-row-hover)'"
                               onmouseout="this.style.background=''">
                            <div class="relative flex-shrink-0">
                                <img src="{{ $u->avatar_url }}" class="w-9 h-9 rounded-full object-cover" alt="">
                                <span class="status-dot {{ $u->is_online ? 'online' : 'offline' }}"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate" style="color:var(--ci-name)">{{ $u->name }}</div>
                                <div class="text-xs" style="color:var(--ci-time)">
                                    {{ $u->is_online ? ucfirst($u->availability ?? 'online') : 'Offline' }}
                                </div>
                            </div>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all"
                                 :style="isSelected({{ $u->id }}) ? 'background:#7c3aed;border-color:#7c3aed' : 'border-color:var(--ci-search-border)'">
                                <i x-show="isSelected({{ $u->id }})" class="bi bi-check text-white text-xs font-bold"></i>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="showGroupModal = false"
                                class="flex-1 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                                style="border:1px solid var(--ci-search-border); color:var(--ci-preview)"
                                onmouseover="this.style.background='var(--ci-row-hover)'"
                                onmouseout="this.style.background=''">
                            Cancel
                        </button>
                        <button type="submit"
                                :disabled="!groupName.trim() || selected.length < 2 || loading"
                                class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-40"
                                style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                            <span x-show="!loading"><i class="bi bi-people-fill mr-1.5"></i>Create group</span>
                            <span x-show="loading">Creating…</span>
                        </button>
                    </div>
                    <p x-show="selected.length === 1" class="text-xs text-amber-500 mt-2 text-center">
                        Add at least one more member.
                    </p>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function chatIndex(currentConvId) {
    return {
        search: '',
        dmSearch: '',
        currentConvId,
    };
}
function groupModal() {
    return {
        showGroupModal: false,
        groupName: '',
        memberSearch: '',
        selected: [],
        loading: false,
        toggle(m) {
            const i = this.selected.findIndex(x => x.id === m.id);
            i === -1 ? this.selected.push(m) : this.selected.splice(i, 1);
        },
        deselect(m) { this.selected = this.selected.filter(x => x.id !== m.id); },
        isSelected(id) { return this.selected.some(x => x.id === id); },
        async createGroup() {
            if (!this.groupName.trim() || this.selected.length < 2) return;
            this.loading = true;
            try {
                const r = await fetch('/chat/groups', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.groupName.trim(), member_ids: this.selected.map(m => m.id) }),
                });
                const d = await r.json();
                if (d.id) window.location.href = `/chat/${d.id}`;
                else alert(d.message || 'Failed.');
            } catch { alert('Something went wrong.'); }
            finally { this.loading = false; }
        },
    };
}
</script>
@endpush
