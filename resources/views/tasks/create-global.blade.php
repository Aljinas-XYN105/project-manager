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
                @foreach($types as $tv => $td)
                <label class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all
                              border-slate-200 dark:border-slate-700 hover:border-violet-400"
                       :class="type==='{{ $tv }}' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 shadow-sm' : ''">
                    <input type="radio" name="type" value="{{ $tv }}" x-model="type" class="sr-only">
                    <i class="bi {{ $td['icon'] }} {{ $td['color'] }} text-base"></i>
                    <div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $td['label'] }}</span>
                        <p class="text-[10px] text-slate-400 leading-none mt-0.5">{{ $td['desc'] }}</p>
                    </div>
                </label>
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
                <select name="project_id" x-model="projectId" @change="loadProjectData()" class="form-select">
                    <option value="">Select a project…</option>
                    @foreach($projects as $proj)
                    <option value="{{ $proj->id }}" {{ old('project_id', $defaultProject?->id) == $proj->id ? 'selected' : '' }}>
                        {{ $proj->name }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Details grid --}}
        <div class="card p-5">
            <p class="section-heading">Details</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','done'=>'Done'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('status','todo')===$v?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('priority','medium')===$v?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}" min="0" step="0.5" class="form-input" placeholder="0">
                </div>
                <div>
                    <label class="form-label">Story Points</label>
                    <input type="number" name="story_points" value="{{ old('story_points') }}" min="0" max="100" class="form-input" placeholder="0">
                </div>
                <div x-show="sprints.length > 0" x-cloak>
                    <label class="form-label">Sprint</label>
                    <select name="sprint_id" class="form-select">
                        <option value="">Backlog (no sprint)</option>
                        <template x-for="sprint in sprints" :key="sprint.id">
                            <option :value="sprint.id" x-text="sprint.name + ' (' + sprint.status + ')'"></option>
                        </template>
                    </select>
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
                    <select name="severity" class="form-select">
                        <option value="">Select severity</option>
                        @foreach(['blocker'=>'🔴 Blocker','critical'=>'🟠 Critical','major'=>'🟡 Major','minor'=>'🟢 Minor','trivial'=>'⚪ Trivial'] as $sv=>$sl)
                        <option value="{{ $sv }}" {{ old('severity')===$sv?'selected':'' }}>{{ $sl }}</option>
                        @endforeach
                    </select>
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
        type: '{{ old('type', 'task') }}',
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
