@extends('layouts.chat-popup')
@section('title', 'Messages')

@push('styles')
<style>
/* ── Popup hub ─────────────────────────────────────── */
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
    --ci-modal-bg: #ffffff;
    --ci-chip-bg: #ede9fe;
    --ci-chip-fg: #5b21b6;
    --ci-online: #22c55e;
    --ci-offline: #94a3b8;
}
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

html, body { height: 100%; overflow: hidden; }

.ph-wrap {
    display: flex; height: 100vh; overflow: hidden;
    background: var(--ci-bg);
}

/* Left sidebar */
.ph-sidebar {
    width: 280px; min-width: 280px; max-width: 280px;
    display: flex; flex-direction: column;
    background: var(--ci-bg);
    border-right: 1px solid var(--ci-header-border);
    overflow: hidden;
}
.ph-sidebar-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px 10px;
    background: var(--ci-header-bg);
    border-bottom: 1px solid var(--ci-header-border);
    flex-shrink: 0;
}
.ph-sidebar-title {
    font-size: 15px; font-weight: 700; color: var(--ci-name);
}
.ph-sidebar-list {
    flex: 1; overflow-y: auto;
}

.conv-row {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; cursor: pointer;
    text-decoration: none; color: inherit;
    transition: background .1s;
    border-left: 3px solid transparent;
}
.conv-row:hover { background: var(--ci-row-hover); }
.conv-row.active {
    background: var(--ci-row-active);
    border-left-color: var(--ci-row-active-border);
}
.conv-name { font-weight: 600; font-size: 13.5px; color: var(--ci-name); }
.conv-preview { font-size: 12px; color: var(--ci-preview); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
.conv-time { font-size: 11px; color: var(--ci-time); white-space: nowrap; }
.conv-badge {
    min-width: 17px; height: 17px;
    background: var(--ci-badge-bg); color: var(--ci-badge-fg);
    border-radius: 9px; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 4px;
    flex-shrink: 0;
}
.status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    border: 2px solid var(--ci-header-bg);
    position: absolute; bottom: 0; right: 0;
}
.status-dot.online  { background: var(--ci-online); }
.status-dot.offline { background: var(--ci-offline); }
.conv-sep { height: 1px; background: var(--ci-sep); margin: 0 14px; }

