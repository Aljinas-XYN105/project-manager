@extends('layouts.app')
@section('title', 'My Standup — ' . $day->format('F j, Y'))

@section('content')
@php
$columns = [
    'todo'        => ['label' => 'To Do',       'icon' => 'bi-circle-fill',       'color' => 'text-slate-400',  'dotBg' => 'bg-slate-400',  'activeTxt' => 'text-slate-600 dark:text-slate-300',  'activeBg' => 'bg-slate-50 dark:bg-slate-800/60',   'border' => 'border-slate-200 dark:border-slate-700',   'addHover' => 'hover:border-slate-300 hover:text-slate-600'],
    'in_progress' => ['label' => 'In Progress',  'icon' => 'bi-arrow-repeat',       'color' => 'text-blue-400',   'dotBg' => 'bg-blue-500',   'activeTxt' => 'text-blue-600 dark:text-blue-400',    'activeBg' => 'bg-blue-50/50 dark:bg-blue-900/10',   'border' => 'border-blue-200 dark:border-blue-800/50',  'addHover' => 'hover:border-blue-300 hover:text-blue-600'],
    'in_review'   => ['label' => 'In Review',    'icon' => 'bi-eye-fill',           'color' => 'text-amber-400',  'dotBg' => 'bg-amber-400',  'activeTxt' => 'text-amber-600 dark:text-amber-400',  'activeBg' => 'bg-amber-50/50 dark:bg-amber-900/10', 'border' => 'border-amber-200 dark:border-amber-800/50','addHover' => 'hover:border-amber-300 hover:text-amber-600'],
    'done'        => ['label' => 'Done',         'icon' => 'bi-check-circle-fill',  'color' => 'text-green-400',  'dotBg' => 'bg-green-500',  'activeTxt' => 'text-green-600 dark:text-green-400',  'activeBg' => 'bg-green-50/50 dark:bg-green-900/10', 'border' => 'border-green-200 dark:border-green-800/50','addHover' => 'hover:border-green-300 hover:text-green-600'],
];

$existingItems    = $standup?->items ?? collect();
$existingBlockers = $standup?->blockers ?? '';
$seedItems        = $existingItems->count()
    ? $existingItems->map(fn($i) => ['id' => $i->id, 'description' => $i->description, 'status' => $i->status])->values()
    : ($defaultItems ?? collect());
$alreadySubmitted = $standup !== null;
@endphp

