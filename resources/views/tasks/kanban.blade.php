@extends('layouts.app')
@section('title', $project->name.' — Board')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-violet-600 transition-colors">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>Board</span>
        </div>
        <h1 class="page-title">Kanban Board</h1>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <div class="tab-bar">
            <a href="{{ route('projects.tasks.index', $project) }}"
               class="tab-item text-xs px-2.5 py-1.5">
                <i class="bi bi-list-task"></i> <span class="hidden sm:inline">List</span>
            </a>
            <a href="{{ route('projects.tasks.kanban', $project) }}"
               class="tab-item active text-xs px-2.5 py-1.5">
                <i class="bi bi-kanban"></i> <span class="hidden sm:inline">Board</span>
            </a>
            <a href="{{ route('projects.tasks.calendar', $project) }}"
               class="tab-item text-xs px-2.5 py-1.5">
                <i class="bi bi-calendar3"></i> <span class="hidden sm:inline">Calendar</span>
            </a>
            <a href="{{ route('projects.tasks.backlog', $project) }}"
               class="tab-item text-xs px-2.5 py-1.5">
                <i class="bi bi-inbox"></i> <span class="hidden sm:inline">Backlog</span>
            </a>
        </div>
        <button x-data @click="$dispatch('open-board-settings')" class="btn-ghost btn-sm p-2">
            <i class="bi bi-gear text-slate-500"></i>
        </button>
        <button @click="$dispatch('open-task-panel', { columnId: null })" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Task
        </button>
    </div>
</div>

