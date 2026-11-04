@extends('layouts.app')
@section('title', 'Standups — ' . $date->format('D, M j'))

@section('content')

{{-- Avatar color palette (inline styles, Tailwind-JIT safe) --}}
@php
$avatarBgs   = ['#ede9fe','#dbeafe','#d1fae5','#fef3c7','#fee2e2','#e0e7ff','#ccfbf1','#fce7f3','#ddd6fe','#bfdbfe'];
$avatarTexts = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#be185d','#6d28d9','#1d4ed8'];
@endphp

<div x-data="standupDashboard()" @keydown.escape.window="closePanel()" class="relative">

    {{-- Main content shifts left when panel is open --}}
    <div :class="panelOpen ? 'lg:mr-[26rem]' : ''" class="transition-all duration-300">

        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                        <i class="bi bi-mic text-violet-600 dark:text-violet-400"></i>
                    </span>
                    Standups
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">Click a member to review their tasks during standup.</p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Date chip --}}
                <a href="{{ route('standups.calendar') }}?return_date={{ $date->toDateString() }}"
                   class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700
                          bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200
                          hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
                    <i class="bi bi-calendar3 text-violet-500 text-sm"></i>
                    @if($date->isToday())
                        Today, {{ $date->format('F j') }}
                    @else
                        {{ $date->format('l, F j') }}
                    @endif
                </a>

                @if(!$date->isToday())
                <a href="{{ route('standups.index') }}" class="btn-ghost btn-sm text-violet-600 dark:text-violet-400">
                    Today
                </a>
                @endif

                <a href="{{ route('standups.show', today()->toDateString()) }}" class="btn-primary">
                    <i class="bi bi-pencil-square text-sm"></i> My Standup
                </a>
            </div>
        </div>

        {{-- Past-date notice --}}
        @if(!$date->isToday())
        <div class="mb-5 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800
                    text-amber-700 dark:text-amber-300 text-sm flex items-center gap-2">
            <i class="bi bi-clock-history flex-shrink-0"></i>
            Viewing <strong>{{ $date->format('l, F j, Y') }}</strong> — past standups are read-only
        </div>
        @endif

        {{-- Stats row --}}
        @php
            $submittedCount = $standups->count();
            $totalCount     = $users->count();
            $pendingCount   = $totalCount - $submittedCount;
        @endphp
        <div class="flex items-center gap-5 mb-6 text-sm">
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $totalCount }} members</span>
            <span class="flex items-center gap-1.5 text-green-600 dark:text-green-400 font-medium">
                <i class="bi bi-check-circle-fill text-xs"></i>
                {{ $submittedCount }} submitted
            </span>
            @if($pendingCount > 0 && $date->isToday())
            <span class="flex items-center gap-1.5 text-slate-400 font-medium">
                <i class="bi bi-clock text-xs"></i>
                {{ $pendingCount }} pending
            </span>
            @endif
        </div>

        {{-- Card grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($users as $member)
            @php
                $standup      = $standups[$member->id] ?? null;
                $openItems    = $standup ? $standup->items->whereNotIn('status', ['done'])->count() : 0;
                $activeItems  = $standup ? $standup->items->whereIn('status', ['in_progress','in_review'])->count() : 0;
                $hasBlockers  = $standup && $standup->blockers;
                $colorIdx     = $member->id % count($avatarBgs);
                $avatarBg     = $avatarBgs[$colorIdx];
                $avatarText   = $avatarTexts[$colorIdx];
                $initials     = strtoupper(substr($member->name, 0, 2));
                $subtitle     = $member->job_title ?? $member->department ?? $member->email;
            @endphp

            <button type="button"
                    @click="openPanel({{ $member->id }}, '{{ addslashes($member->name) }}', '{{ addslashes($subtitle) }}')"
                    :class="activeMemberId === {{ $member->id }} ? 'ring-2 ring-violet-500 ring-offset-2 dark:ring-offset-slate-900' : ''"
                    class="group text-left bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700
                           shadow-sm hover:shadow-md hover:border-violet-300 dark:hover:border-violet-600
                           transition-all duration-200 p-5 flex flex-col gap-3 w-full">

                {{-- Top row: avatar + chevron --}}
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold text-base flex-shrink-0"
                         style="background: {{ $avatarBg }}; color: {{ $avatarText }}">
                        {{ $initials }}
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        @if($standup)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                         bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                <i class="bi bi-check-circle-fill text-[10px]"></i> Submitted
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                         bg-slate-100 dark:bg-slate-700 text-slate-400">
                                <i class="bi bi-clock text-[10px]"></i> Pending
                            </span>
                        @endif
                        @if($hasBlockers)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                     bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                            <i class="bi bi-exclamation-triangle-fill text-[10px]"></i> Blocked
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Name + role badges --}}
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $member->name }}</span>
                        @foreach($member->roles as $role)
                        @if($role->name !== 'member')
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide
                                     {{ $role->name === 'admin' ? 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300'
                                      : ($role->name === 'manager' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                      : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300') }}">
                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                        </span>
                        @endif
                        @endforeach
                    </div>
                    @if($subtitle)
                    <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $subtitle }}</p>
                    @endif
                </div>

                {{-- Task counts --}}
                <div class="flex items-center gap-4 pt-1 border-t border-slate-100 dark:border-slate-700/60">
                    <div class="flex flex-col">
                        <span class="text-lg font-bold text-slate-900 dark:text-white leading-tight">{{ $member->open_tasks ?? 0 }}</span>
                        <span class="text-[11px] text-slate-400 font-medium">Open</span>
                    </div>
                    <div class="w-px h-8 bg-slate-100 dark:bg-slate-700"></div>
                    <div class="flex flex-col">
                        <span class="text-lg font-bold {{ $member->active_tasks > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-900 dark:text-white' }} leading-tight">{{ $member->active_tasks ?? 0 }}</span>
                        <span class="text-[11px] text-slate-400 font-medium">Active</span>
                    </div>
                    <div class="ml-auto">
                        <i class="bi bi-chevron-right text-slate-300 dark:text-slate-600 group-hover:text-violet-500 transition-colors text-sm"></i>
                    </div>
                </div>

            </button>
            @empty
            <div class="col-span-4 text-center py-16 text-slate-400 text-sm">No team members found.</div>
            @endforelse
        </div>

    </div>{{-- /main content --}}

    {{-- Right-side slide panel --}}
    <div x-show="panelOpen"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         class="fixed inset-y-0 right-0 w-full sm:w-[26rem] z-40 flex flex-col
                bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-700 shadow-2xl"
         style="display: none;">

        {{-- Panel header --}}
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm flex-shrink-0"
                 :style="`background: ${activeBg}; color: ${activeText}`">
                <span x-text="initials"></span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-900 dark:text-white text-sm truncate" x-text="activeName"></p>
                <p class="text-xs text-slate-400 truncate" x-text="activeSubtitle"></p>
            </div>
            <button @click="closePanel()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1">
                <i class="bi bi-x-lg text-base"></i>
            </button>
        </div>

        {{-- Panel body --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Loading --}}
            <div x-show="loading" class="flex items-center justify-center py-16">
                <i class="bi bi-arrow-repeat animate-spin text-2xl text-violet-400"></i>
            </div>

            {{-- Content --}}
            <div x-show="!loading" class="p-5 space-y-4">

                {{-- Saved banner --}}
                <div x-show="saved" x-transition
                     class="px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800
                            text-green-700 dark:text-green-300 text-sm flex items-center gap-2">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    Standup saved!
                </div>

                {{-- Blockers --}}
                <template x-if="!editMode && standup && standup.blockers">
                    <div class="px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40 flex items-start gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
                        <div>
                            <p class="text-xs font-semibold text-rose-700 dark:text-rose-400 mb-0.5">Blocker</p>
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed" x-text="standup.blockers"></p>
                        </div>
                    </div>
                </template>

                {{-- View mode: kanban-style list --}}
                <template x-if="!editMode">
                    <div class="space-y-3">

                        {{-- No standup --}}
                        <template x-if="!standup || !standup.items || standup.items.length === 0">
                            <div class="text-center py-10">
                                <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                                    <i class="bi bi-calendar-x text-lg text-slate-400"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No standup submitted</p>
                                <p class="text-xs text-slate-400 mt-1" x-text="`${activeName} hasn't added anything yet.`"></p>
                            </div>
                        </template>

                        {{-- Items grouped by status --}}
                        <template x-if="standup && standup.items && standup.items.length > 0">
                            <div>
                                <template x-for="grp in groupedItems()" :key="grp.status">
                                    <div class="mb-3">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="w-2 h-2 rounded-full" :class="statusDot(grp.status)"></span>
                                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                                                  x-text="statusLabel(grp.status)"></span>
                                            <span class="text-xs text-slate-400" x-text="`(${grp.items.length})`"></span>
                                        </div>
                                        <template x-for="item in grp.items" :key="item.id">
                                            <div class="flex items-start gap-2 px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 mb-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0" :class="statusDot(item.status)"></span>
                                                <p class="text-sm text-slate-700 dark:text-slate-200 leading-snug" x-text="item.description"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Edit button --}}
                        @if($date->isToday())
                        <div class="pt-2">
                            <button @click="switchToEdit()"
                                    class="w-full btn-ghost text-sm flex items-center justify-center gap-2">
                                <i class="bi bi-pencil text-xs"></i>
                                <span x-text="standup ? 'Edit Standup' : 'Add Standup'"></span>
                            </button>
                        </div>
                        @endif
                    </div>
                </template>

                {{-- Edit mode form --}}
                <template x-if="editMode">
                    <div class="space-y-3">

                        {{-- Items --}}
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 space-y-2">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Tasks</h3>
                                <button type="button" @click="addItem()"
                                        class="text-xs text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                                    <i class="bi bi-plus-lg"></i> Add
                                </button>
                            </div>

                            <template x-for="(item, idx) in editItems" :key="item._id">
                                <div class="flex items-start gap-2 group">
                                    {{-- Status pill --}}
                                    <div class="relative flex-shrink-0 mt-0.5" x-data="{open:false}">
                                        <button type="button" @click="open=!open" @click.outside="open=false"
                                                :class="statusClass(item.status)"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold cursor-pointer whitespace-nowrap">
                                            <span x-text="statusLabel(item.status)"></span>
                                            <i class="bi bi-chevron-down text-[9px]"></i>
                                        </button>
                                        <div x-show="open" x-transition
                                             class="absolute left-0 top-full mt-1 w-32 rounded-xl shadow-lg bg-white dark:bg-slate-800
                                                    border border-slate-200 dark:border-slate-700 z-50 overflow-hidden">
                                            <template x-for="s in statuses" :key="s.value">
                                                <button type="button"
                                                        @click="item.status = s.value; open=false"
                                                        :class="item.status === s.value ? 'bg-slate-50 dark:bg-slate-700/50 font-semibold' : ''"
                                                        class="w-full text-left px-3 py-1.5 text-xs flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                                    <span :class="s.dot" class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                                    <span x-text="s.label" class="text-slate-700 dark:text-slate-200"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <input type="text" x-model="item.description"
                                           @keydown.enter.prevent="addItem()"
                                           placeholder="What are they working on?"
                                           class="flex-1 bg-transparent border-0 text-sm text-slate-700 dark:text-slate-200
                                                  placeholder-slate-300 dark:placeholder-slate-600 outline-none py-0.5 focus:outline-none" />

                                    <button type="button" @click="removeItem(idx)"
                                            class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-slate-300 hover:text-rose-500 mt-1">
                                        <i class="bi bi-x text-sm"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Blockers --}}
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4">
                            <label class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Blockers</label>
                            <textarea x-model="editBlockers" rows="2"
                                      placeholder="Any blockers?"
                                      class="form-input w-full resize-none text-sm"></textarea>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" @click="cancelEdit()"
                                    class="btn-ghost btn-sm flex-1">Cancel</button>
                            <button type="button" @click="save()"
                                    :disabled="saving"
                                    class="btn-primary btn-sm flex-1">
                                <span x-show="!saving"><i class="bi bi-check2 text-sm"></i> Save</span>
                                <span x-show="saving"><i class="bi bi-arrow-repeat animate-spin text-sm"></i> Saving…</span>
                            </button>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>{{-- /panel --}}

    {{-- Panel backdrop (mobile) --}}
    <div x-show="panelOpen"
         @click="closePanel()"
         class="fixed inset-0 bg-black/20 backdrop-blur-sm z-30 lg:hidden"
         style="display: none;"></div>