<div x-data="standupBoard(
        {{ $user->id }},
        '{{ $day->toDateString() }}',
        @json($seedItems),
        @json($existingBlockers),
        {{ $alreadySubmitted ? 'true' : 'false' }}
     )"
     class="flex flex-col gap-4">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('standups.index') }}"
               class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center
                      text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center
                        font-bold text-sm bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <h1 class="text-base font-bold text-slate-900 dark:text-white leading-tight">My Standup</h1>
                <p class="text-xs text-slate-500">{{ $day->format('l, F j, Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            {{-- Auto-save indicator --}}
            <span x-show="saving" x-cloak
                  class="text-xs text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrow-repeat animate-spin text-xs"></i> Saving…
            </span>
            <span x-show="saved && !submitted" x-cloak x-transition
                  class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <i class="bi bi-check-circle-fill text-xs"></i> Saved
            </span>

            {{-- Submit button --}}
            <button @click="submitStandup()"
                    :disabled="saving || submitted"
                    :class="submitted
                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-700 cursor-default'
                        : 'bg-violet-600 hover:bg-violet-700 text-white border-violet-600 hover:border-violet-700'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-semibold
                           transition-all duration-200 disabled:opacity-70">
                <i :class="submitted ? 'bi-check-circle-fill' : 'bi-send-fill'" class="bi text-xs"></i>
                <span x-text="submitted ? 'Submitted ✓' : 'Submit Standup'"></span>
            </button>
        </div>
    </div>

    {{-- ── Success banner ──────────────────────────────────────────────── --}}
    <template x-if="submitted && justSubmitted">
        <div x-transition class="flex items-center gap-3 px-4 py-3 rounded-xl
                                  bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
            <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0"></i>
            <p class="text-sm font-medium text-green-700 dark:text-green-300">
                Standup submitted for {{ $day->format('l, F j') }}. Nice work!
            </p>
            <button @click="justSubmitted = false"
                    class="ml-auto text-green-400 hover:text-green-600 flex-shrink-0">
                <i class="bi bi-x text-sm"></i>
            </button>
        </div>
    </template>

    {{-- ── Blockers banner (existing) ──────────────────────────────────── --}}
    <template x-if="blockers && !justSubmitted">
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl
                    bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
            <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed" x-text="blockers"></p>
        </div>
    </template>

    {{-- ── Kanban columns ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-start">

        @foreach($columns as $status => $col)
        <div class="rounded-2xl {{ $col['activeBg'] }} border {{ $col['border'] }} flex flex-col min-h-[180px]">

            {{-- Column header --}}
            <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                <i class="bi {{ $col['icon'] }} text-xs {{ $col['color'] }}"></i>
                <span class="text-xs font-bold {{ $col['color'] }}">{{ $col['label'] }}</span>
                <span class="ml-auto text-xs font-bold {{ $col['color'] }} opacity-60 tabular-nums"
                      x-text="itemsFor('{{ $status }}').length || ''"></span>
            </div>

            {{-- Task cards --}}
            <div class="flex flex-col gap-1.5 px-2 flex-1">
                <template x-for="item in itemsFor('{{ $status }}')" :key="item._id">
                    <div class="group bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm
                                border border-white dark:border-slate-700/60
                                flex items-start gap-2">
                        <p class="flex-1 text-sm text-slate-700 dark:text-slate-200 leading-snug break-words"
                           x-text="item.description"></p>
                        <button @click="removeItem(item._id)"
                                class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity
                                       text-slate-300 hover:text-rose-500 mt-0.5">
                            <i class="bi bi-x text-sm"></i>
                        </button>
                    </div>
                </template>

                {{-- Inline add input --}}
                <template x-if="addingIn === '{{ $status }}'">
                    <div class="bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm
                                border border-violet-300 dark:border-violet-600">
                        <textarea
                               x-model="newText"
                               @keydown.enter.prevent="confirmAdd('{{ $status }}')"
                               @keydown.escape="addingIn = null; newText = ''"
                               x-effect="addingIn === '{{ $status }}' && $nextTick(() => { const el = $el.closest('[x-data]').querySelector('textarea[x-model=\'newText\']'); el && el.focus(); })"
                               rows="2"
                               placeholder="Describe what you're working on…"
                               class="w-full bg-transparent text-sm text-slate-700 dark:text-slate-200
                                      placeholder-slate-300 dark:placeholder-slate-600 outline-none resize-none"></textarea>
                        <div class="flex items-center justify-between mt-1.5">
                            <span class="text-[10px] text-slate-300 dark:text-slate-600">Enter to add · Esc to cancel</span>
                            <div class="flex items-center gap-2">
                                <button @click="addingIn = null; newText = ''"
                                        class="text-xs text-slate-400 hover:text-slate-600 transition-colors">Cancel</button>
                                <button @click="confirmAdd('{{ $status }}')"
                                        class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:text-violet-700 transition-colors">Add</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Add button — always at bottom, full-width --}}
            <button @click="startAdd('{{ $status }}')"
                    x-show="addingIn !== '{{ $status }}'"
                    class="flex items-center gap-2 mx-2 mb-2 mt-1 px-3 py-2 rounded-xl
                           border border-dashed border-slate-200 dark:border-slate-700
                           text-xs text-slate-400 dark:text-slate-500
                           {{ $col['addHover'] }} dark:hover:border-slate-600 dark:hover:text-slate-300
                           transition-all w-[calc(100%-16px)]">
                <i class="bi bi-plus-lg text-xs"></i>
                Add task
            </button>

        </div>
        @endforeach

    </div>

    {{-- ── Blockers ─────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4">
        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
            <i class="bi bi-exclamation-triangle text-amber-500"></i>
            Blockers
            <span class="font-normal normal-case tracking-normal text-slate-400">(optional — anything blocking your progress?)</span>
        </label>
        <textarea x-model="blockers"
                  rows="2"
                  placeholder="e.g. Waiting for API access, blocked on design review…"
                  class="form-input w-full resize-none text-sm bg-white dark:bg-slate-800"></textarea>
    </div>

    {{-- ── Bottom submit bar ───────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 pt-2">
        <p class="text-xs text-slate-400">
            @if($alreadySubmitted)
            Last updated today · changes auto-save
            @else
            Items auto-save as you add them. Hit Submit when you're ready.
            @endif
        </p>
        <button @click="submitStandup()"
                :disabled="saving || submitted"
                :class="submitted
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-700 cursor-default'
                    : 'bg-violet-600 hover:bg-violet-700 text-white border-violet-600 hover:border-violet-700'"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border
                       text-sm font-semibold transition-all duration-200 disabled:opacity-70">
            <i :class="submitted ? 'bi-check-circle-fill' : 'bi-send-fill'" class="bi text-xs"></i>
            <span x-text="submitted ? 'Submitted ✓' : 'Submit Standup'"></span>
        </button>
    </div>

</div>

<script>
function standupBoard(userId, dateStr, existingItems, existingBlockers, alreadySubmitted) {
    let nextId = existingItems.length;

    return {
        items:         existingItems.map((item, i) => ({ ...item, _id: i })),
        blockers:      existingBlockers || '',
        addingIn:      null,
        newText:       '',
        saving:        false,
        saved:         false,
        submitted:     alreadySubmitted,
        justSubmitted: false,

        itemsFor(status) {
            return this.items.filter(i => i.status === status);
        },

        startAdd(status) {
            this.addingIn = status;
            this.newText  = '';
        },

        confirmAdd(status) {
            const text = this.newText.trim();
            if (!text) { this.addingIn = null; return; }
            this.items.push({ _id: nextId++, description: text, status });
            this.newText  = '';
            this.addingIn = null;
            this.autoSave();
        },

        removeItem(id) {
            this.items = this.items.filter(i => i._id !== id);
            this.autoSave();
        },

        async autoSave() {
            await this.save();
        },

        async submitStandup() {
            await this.save();
            this.submitted     = true;
            this.justSubmitted = true;
            setTimeout(() => { this.justSubmitted = false; }, 5000);
        },

        async save() {
            this.saving = true;
            this.saved  = false;
            try {
                await fetch('/standups', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                    body: JSON.stringify({
                        standup_date: dateStr,
                        blockers:     this.blockers || null,
                        items: this.items
                            .filter(i => i.description.trim())
                            .map((i, order) => ({
                                description: i.description.trim(),
                                status:      i.status,
                            })),
                    }),
                });
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 3000);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