.ph-search-wrap {
    padding: 8px 12px;
    background: var(--ci-header-bg);
    border-bottom: 1px solid var(--ci-sep);
    flex-shrink: 0;
}
.ph-search {
    width: 100%; background: var(--ci-search-bg);
    border: 1px solid var(--ci-search-border);
    border-radius: 8px; padding: 6px 10px 6px 30px;
    font-size: 13px; color: var(--ci-name); outline: none;
}
.ph-search:focus { border-color: #7c3aed; }
.ph-search::placeholder { color: var(--ci-time); }

.ph-btn {
    width: 30px; height: 30px; border-radius: 8px; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--ci-preview); background: none; font-size: 14px;
    transition: color .15s, background .15s;
}
.ph-btn:hover { color: #7c3aed; background: var(--ci-row-hover); }
.ph-btn-primary {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 10px; border-radius: 8px; border: none; cursor: pointer;
    background: linear-gradient(135deg,#7c3aed,#6d28d9);
    color: white; font-size: 12px; font-weight: 600;
    box-shadow: 0 2px 6px rgba(124,58,237,.35);
    transition: transform .1s;
}
.ph-btn-primary:hover { transform: scale(1.03); }

/* Right iframe pane */
.ph-frame {
    flex: 1; border: none; height: 100vh;
    background: var(--ci-bg);
}

/* Empty state right pane */
.ph-empty {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 10px; color: var(--ci-preview);
}
.ph-empty-icon {
    width: 64px; height: 64px; border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    background: var(--ci-avatar-bg); font-size: 28px;
    color: var(--ci-avatar-fg);
}

/* Modal */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(15,23,42,.6);
    z-index: 200; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.modal-card {
    background: var(--ci-modal-bg);
    border-radius: 20px; padding: 24px 20px;
    width: min(460px, calc(100vw - 32px));
    max-height: 85vh; overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,.25);
    border: 1px solid rgba(124,58,237,.2);
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
@endphp

<div class="ph-wrap"
     x-data="{
         search: '',
         activeId: {{ $openConv?->id ?? 'null' }},
         dmOpen: false,
         dmSearch: '',
     }">

    {{-- ── LEFT SIDEBAR ───────────────────────────── --}}
    <div class="ph-sidebar">

        {{-- Header --}}
        <div class="ph-sidebar-header">
            <span class="ph-sidebar-title">Messages</span>
            <div class="flex items-center gap-1">
                {{-- Pop-in: open main app chat --}}
                <button onclick="(window.opener ? window.opener : window).location.href='{{ route('chat.index') }}'; if(window.opener) window.close();"
                        class="ph-btn" title="Open in main app">
                    <i class="bi bi-arrows-angle-contract"></i>
                </button>
                {{-- New DM dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="ph-btn" title="New direct message">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 mt-1 w-56 rounded-2xl overflow-hidden z-50"
                         style="background:var(--ci-modal-bg); border:1px solid var(--ci-header-border); box-shadow:0 8px 32px rgba(0,0,0,.18);">
                        <div class="px-3 pt-2.5 pb-2" style="border-bottom:1px solid var(--ci-sep)">
                            <div class="relative">
                                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
                                <input type="text" x-model="dmSearch" placeholder="Search people…"
                                       class="ci-search text-xs py-1.5" style="padding-left:26px; font-size:12px">
                            </div>
                        </div>
                        <div class="max-h-48 overflow-y-auto py-1">
                            @foreach($users as $u)
                            <a href="{{ route('chat.window', \App\Models\Conversation::directBetween($authUser, $u)) }}"
                               target="chat-frame"
                               class="flex items-center gap-2 px-3 py-2 transition-colors"
                               style="text-decoration:none;"
                               x-show="!dmSearch || '{{ strtolower($u->name) }}'.includes(dmSearch.toLowerCase())"
                               @click="activeId = null; open = false"
                               onmouseover="this.style.background='var(--ci-row-hover)'"
                               onmouseout="this.style.background=''">
                                <div class="relative flex-shrink-0">
                                    <img src="{{ $u->avatar_url }}" class="w-7 h-7 rounded-full object-cover" alt="">
                                    <span class="status-dot {{ $u->is_online ? 'online' : 'offline' }}"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-medium truncate" style="color:var(--ci-name)">{{ $u->name }}</div>
                                    <div class="text-xs" style="color:var(--ci-time)">
                                        {{ $u->is_online ? ucfirst($u->availability ?? 'online') : 'Offline' }}
                                    </div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                {{-- New Group --}}
                <button @click="$dispatch('open-group-modal')" class="ph-btn" title="New group">
                    <i class="bi bi-people-fill"></i>
                </button>
            </div>
        </div>

        {{-- Search --}}
        <div class="ph-search-wrap">
            <div class="relative">
                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
                <input type="text" x-model="search" placeholder="Search…" class="ph-search">
            </div>
        </div>

        {{-- Conversation list --}}
        <div class="ph-sidebar-list">
            @if($conversations->isEmpty())
            <div class="flex flex-col items-center py-12 px-4 text-center">
                <i class="bi bi-chat-dots text-3xl mb-2" style="color:var(--ci-avatar-fg)"></i>
                <p class="text-sm font-medium" style="color:var(--ci-name)">No conversations yet</p>
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
                    ? (($lastMsg->user_id === $authUser->id ? 'You: ' : '') . \Str::limit(strip_tags($lastMsg->body ?? 'Attachment'), 40))
                    : 'No messages yet';
                $timeLabel = $lastMsg?->created_at?->diffForHumans(null, true, true) ?? '';
                $unread    = $conv->unread_count ?? 0;
            @endphp
            <a href="{{ route('chat.window', $conv) }}"
               target="chat-frame"
               class="conv-row"
               :class="activeId === {{ $conv->id }} ? 'active' : ''"
               @click="activeId = {{ $conv->id }}"
               x-show="!search || '{{ strtolower($convTitle) }}'.includes(search.toLowerCase())">

                <div class="relative flex-shrink-0">
                    @if($other)
                    <img src="{{ $other->avatar_url }}" class="w-10 h-10 rounded-full object-cover" alt="">
                    <span class="status-dot {{ $other->is_online ? 'online' : 'offline' }}"></span>
                    @else
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:var(--ci-avatar-bg)">
                        <i class="bi bi-people-fill text-sm" style="color:var(--ci-avatar-fg)"></i>
                    </div>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1 mb-0.5">
                        <span class="conv-name truncate">{{ $convTitle }}</span>
                        <div class="flex items-center gap-1 flex-shrink-0">
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
                        <span class="conv-preview {{ $unread > 0 ? 'font-semibold' : '' }}"
                              style="{{ $unread > 0 ? 'color:var(--ci-name)' : '' }}">{{ $preview }}</span>
                    </div>
                </div>
            </a>
            <div class="conv-sep"></div>
            @endforeach
            @endif
        </div>
    </div>

    {{-- ── RIGHT PANE (iframe) ────────────────────── --}}
    @if($openConv)
    <iframe name="chat-frame" class="ph-frame"
            src="{{ route('chat.window', $openConv) }}"></iframe>
    @else
    <div class="ph-empty">
        <div class="ph-empty-icon"><i class="bi bi-chat-text"></i></div>
        <p class="font-semibold text-sm" style="color:var(--ci-name)">Select a conversation</p>
        <p class="text-xs" style="color:var(--ci-time)">Pick one from the sidebar to start chatting</p>
    </div>
    @endif
</div>

{{-- New Group Modal --}}
<div x-data="groupModal()" @open-group-modal.window="showGroupModal = true">
    <template x-teleport="body">
        <div x-show="showGroupModal" x-cloak class="modal-overlay" @click.self="showGroupModal = false">
            <div class="modal-card" @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold" style="color:var(--ci-name)">New Group</h2>
                        <p class="text-xs mt-0.5" style="color:var(--ci-preview)">Create a group with 2 or more members</p>
                    </div>
                    <button @click="showGroupModal = false"
                            class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
                            style="color:var(--ci-preview)"
                            onmouseover="this.style.background='var(--ci-row-hover)'"
                            onmouseout="this.style.background=''">
                        <i class="bi bi-x text-xl"></i>
                    </button>
                </div>
                <form @submit.prevent="createGroup">
                    <div class="mb-3">
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--ci-preview)">Group name</label>
                        <input type="text" x-model="groupName" required maxlength="100"
                               placeholder="e.g. Frontend team…" class="form-input w-full text-sm">
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--ci-preview)">
                            Add members
                            <span x-show="selected.length > 0" class="normal-case font-normal ml-1"
                                  x-text="`— ${selected.length} selected`"></span>
                        </label>
                        <div class="relative">
                            <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--ci-time)"></i>
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
                    <div class="rounded-xl overflow-hidden mb-4 max-h-44 overflow-y-auto"
                         style="border:1px solid var(--ci-search-border)">
                        @foreach($users as $u)
                        <label class="flex items-center gap-3 px-3 py-2 cursor-pointer transition-colors"
                               style="border-bottom:1px solid var(--ci-sep)"
                               x-show="!memberSearch || '{{ strtolower($u->name) }}'.includes(memberSearch.toLowerCase())"
                               @click.prevent="toggle({{ json_encode(['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]) }})"
                               onmouseover="this.style.background='var(--ci-row-hover)'"
                               onmouseout="this.style.background=''">
                            <div class="relative flex-shrink-0">
                                <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full object-cover" alt="">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate" style="color:var(--ci-name)">{{ $u->name }}</div>
                                <div class="text-xs" style="color:var(--ci-time)">{{ $u->job_title ?? '' }}</div>
                            </div>
                            <div :class="selected.find(s => s.id === {{ $u->id }}) ? 'opacity-100' : 'opacity-0'"
                                 class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 transition-opacity"
                                 style="background:#7c3aed">
                                <i class="bi bi-check text-white text-xs"></i>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <button type="submit" :disabled="creating || !groupName.trim() || selected.length < 2"
                            class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                            style="background:linear-gradient(135deg,#7c3aed,#6d28d9)"
                            x-text="creating ? 'Creating…' : 'Create Group'"></button>
                </form>
            </div>
        </div>
    </template>
</div>
@push('scripts')
<script>
function groupModal() {
    return {
        showGroupModal: false,
        groupName: '',
        memberSearch: '',
        selected: [],
        creating: false,
        toggle(user) {
            const idx = this.selected.findIndex(s => s.id === user.id);
            if (idx >= 0) this.selected.splice(idx, 1);
            else this.selected.push(user);
        },
        deselect(user) {
            this.selected = this.selected.filter(s => s.id !== user.id);
        },
        async createGroup() {
            if (this.creating || !this.groupName.trim() || this.selected.length < 2) return;
            this.creating = true;
            try {
                const resp = await fetch('{{ route("chat.groups.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.groupName,
                        member_ids: this.selected.map(s => s.id),
                    }),
                });
                if (!resp.ok) throw new Error('Failed');
                const conv = await resp.json();
                this.showGroupModal = false;
                this.groupName = '';
                this.selected = [];
                // Load the new group in the iframe
                const frame = document.querySelector('iframe[name="chat-frame"]');
                if (frame) frame.src = `/chat/${conv.id}/window`;
                window.location.reload();
            } catch (e) {
                alert('Could not create group. Please try again.');
            } finally {
                this.creating = false;
            }
        },
    };
}
</script>
@endpush
@endsection
