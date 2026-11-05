@extends('layouts.app')
@section('title', 'Create Task')

@section('content')
<div class="max-w-3xl mx-auto"
     x-data="globalTaskCreate({{ $defaultProject?->id ?? 'null' }}, @js($projects))">

    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="bi bi-plus-lg text-violet-600 dark:text-violet-400"></i>
                </span>
                Create Task
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Create a task, bug, or any work item for any project</p>
        </div>
        <button onclick="history.back()" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </button>
    </div>

    <form method="POST" action="{{ route('tasks.store') }}" class="space-y-4">
        @csrf

        {{-- Type selector --}}
        <div class="card p-4">
            <label class="form-label mb-2">Type *</label>
            <div class="flex flex-wrap gap-2">
                @php
                $types = [
                    'task'    => ['icon'=>'bi-check-square-fill','color'=>'text-slate-500','label'=>'Task','desc'=>'General work item'],
                    'bug'     => ['icon'=>'bi-bug-fill','color'=>'text-rose-500','label'=>'Bug','desc'=>'Something is broken'],
                    'feature' => ['icon'=>'bi-stars','color'=>'text-purple-500','label'=>'Feature','desc'=>'New functionality'],
                    'story'   => ['icon'=>'bi-bookmark-fill','color'=>'text-blue-500','label'=>'Story','desc'=>'User story'],
                    'epic'    => ['icon'=>'bi-lightning-fill','color'=>'text-orange-500','label'=>'Epic','desc'=>'Large initiative'],
                ];
                @endphp
                <input type="hidden" name="type" :value="type">
                @foreach($types as $tv => $td)
                <button type="button"
                        class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all
                               border-slate-200 dark:border-slate-700 hover:border-violet-400"
                        :class="type==='{{ $tv }}' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 shadow-sm' : ''"
                        @mousedown.prevent
                        @click="type='{{ $tv }}'">
                    <i class="bi {{ $td['icon'] }} {{ $td['color'] }} text-base"></i>
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $td['label'] }}</span>
                        <p class="text-[10px] text-slate-400 leading-none mt-0.5">{{ $td['desc'] }}</p>
                    </div>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Title + Project --}}
        <div class="card p-5 space-y-4">
            <div>
                <label class="form-label">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="form-input text-base font-medium" placeholder="What needs to be done?" autofocus>
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-input resize-none"
                          placeholder="Add details, context, or acceptance criteria…">{{ old('description') }}</textarea>
            </div>

            {{-- Project selector --}}
            <div>
                <label class="form-label">Project *</label>
                <x-ui.select name="project_id" placeholder="Select a project…"
                    x-model="projectId"
                    @change="loadProjectData()"
                    :value="old('project_id', $defaultProject?->id??'')"
                    :options="$projects->pluck('name','id')->toArray()" />
            </div>
        </div>

        {{-- Details grid --}}
        <div class="card p-5">
            <p class="section-heading">Details</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Status</label>
                    <x-ui.select name="status"
                        :value="old('status','todo')"
                        :options="['todo'=>'To Do','in_progress'=>'In Progress','qa_testing'=>'QA Testing','in_review'=>'In Review','done'=>'Done']" />
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <x-ui.select name="priority"
                        :value="old('priority','medium')"
                        :options="['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical']" />
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Estimated Hours <span class="text-slate-400 font-normal text-xs">(optional)</span></label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}" min="0" step="0.5" class="form-input" placeholder="e.g. 4">
                    <p class="text-xs text-slate-400 mt-1">Used in burn-rate and project reports.</p>
                </div>
                <div>
                    <label class="form-label">Story Points <span class="text-slate-400 font-normal text-xs">(optional)</span></label>
                    <input type="number" name="story_points" value="{{ old('story_points') }}" min="0" max="100" class="form-input" placeholder="e.g. 3">
                    <p class="text-xs text-slate-400 mt-1">Relative effort estimate (Fibonacci: 1, 2, 3, 5, 8…).</p>
                </div>
                <div x-show="sprints.length > 0" x-cloak>
                    <label class="form-label">Sprint</label>
                    <div x-data="{open:false,val:'',lbl:'Backlog (no sprint)'}" @click.outside="open=false" class="relative">
                        <input type="hidden" name="sprint_id" :value="val">
                        <button type="button" @click="open=!open"
                                :class="open?'ring-2 ring-violet-500/30 border-violet-500':''"
                                class="form-input text-left flex items-center justify-between gap-2 w-full">
                            <span :class="val===''?'text-slate-400 dark:text-slate-500':''" x-text="lbl"></span>
                            <i class="bi bi-chevron-down flex-shrink-0 text-slate-400 transition-transform duration-150" :class="open?'rotate-180':''" style="font-size:0.65rem"></i>
                        </button>
                        <div x-show="open" x-cloak style="z-index:200"
                             class="absolute mt-1 w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden max-h-56 overflow-y-auto">
                            <button type="button" @click="val=''; lbl='Backlog (no sprint)'; open=false"
                                    :class="val===''?'text-violet-600 dark:text-violet-400 bg-violet-50/50 font-medium':'text-slate-400 dark:text-slate-500'"
                                    class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors">
                                <span>Backlog (no sprint)</span>
                                <i x-show="val===''" class="bi bi-check2 text-violet-500 text-xs"></i>
                            </button>
                            <template x-for="sp in sprints" :key="sp.id">
                                <button type="button" @click="val=sp.id; lbl=sp.name+' ('+sp.status+')'; open=false"
                                        :class="val===sp.id?'text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-900/10 font-medium':'text-slate-700 dark:text-slate-200'"
                                        class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors">
                                    <span x-text="sp.name+' ('+sp.status+')'"></span>
                                    <i x-show="val===sp.id" class="bi bi-check2 text-violet-500 text-xs"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bug-specific fields --}}
        <div x-show="type === 'bug'" x-transition
             class="card p-5 border-l-4 border-l-rose-400 space-y-4">
            <h4 class="font-semibold text-sm text-rose-700 dark:text-rose-400 flex items-center gap-2 -mb-1">
                <i class="bi bi-bug-fill"></i> Bug Report Details
            </h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Severity</label>
                    <x-ui.select name="severity" placeholder="Select severity"
                        :value="old('severity','')"
                        :options="['blocker'=>'Blocker','critical'=>'Critical','major'=>'Major','minor'=>'Minor','trivial'=>'Trivial']" />
                </div>
                <div>
                    <label class="form-label text-xs">Environment</label>
                    <input type="text" name="environment" value="{{ old('environment') }}"
                           class="form-input" placeholder="e.g. Production, iOS 17, Chrome 120">
                </div>
            </div>
            <div>
                <label class="form-label text-xs">Steps to Reproduce</label>
                <textarea name="steps_to_reproduce" rows="3" class="form-input resize-none"
                          placeholder="1. Go to …&#10;2. Click …&#10;3. Observe the error">{{ old('steps_to_reproduce') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Expected Behavior</label>
                    <textarea name="expected_behavior" rows="2" class="form-input resize-none"
                              placeholder="What should happen?">{{ old('expected_behavior') }}</textarea>
                </div>
                <div>
                    <label class="form-label text-xs">Actual Behavior</label>
                    <textarea name="actual_behavior" rows="2" class="form-input resize-none"
                              placeholder="What actually happens?">{{ old('actual_behavior') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Assignees (loaded dynamically) --}}
        <div class="card p-5" x-show="members.length > 0" x-cloak>
            <label class="form-label mb-2">Assign To</label>
            <div class="flex flex-wrap gap-2">
                <template x-for="m in members" :key="m.id">
                    <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600
                                  cursor-pointer hover:border-violet-400 transition-colors has-[:checked]:border-violet-500
                                  has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20">
                        <input type="checkbox" :name="'assignee_ids[]'" :value="m.id" class="sr-only">
                        <img :src="m.avatar" :title="m.name" class="w-6 h-6 rounded-full object-cover">
                        <span class="text-sm text-slate-700 dark:text-slate-300" x-text="m.name"></span>
                    </label>
                </template>
            </div>
        </div>

        {{-- Labels (loaded dynamically) --}}
        <div class="card p-5" x-show="labels.length > 0" x-cloak>
            <label class="form-label mb-2">Labels</label>
            <div class="flex flex-wrap gap-2">
                <template x-for="l in labels" :key="l.id">
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer border border-transparent
                                  hover:border-slate-300 transition-colors has-[:checked]:ring-2 has-[:checked]:ring-offset-1"
                           :style="'background-color: ' + l.color + '20'">
                        <input type="checkbox" :name="'label_ids[]'" :value="l.id" class="sr-only">
                        <span class="w-2 h-2 rounded-full" :style="'background-color: ' + l.color"></span>
                        <span class="text-xs font-medium" :style="'color: ' + l.color" x-text="l.name"></span>
                    </label>
                </template>
            </div>
        </div>

        {{-- Empty state when no project selected --}}
        <div x-show="!projectId" class="card p-6 text-center text-slate-400">
            <i class="bi bi-arrow-up text-2xl block mb-2 text-slate-300"></i>
            <p class="text-sm">Select a project above to see members and labels</p>
        </div>

        <div class="flex justify-between items-center pt-1">
            <button type="button" onclick="history.back()" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary px-6">
                <i class="bi bi-plus-lg"></i> Create
                <span x-text="type === 'bug' ? 'Bug Report' : type === 'feature' ? 'Feature' : type === 'epic' ? 'Epic' : type === 'story' ? 'Story' : 'Task'"></span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function globalTaskCreate(defaultProjectId, projects) {
    return {
        type: '{{ old('type', request('type', 'task')) }}',
        projectId: defaultProjectId ? String(defaultProjectId) : '',
        members: [],
        labels: [],
        sprints: [],
        loading: false,

        init() {
            if (this.projectId) this.loadProjectData();
        },

        loadProjectData() {
            if (!this.projectId) { this.members = []; this.labels = []; this.sprints = []; return; }
            this.loading = true;
            fetch('/tasks/project-data/' + this.projectId, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    this.members = d.members || [];
                    this.labels  = d.labels  || [];
                    this.sprints = d.sprints || [];
                    this.loading = false;
                })
                .catch(() => this.loading = false);
        }
    }
}
</script>
@endpush
@endsection