</div>

<script>
const STANDUP_DATE   = '{{ $date->toDateString() }}';
const IS_TODAY       = {{ $date->isToday() ? 'true' : 'false' }};
const STANDUP_ROUTE  = '{{ route('standups.store') }}';
const CSRF_TOKEN     = document.querySelector('meta[name=csrf-token]')?.content;

// Avatar palette (must match PHP)
const AVATAR_BGS   = ['#ede9fe','#dbeafe','#d1fae5','#fef3c7','#fee2e2','#e0e7ff','#ccfbf1','#fce7f3','#ddd6fe','#bfdbfe'];
const AVATAR_TEXTS = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#be185d','#6d28d9','#1d4ed8'];

let _nextId = 1;
function mkItem(d = '', s = 'todo') { return { _id: _nextId++, description: d, status: s }; }

function standupDashboard() {
    return {
        panelOpen:      false,
        loading:        false,
        activeMemberId: null,
        activeName:     '',
        activeSubtitle: '',
        activeBg:       '',
        activeText:     '',
        standup:        null,
        editMode:       false,
        editItems:      [],
        editBlockers:   '',
        saving:         false,
        saved:          false,

        statuses: [
            { value: 'todo',        label: 'Todo',        dot: 'bg-slate-400' },
            { value: 'in_progress', label: 'In Progress', dot: 'bg-blue-500' },
            { value: 'in_review',   label: 'In Review',   dot: 'bg-amber-500' },
            { value: 'done',        label: 'Done',        dot: 'bg-green-500' },
        ],

        get initials() {
            if (!this.activeName) return '';
            return this.activeName.slice(0, 2).toUpperCase();
        },

        statusLabel(s) {
            return { todo:'Todo', in_progress:'In Progress', in_review:'In Review', done:'Done' }[s] ?? s;
        },
        statusClass(s) {
            return {
                todo:        'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300',
                in_progress: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                in_review:   'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                done:        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
            }[s] ?? '';
        },
        statusDot(s) {
            return { todo:'bg-slate-400', in_progress:'bg-blue-500', in_review:'bg-amber-500', done:'bg-green-500' }[s] ?? 'bg-slate-400';
        },

        groupedItems() {
            if (!this.standup?.items?.length) return [];
            const order = ['in_progress','in_review','todo','done'];
            const groups = {};
            for (const s of order) groups[s] = { status: s, items: [] };
            for (const item of this.standup.items) {
                if (groups[item.status]) groups[item.status].items.push(item);
            }
            return Object.values(groups).filter(g => g.items.length > 0);
        },

        async openPanel(userId, name, subtitle) {
            const colorIdx = userId % AVATAR_BGS.length;
            this.activeMemberId = userId;
            this.activeName     = name;
            this.activeSubtitle = subtitle;
            this.activeBg       = AVATAR_BGS[colorIdx];
            this.activeText     = AVATAR_TEXTS[colorIdx];
            this.panelOpen      = true;
            this.loading        = true;
            this.editMode       = false;
            this.saved          = false;
            this.standup        = null;

            try {
                const res  = await fetch(`/standups/${STANDUP_DATE}/${userId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.standup = data.standup;
                // Auto-open edit mode if no standup and it's today
                if (!this.standup && IS_TODAY) this.switchToEdit();
            } catch {
                // ignore fetch errors — panel still shows
            } finally {
                this.loading = false;
            }
        },

        switchToEdit() {
            this.editBlockers = this.standup?.blockers ?? '';
            const existing    = this.standup?.items ?? [];
            this.editItems    = existing.length
                ? existing.map(i => mkItem(i.description, i.status))
                : [mkItem()];
            this.editMode = true;
        },

        cancelEdit() {
            this.editMode = false;
            if (!this.standup) this.panelOpen = false;
        },

        addItem() {
            this.editItems.push(mkItem());
        },
        removeItem(idx) {
            this.editItems.splice(idx, 1);
        },

        async save() {
            this.saving = true;
            try {
                const payload = {
                    standup_date: STANDUP_DATE,
                    user_id:      this.activeMemberId,
                    blockers:     this.editBlockers || null,
                    items:        this.editItems
                        .filter(i => i.description.trim())
                        .map((i, order) => ({ description: i.description.trim(), status: i.status })),
                };
                const res = await fetch(STANDUP_ROUTE, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error(await res.text());
                const data = await res.json();

                // Reload standup data into view mode
                const refetch = await fetch(`/standups/${STANDUP_DATE}/${this.activeMemberId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const refreshed = await refetch.json();
                this.standup  = refreshed.standup;
                this.editMode = false;
                this.saved    = true;
                setTimeout(() => { this.saved = false; }, 3000);

                // Update the card's submitted state without a full page reload
                // (reload after a short delay so the grid reflects the new data)
                setTimeout(() => location.reload(), 1500);
            } catch {
                alert('Failed to save standup. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        closePanel() {
            this.panelOpen      = false;
            this.activeMemberId = null;
        },
    };
}
</script>
@endsection
