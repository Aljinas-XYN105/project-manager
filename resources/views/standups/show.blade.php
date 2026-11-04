@extends('layouts.app')
@section('title', 'My Standup — ' . $day->format('F j, Y'))

@section('content')
@php
$columns = [
    'todo'        => ['label' => 'To Do',       'dot' => 'text-slate-400',  'dotBg' => 'bg-slate-400',  'border' => 'border-slate-200 dark:border-slate-700'],
    'in_progress' => ['label' => 'In Progress',  'dot' => 'text-blue-500',   'dotBg' => 'bg-blue-500',   'border' => 'border-blue-200 dark:border-blue-800/50'],
    'in_review'   => ['label' => 'In Review',    'dot' => 'text-amber-500',  'dotBg' => 'bg-amber-400',  'border' => 'border-amber-200 dark:border-amber-800/50'],
    'done'        => ['label' => 'Done',         'dot' => 'text-green-500',  'dotBg' => 'bg-green-500',  'border' => 'border-green-200 dark:border-green-800/50'],
];
$existingItems    = $standup?->items ?? collect();
$existingBlockers = $standup?->blockers ?? '';
$seedItems        = $existingItems->count()
    ? $existingItems->map(fn($i) => ['id' => $i->id, 'description' => $i->description, 'status' => $i->status])->values()
    : ($defaultItems ?? collect());
@endphp

<div x-data="standupBoard(
        {{ $user->id }},
        '{{ $day->toDateString() }}',
        @json($seedItems),
        @json($existingBlockers)
     )"
     class="flex flex-col h-full">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-5 gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('standups.index') }}"
               class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center
                      text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center
                        font-bold text-violet-600 dark:text-violet-400 text-sm flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">My Standup</h1>
                <p class="text-sm text-slate-500">
                    {{ $day->isToday() ? 'Today — ' : '' }}{{ $day->format('l, F j, Y') }}
                </p>
            </div>
        </div>

        {{-- Top right: save indicator + date --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            <span x-show="saving" class="text-xs text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrow-repeat animate-spin text-xs"></i> Saving…
            </span>
            <span x-show="saved" x-transition class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                <i class="bi bi-check-circle-fill text-xs"></i> Saved
            </span>
            <a href="{{ route('standups.index') }}"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700
                      bg-white dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300
                      hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
                <i class="bi bi-people text-xs text-violet-500"></i>
                View Team
            </a>
        </div>
    </div>

    {{-- Blockers banner (if any) --}}
    <template x-if="blockers">
        <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/40 flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0 text-sm"></i>
            <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed" x-text="blockers"></p>
        </div>
    </template>

    {{-- Kanban board — always visible --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-start flex-1">

        @foreach($columns as $status => $col)
        <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border {{ $col['border'] }} p-3 flex flex-col gap-2">

            {{-- Column header --}}
            <div class="flex items-center gap-2 px-0.5 mb-1">
                <span class="w-2 h-2 rounded-full {{ $col['dotBg'] }} flex-shrink-0"></span>
                <span class="text-xs font-bold {{ $col['dot'] }}">{{ $col['label'] }}</span>
                <span class="ml-auto text-xs font-bold {{ $col['dot'] }} opacity-70"
                      x-text="itemsFor('{{ $status }}').length"></span>
            </div>

            {{-- Task cards --}}
            <template x-for="item in itemsFor('{{ $status }}')" :key="item._id">
                <div class="group bg-white dark:bg-slate-800 rounded-xl px-3 py-2.5 shadow-sm
                            border border-slate-100 dark:border-slate-700
                            flex items-start gap-2">
                    <p class="flex-1 text-sm text-slate-700 dark:text-slate-200 leading-snug"
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
                <div class="bg-white dark:bg-slate-800 rounded-xl px-3 py-2 shadow-sm border border-violet-300 dark:border-violet-600">
                    <input type="text"
                           x-model="newText"
                           @keydown.enter.prevent="confirmAdd('{{ $status }}')"
                           @keydown.escape="addingIn = null; newText = ''"
                           x-effect="addingIn === '{{ $status }}' && $nextTick(() => $el.focus())"
                           placeholder="What are you working on?"
                           class="w-full bg-transparent text-sm text-slate-700 dark:text-slate-200
                                  placeholder-slate-300 dark:placeholder-slate-600 outline-none" />
                    <div class="flex items-center justify-end gap-2 mt-1.5">
                        <button @click="addingIn = null; newText = ''"
                                class="text-xs text-slate-400 hover:text-slate-600 transition-colors">Cancel</button>
                        <button @click="confirmAdd('{{ $status }}')"
                                class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:text-violet-700 transition-colors">Add</button>
                    </div>
                </div>
            </template>

            {{-- Add button --}}
            <button @click="startAdd('{{ $status }}')"
                    x-show="addingIn !== '{{ $status }}'"
                    class="flex items-center gap-1.5 px-2 py-1.5 rounded-xl text-xs text-slate-400
                           hover:text-violet-600 dark:hover:text-violet-400 hover:bg-white dark:hover:bg-slate-800
                           transition-all w-full mt-auto">
                <i class="bi bi-plus-lg text-xs"></i>
                Add task
            </button>

        </div>
        @endforeach

    </div>

    {{-- Blockers textarea --}}
    <div class="mt-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4">
        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
            <i class="bi bi-exclamation-triangle text-amber-500"></i>
            Blockers
            <span class="font-normal text-slate-400 normal-case tracking-normal">(optional)</span>
        </label>
        <textarea x-model="blockers"
                  @blur="blockers !== origBlockers && save()"
                  @input="origBlockers !== undefined && null"
                  rows="2"
                  placeholder="Any blockers or impediments today?"
                  class="form-input w-full resize-none text-sm bg-white dark:bg-slate-800"></textarea>
    </div>

</div>

<script>
function standupBoard(userId, dateStr, existingItems, existingBlockers) {
    let nextId = existingItems.length;

    return {
        items:        existingItems.map((item, i) => ({ ...item, _id: i })),
        blockers:     existingBlockers || '',
        origBlockers: existingBlockers || '',
        addingIn:     null,
        newText:      '',
        saving:       false,
        saved:        false,

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
            this.save();
        },

        removeItem(id) {
            this.items = this.items.filter(i => i._id !== id);
            this.save();
        },

        async save() {
            this.saving       = true;
            this.saved        = false;
            this.origBlockers = this.blockers;
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
                            .map((i, order) => ({ description: i.description.trim(), status: i.status })),
                    }),
                });
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 2500);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
