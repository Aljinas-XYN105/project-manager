@extends('layouts.app')
@section('title', 'My Standup — ' . $day->format('F j, Y'))

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('standups.index') }}" class="hover:text-violet-600 transition-colors">Standups</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <a href="{{ route('standups.day', $day->toDateString()) }}" class="hover:text-violet-600 transition-colors">{{ $day->format('M j') }}</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span>My Standup</span>
            </div>
            <h1 class="page-title">My Standup</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $day->isToday() ? 'Today — ' : '' }}{{ $day->format('l, F j, Y') }}
            </p>
        </div>
        @if($standup)
        <a href="{{ route('standups.day', $day->toDateString()) }}" class="btn-ghost btn-sm">
            <i class="bi bi-people text-sm"></i> View Team
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm flex items-center gap-2">
        <i class="bi bi-check-circle-fill flex-shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Form --}}
    <div x-data="standupForm()" class="space-y-4">

        {{-- Items section --}}
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-list-check text-violet-500"></i>
                    Tasks &amp; Work Items
                </h2>
                <button type="button" @click="addItem()"
                        class="btn-ghost btn-sm text-violet-600 dark:text-violet-400">
                    <i class="bi bi-plus-lg text-sm"></i> Add Item
                </button>
            </div>

            <div class="space-y-2" x-ref="itemList">
                <template x-for="(item, idx) in items" :key="item.id">
                    <div class="flex items-start gap-2 group">
                        {{-- Status pill --}}
                        <div class="relative flex-shrink-0 mt-0.5" x-data="{open:false}">
                            <button type="button" @click="open=!open" @click.outside="open=false"
                                    :class="statusClass(item.status)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold cursor-pointer whitespace-nowrap transition-all">
                                <span x-text="statusLabel(item.status)"></span>
                                <i class="bi bi-chevron-down text-[9px]"></i>
                            </button>
                            <div x-show="open" x-transition
                                 class="absolute left-0 top-full mt-1 w-36 rounded-xl shadow-lg bg-white dark:bg-slate-800
                                        border border-slate-200 dark:border-slate-700 z-20 overflow-hidden">
                                <template x-for="s in statuses" :key="s.value">
                                    <button type="button"
                                            @click="item.status = s.value; open=false"
                                            :class="item.status === s.value ? 'bg-slate-50 dark:bg-slate-700/50 font-semibold' : ''"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <span :class="s.dot" class="w-2 h-2 rounded-full flex-shrink-0"></span>
                                        <span x-text="s.label" class="text-slate-700 dark:text-slate-200"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Description --}}
                        <input type="text" x-model="item.description"
                               @keydown.enter.prevent="addItem()"
                               @keydown.backspace="item.description === '' && removeItem(idx)"
                               placeholder="What are you working on?"
                               class="flex-1 bg-transparent border-0 text-sm text-slate-700 dark:text-slate-200
                                      placeholder-slate-300 dark:placeholder-slate-600 outline-none py-1
                                      focus:outline-none" />

                        {{-- Remove --}}
                        <button type="button" @click="removeItem(idx)"
                                class="flex-shrink-0 mt-1 opacity-0 group-hover:opacity-100 transition-opacity text-slate-300 hover:text-rose-500">
                            <i class="bi bi-x text-base"></i>
                        </button>
                    </div>
                </template>

                <div x-show="items.length === 0" class="text-sm text-slate-400 py-4 text-center">
                    No items yet. Click "Add Item" to start.
                </div>
            </div>
        </div>

        {{-- Blockers --}}
        <div class="card">
            <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 mb-3">
                <i class="bi bi-exclamation-triangle text-amber-500"></i>
                Blockers
                <span class="text-xs font-normal text-slate-400">(optional)</span>
            </h2>
            <textarea x-model="blockers" rows="3"
                      placeholder="Any blockers or impediments today?"
                      class="form-input w-full resize-none text-sm"></textarea>
        </div>

        {{-- Save button --}}
        <div class="flex items-center justify-between pt-1">
            <a href="{{ route('standups.index') }}" class="btn-ghost btn-sm">Cancel</a>
            <button type="button" @click="save()"
                    :disabled="saving"
                    class="btn-primary">
                <span x-show="!saving"><i class="bi bi-check2 text-sm"></i> Save Standup</span>
                <span x-show="saving"><i class="bi bi-arrow-repeat animate-spin text-sm"></i> Saving…</span>
            </button>
        </div>

        {{-- Success flash --}}
        <div x-show="saved" x-transition
             class="text-center text-sm text-green-600 dark:text-green-400 font-medium py-2">
            <i class="bi bi-check-circle-fill mr-1"></i> Standup saved!
        </div>
    </div>

</div>

<script>
function standupForm() {
    let nextId = 1;

    function makeItem(desc = '', status = 'todo') {
        return { id: nextId++, description: desc, status };
    }

    // Existing items from server
    const existing = @json($standup?->items ?? []);

    return {
        items: existing.length
            ? existing.map(i => makeItem(i.description, i.status))
            : [makeItem()],
        blockers: @json($standup?->blockers ?? ''),
        saving: false,
        saved: false,

        statuses: [
            { value: 'todo',        label: 'Todo',        dot: 'bg-slate-400' },
            { value: 'in_progress', label: 'In Progress', dot: 'bg-blue-500' },
            { value: 'in_review',   label: 'In Review',   dot: 'bg-amber-500' },
            { value: 'done',        label: 'Done',        dot: 'bg-green-500' },
        ],

        statusLabel(s) {
            const map = { todo:'Todo', in_progress:'In Progress', in_review:'In Review', done:'Done' };
            return map[s] ?? s;
        },
        statusClass(s) {
            const map = {
                todo:        'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300',
                in_progress: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                in_review:   'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
                done:        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
            };
            return map[s] ?? '';
        },

        addItem() {
            this.items.push(makeItem());
            this.$nextTick(() => {
                const inputs = this.$refs.itemList.querySelectorAll('input[type=text]');
                inputs[inputs.length - 1]?.focus();
            });
        },
        removeItem(idx) {
            this.items.splice(idx, 1);
        },

        async save() {
            this.saving = true;
            try {
                const payload = {
                    standup_date: '{{ $day->toDateString() }}',
                    blockers: this.blockers || null,
                    items: this.items
                        .filter(i => i.description.trim())
                        .map((i, order) => ({
                            description: i.description.trim(),
                            status: i.status,
                        })),
                    _token: document.querySelector('meta[name=csrf-token]')?.content,
                };
                const res = await fetch('{{ route('standups.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                               'X-CSRF-TOKEN': payload._token },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error(await res.text());
                this.saved = true;
                setTimeout(() => this.saved = false, 3000);
            } catch (e) {
                alert('Failed to save standup. Please try again.');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