{{-- Kanban Board --}}
<div x-data="kanbanBoard({{ $project->id }})"
     class="flex gap-3 overflow-x-auto pb-6 -mx-4 lg:-mx-6 px-4 lg:px-6 min-h-[calc(100vh-13rem)] items-start">

    @foreach($columns as $column)
    <div class="flex flex-col flex-shrink-0 w-[280px] rounded-2xl
                bg-slate-100/70 dark:bg-slate-800/50 backdrop-blur-sm
                border border-slate-200/60 dark:border-slate-700/50 overflow-hidden"
         @dragover.prevent="dragEnter({{ $column->id }})"
         @drop.prevent="drop({{ $column->id }}, {{ $column->tasks->count() }})"
         :class="{ 'ring-2 ring-inset ring-violet-400 dark:ring-violet-600': dragOver === {{ $column->id }} }">

        {{-- Column Header --}}
        <div class="flex items-center justify-between px-3 py-3 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $column->color }}"></div>
                <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ $column->name }}</span>
                <span class="min-w-[20px] h-5 bg-white dark:bg-slate-700 rounded-full text-xs font-semibold
                             text-slate-500 dark:text-slate-400 flex items-center justify-center px-1.5 shadow-sm">
                    {{ $column->tasks->count() }}
                </span>
            </div>
            @if($column->wip_limit)
            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md
                         {{ $column->tasks->count() >= $column->wip_limit
                            ? 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30'
                            : 'text-slate-400 bg-slate-200 dark:bg-slate-700' }}">
                {{ $column->tasks->count() }}/{{ $column->wip_limit }}
            </span>
            @endif
        </div>

        {{-- Task Cards --}}
        <div class="flex-1 overflow-y-auto px-2.5 pb-2.5 space-y-2"
             id="column-{{ $column->id }}">
            @foreach($column->tasks as $task)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700/80
                        shadow-sm cursor-pointer p-3
                        hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700/60
                        hover:-translate-y-px transition-all duration-150 group"
                 draggable="true"
                 @click="$dispatch('open-task-panel', { taskId: {{ $task->id }} })"
                 @dragstart="dragStart({{ $task->id }}); $el.classList.add('opacity-50','rotate-1')"
                 @dragend="$el.classList.remove('opacity-50','rotate-1')">

                {{-- Type icon + Labels --}}
                <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                    @if($task->type)
                    <i class="bi {{ $task->type_icon }} text-xs {{ $task->type_color }}"></i>
                    @endif
                    @if($task->ticket_key)
                    <span class="font-mono text-[9px] text-slate-400">{{ $task->ticket_key }}</span>
                    @endif
                    @foreach($task->labels as $label)
                    <span class="inline-block px-1.5 py-px rounded-full text-[9px] font-semibold text-white leading-none"
                          style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                </div>

                <p class="block font-medium text-sm text-slate-900 dark:text-white leading-snug mb-2.5">
                    {{ $task->title }}
                </p>

                @if($task->subtasks->count())
                @php $subDone = $task->subtasks->where('status','done')->count(); @endphp
                <div class="mb-2.5">
                    <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1">
                        <span><i class="bi bi-diagram-2 mr-0.5"></i> {{ $subDone }}/{{ $task->subtasks->count() }}</span>
                    </div>
                    <div class="h-1 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-1 bg-violet-400 rounded-full" style="width: {{ $task->subtasks->count() > 0 ? round($subDone/$task->subtasks->count()*100) : 0 }}%"></div>
                    </div>
                </div>
                @endif

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="badge-{{ $task->priority }} py-0 text-[9px]">{{ ucfirst($task->priority) }}</span>
                        @if($task->estimated_hours)
                        <span class="text-[10px] text-slate-400"><i class="bi bi-clock mr-0.5"></i>{{ $task->estimated_hours }}h</span>
                        @endif
                        @if($task->due_date)
                        <span class="text-[10px] {{ $task->is_overdue ? 'text-rose-500 font-medium' : 'text-slate-400' }}">
                            <i class="bi bi-calendar3 mr-0.5"></i>{{ $task->due_date->format('M j') }}
                        </span>
                        @endif
                    </div>
                    <div class="avatar-stack">
                        @foreach($task->assignees->take(3) as $assignee)
                        <img src="{{ $assignee->avatar_url }}" title="{{ $assignee->name }}"
                             class="w-5 h-5 rounded-full">
                        @endforeach
                        @if($task->assignees->count() > 3)
                        <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-[8px] font-semibold
                                    text-slate-500 flex items-center justify-center">
                            +{{ $task->assignees->count() - 3 }}
                        </div>
                        @endif
                        @if(!$task->assignees->count())
                        <div class="w-5 h-5 rounded-full border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center">
                            <i class="bi bi-person text-[8px] text-slate-400"></i>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Drop zone placeholder --}}
            <div x-show="dragOver === {{ $column->id }}" x-cloak
                 class="h-16 rounded-xl border-2 border-dashed border-violet-300 dark:border-violet-700
                        bg-violet-50 dark:bg-violet-900/10 flex items-center justify-center">
                <span class="text-xs text-violet-400">Drop here</span>
            </div>
        </div>

        {{-- Quick Add Button --}}
        <div class="px-2.5 pb-2.5 flex-shrink-0">
            <button @click="$dispatch('open-task-panel', { columnId: {{ $column->id }} })"
                    class="w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-400
                           hover:text-slate-600 dark:hover:text-slate-200
                           hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-colors">
                <i class="bi bi-plus-lg text-sm"></i> Add task
            </button>
        </div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════
     New Task Slide-In Panel
     ═══════════════════════════════════════════════════════════ --}}
<div x-data="{
        show: false,
        saving: false,
        form: {
            title: '',
            type: 'task',
            priority: 'medium',
            column_id: null,
            assignee_ids: [],
            label_ids: [],
            due_date: '',
            estimated_hours: '',
            description: '',
        },
        columns: @js($columns->map(fn($c) => ['id' => $c->id, 'name' => $c->name])),
        members: @js($members),
        labels:  @js($labels),
        reset() {
            this.form = { title:'', type:'task', priority:'medium', column_id: this.columns[0]?.id ?? null,
                          assignee_ids:[], label_ids:[], due_date:'', estimated_hours:'', description:'' };
            this.saving = false;
        },
        open(columnId) {
            this.reset();
            if (columnId) this.form.column_id = columnId;
            this.show = true;
            this.$nextTick(() => this.$refs.titleInput?.focus());
        },
        toggleAssignee(id) {
            const i = this.form.assignee_ids.indexOf(id);
            i === -1 ? this.form.assignee_ids.push(id) : this.form.assignee_ids.splice(i, 1);
        },
        toggleLabel(id) {
            const i = this.form.label_ids.indexOf(id);
            i === -1 ? this.form.label_ids.push(id) : this.form.label_ids.splice(i, 1);
        },
        memberName(id) {
            return this.members.find(m => m.id === id)?.name ?? '';
        },
        save() {
            if (!this.form.title.trim() || this.saving) return;
            this.saving = true;
            axios.post('/projects/{{ $project->id }}/tasks', {
                ...this.form,
                project_id: {{ $project->id }},
            })
            .then(() => { this.show = false; window.location.reload(); })
            .catch(() => { this.saving = false; window.toast('Failed to create task', 'error'); });
        }
     }"
     @open-task-panel.window="open($event.detail.columnId)"
     @keydown.escape.window="show = false">

    {{-- Backdrop --}}
    <div x-show="show" x-cloak
         class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm"
         @click="show = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    {{-- Panel --}}
    <div x-show="show" x-cloak
         class="fixed inset-y-0 right-0 z-50 w-full max-w-md flex flex-col
                bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        {{-- Panel Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center">
                    <i class="bi bi-plus-lg text-violet-600 dark:text-violet-400 text-sm"></i>
                </div>
                <h2 class="font-semibold text-slate-900 dark:text-white">New Task</h2>
                <span class="text-xs text-slate-400 font-mono">{{ $project->name }}</span>
            </div>
            <button @click="show = false" class="btn-icon btn-ghost p-1.5">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        {{-- Panel Body --}}
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

            {{-- Title --}}
            <div>
                <label class="form-label">Title <span class="text-rose-500">*</span></label>
                <input x-ref="titleInput" x-model="form.title" type="text"
                       class="form-input" placeholder="What needs to be done?"
                       @keydown.enter="save()">
            </div>

            {{-- Type + Priority row --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Type</label>
                    <select x-model="form.type" class="form-select">
                        <option value="task">Task</option>
                        <option value="bug">Bug</option>
                        <option value="feature">Feature</option>
                        <option value="story">Story</option>
                        <option value="epic">Epic</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select x-model="form.priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>

            {{-- Column --}}
            <div>
                <label class="form-label">Column</label>
                <select x-model.number="form.column_id" class="form-select">
                    <template x-for="col in columns" :key="col.id">
                        <option :value="col.id" x-text="col.name"></option>
                    </template>
                </select>
            </div>

            {{-- Assignees --}}
            <div>
                <label class="form-label">Assignees</label>
                <div class="flex flex-wrap gap-2 mb-2" x-show="form.assignee_ids.length > 0">
                    <template x-for="id in form.assignee_ids" :key="id">
                        <span class="inline-flex items-center gap-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs px-2 py-0.5 rounded-full">
                            <span x-text="memberName(id)"></span>
                            <button type="button" @click="toggleAssignee(id)" class="hover:text-rose-500">
                                <i class="bi bi-x text-xs"></i>
                            </button>
                        </span>
                    </template>
                </div>
                <div class="grid grid-cols-2 gap-1.5 max-h-36 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                    <template x-for="member in members" :key="member.id">
                        <button type="button" @click="toggleAssignee(member.id)"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-sm transition-colors"
                                :class="form.assignee_ids.includes(member.id)
                                    ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300'
                                    : 'hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
                            <img :src="member.avatar" :alt="member.name" class="w-5 h-5 rounded-full flex-shrink-0">
                            <span class="truncate text-xs" x-text="member.name"></span>
                            <i x-show="form.assignee_ids.includes(member.id)"
                               class="bi bi-check-lg text-violet-500 ml-auto text-xs flex-shrink-0"></i>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Labels --}}
            @if($labels->isNotEmpty())
            <div>
                <label class="form-label">Labels</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="lbl in labels" :key="lbl.id">
                        <button type="button" @click="toggleLabel(lbl.id)"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-white transition-all"
                                :style="form.label_ids.includes(lbl.id)
                                    ? 'background-color:' + lbl.color + '; box-shadow: 0 0 0 2px ' + lbl.color
                                    : 'background-color:' + lbl.color + '66'"
                                x-text="lbl.name">
                        </button>
                    </template>
                </div>
            </div>
            @endif

            {{-- Due date + Estimate row --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Due date</label>
                    <input x-model="form.due_date" type="date" class="form-input">
                </div>
                <div>
                    <label class="form-label">Estimate (hrs)</label>
                    <input x-model="form.estimated_hours" type="number" min="0" step="0.5"
                           class="form-input" placeholder="0">
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="form-label">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea x-model="form.description" rows="3"
                          class="form-input resize-none"
                          placeholder="Add more context…"></textarea>
            </div>

        </div>

        {{-- Panel Footer --}}
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 flex-shrink-0">
            <button @click="show = false" class="btn-ghost btn-sm">Cancel</button>
            <button @click="save()"
                    :disabled="!form.title.trim() || saving"
                    class="btn-primary btn-sm gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <template x-if="saving">
                    <i class="bi bi-arrow-repeat animate-spin"></i>
                </template>
                <template x-if="!saving">
                    <i class="bi bi-plus-lg"></i>
                </template>
                <span x-text="saving ? 'Creating…' : 'Create Task'"></span>
            </button>
        </div>
    </div>
</div>

{{-- Board Settings Modal --}}
<div x-data="{
    show: false,
    columns: @js($columns),
    newName: '',
    newColor: '#7c3aed',
    newWip: '',
    addColumn() {
        if (!this.newName.trim()) return;
        axios.post('/projects/{{ $project->id }}/columns', { name: this.newName, color: this.newColor, wip_limit: this.newWip || null })
            .then(r => { this.columns.push(r.data.column); this.newName=''; this.newColor='#7c3aed'; this.newWip=''; })
            .catch(() => {});
    },
    updateColumn(col) {
        axios.patch('/columns/'+col.id, { name: col.name, color: col.color, wip_limit: col.wip_limit || null });
    },
    deleteColumn(col) {
        if (!confirm('Delete column? Tasks will be moved to the first column.')) return;
        axios.delete('/columns/'+col.id)
            .then(() => { this.columns = this.columns.filter(c => c.id !== col.id); });
    }
}"
     @open-board-settings.window="show = true"
     @keydown.escape.window="show = false"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="show = false"></div>
    <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Board Settings</h2>
            <button @click="show = false" class="btn-icon btn-ghost p-1.5"><i class="bi bi-x-lg text-sm"></i></button>
        </div>
        <div class="overflow-y-auto p-5 space-y-2.5 flex-1">
            <p class="section-heading px-0 mb-3">Columns</p>
            <template x-for="col in columns" :key="col.id">
                <div class="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl">
                    <div class="color-swatch-btn !w-8 !h-8"><input type="color" x-model="col.color"></div>
                    <input x-model="col.name" class="form-input flex-1 text-sm py-1.5" placeholder="Column name">
                    <input x-model="col.wip_limit" type="number" min="1" class="form-input w-16 text-sm py-1.5 text-center" placeholder="WIP">
                    <button @click="updateColumn(col)" class="btn-secondary btn-sm px-2.5 py-1.5" title="Save">
                        <i class="bi bi-check-lg text-emerald-600"></i>
                    </button>
                    <button @click="deleteColumn(col)" class="btn-ghost btn-sm px-2 py-1.5 text-rose-400" title="Delete">
                        <i class="bi bi-trash text-sm"></i>
                    </button>
                </div>
            </template>
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                <p class="text-xs font-semibold text-slate-500 mb-2.5">Add New Column</p>
                <div class="flex items-center gap-2">
                    <div class="color-swatch-btn !w-8 !h-8"><input type="color" x-model="newColor"></div>
                    <input x-model="newName" class="form-input flex-1 text-sm py-1.5" placeholder="Column name" @keydown.enter="addColumn()">
                    <input x-model="newWip" type="number" min="1" class="form-input w-16 text-sm py-1.5 text-center" placeholder="WIP">
                    <button @click="addColumn()" class="btn-primary btn-sm px-2.5 py-1.5">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
            <button @click="show = false" class="btn-ghost btn-sm">Cancel</button>
            <button @click="show = false; window.location.reload()" class="btn-primary btn-sm">Done</button>
        </div>
    </div>
</div>
@endsection
