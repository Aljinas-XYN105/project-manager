{{-- Shared task breakdown for create/edit. Expects $projects; the parent form's x-data is dailyReportForm(). --}}
@if($errors->any())
<div class="alert-danger">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <p class="font-semibold">Please fix the following:</p>
        <ul class="list-disc pl-4 mt-1 text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="card p-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <label class="form-label mb-0">Task Breakdown</label>
            <p class="text-xs text-slate-400 mt-0.5">Log hours per task. Can't find the task? Pick <strong>“Create a new task”</strong>.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-slate-900 dark:text-white">
                Total: <span x-text="totalHours.toFixed(1)"></span>h
            </span>
            <button type="button" @click="addTask()" class="btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add Row
            </button>
        </div>
    </div>

    <div class="space-y-3">
        <template x-for="(task, idx) in tasks" :key="task._id">
            <div class="border rounded-xl p-4 space-y-3 transition-colors"
                 :class="task.task_id === '__new__'
                    ? 'border-violet-300 dark:border-violet-700 bg-violet-50/40 dark:bg-violet-900/10'
                    : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30'">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0 space-y-3">
                        {{-- Task selector --}}
                        <div>
                            <label class="form-label">Task</label>
                            <select x-model="task.task_id" @change="onTaskPicked(task)" class="form-select text-sm">
                                <option value="">— Not linked to a task —</option>
                                <option value="__new__">＋ Create a new task…</option>
                                <template x-for="opt in optionsFor(task)" :key="opt.id">
                                    <option :value="String(opt.id)" x-text="opt.label" :selected="String(opt.id) === String(task.task_id)"></option>
                                </template>
                            </select>
                            <input type="hidden" :name="'tasks['+idx+'][task_id]'" :value="task.task_id === '__new__' ? '' : task.task_id">
                        </div>

                        {{-- Inline new task --}}
                        <template x-if="task.task_id === '__new__'">
                            <div class="rounded-xl border border-violet-200 dark:border-violet-800 bg-white dark:bg-slate-900 p-3.5 space-y-3">
                                <p class="text-xs font-semibold text-violet-700 dark:text-violet-300 flex items-center gap-1.5">
                                    <i class="bi bi-plus-circle-fill"></i> New task — created and assigned to you when you submit
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Task title *</label>
                                        <input type="text" :name="'tasks['+idx+'][new_task][title]'" x-model="task.new_task.title"
                                               @input="if (!task._descTouched) task.description = task.new_task.title"
                                               required maxlength="255" class="form-input text-sm" placeholder="e.g. Fix checkout total rounding">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Project *</label>
                                        <select :name="'tasks['+idx+'][new_task][project_id]'" x-model="task.new_task.project_id" required class="form-select text-sm">
                                            <option value="">Select a project…</option>
                                            @foreach($projects as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Type</label>
                                        <select :name="'tasks['+idx+'][new_task][type]'" x-model="task.new_task.type" class="form-select text-sm">
                                            <option value="task">Task</option>
                                            <option value="bug">Bug</option>
                                            <option value="feature">Feature</option>
                                            <option value="story">Story</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Priority</label>
                                        <select :name="'tasks['+idx+'][new_task][priority]'" x-model="task.new_task.priority" class="form-select text-sm">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                </div>
                                @if($projects->isEmpty())
                                <p class="text-xs text-amber-600">You're not a member of any project yet — ask a manager to add you.</p>
                                @endif
                            </div>
                        </template>

                        {{-- Description --}}
                        <div>
                            <label class="form-label">What was done? *</label>
                            <input type="text" :name="'tasks['+idx+'][description]'" x-model="task.description"
                                   @input="task._descTouched = true"
                                   required class="form-input text-sm" placeholder="Brief description of what you worked on…">
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="form-label">Hours *</label>
                                <input type="number" :name="'tasks['+idx+'][hours]'" x-model.number="task.hours"
                                       step="0.5" min="0" max="24" required class="form-input text-sm text-center"
                                       @input="calcTotals()">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="form-label">Status After Today</label>
                                <select :name="'tasks['+idx+'][status_update]'" x-model="task.status_update" class="form-select text-sm">
                                    <option value="">No change</option>
                                    <option value="in_progress">🔵 In Progress</option>
                                    <option value="in_review">🟡 In Review</option>
                                    <option value="done">✅ Done</option>
                                    <option value="blocked">🔴 Blocked</option>
                                    <option value="todo">⚪ Back to To Do</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 cursor-pointer py-2">
                                    <input type="checkbox" :name="'tasks['+idx+'][is_blocked]'" x-model="task.is_blocked" value="1"
                                           class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">
                                        <i class="bi bi-exclamation-octagon text-rose-500 mr-0.5"></i> Blocked
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Notes / Context (optional)</label>
                            <input type="text" :name="'tasks['+idx+'][notes]'" x-model="task.notes"
                                   class="form-input text-sm" placeholder="Any blockers, links, or context…">
                        </div>
                    </div>
                    <button type="button" @click="removeTask(idx)"
                            class="flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors mt-6">
                        <i class="bi bi-trash3 text-sm"></i>
                    </button>
                </div>
                <div x-show="task.is_blocked || task.status_update === 'blocked'" x-cloak
                     class="flex items-center gap-2 px-3 py-2 rounded-lg bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-400">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>This task is marked as blocked — describe the blocker in your notes or the Blockers box.</span>
                </div>
            </div>
        </template>

        <button type="button" @click="addTask()"
                class="w-full py-3 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700
                       text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300
                       hover:border-violet-300 dark:hover:border-violet-700 hover:bg-violet-50 dark:hover:bg-violet-900/10
                       transition-all flex items-center justify-center gap-2">
            <i class="bi bi-plus-lg"></i> Add another task
        </button>
    </div>

    <div x-show="tasks.length > 0" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Total hours logged</span>
            <span class="font-bold text-slate-900 dark:text-white" x-text="totalHours.toFixed(1) + 'h'"></span>
        </div>
        <div class="mt-2 h-2 bg-slate-100 dark:bg-slate-800 rounded-full">
            <div class="h-2 rounded-full transition-all duration-500"
                 :class="totalHours >= 8 ? 'bg-emerald-500' : (totalHours >= 4 ? 'bg-amber-500' : 'bg-rose-400')"
                 :style="'width:'+Math.min(100, totalHours/8*100)+'%'"></div>
        </div>
        <p class="text-xs text-slate-400 mt-1" x-text="totalHours >= 8 ? 'Great work today!' : (totalHours >= 4 ? 'Halfway there' : 'Less than half a day logged')"></p>
    </div>
</div>

@push('scripts')
<script>
function dailyReportForm(opts) {
    const blankNewTask = () => ({ project_id: '', title: '', type: 'task', priority: 'medium' });
    const normalize = (t, i) => ({
        _id: i + 1,
        _descTouched: !!t.description,
        task_id: t.new_task ? '__new__' : String(t.task_id ?? ''),
        task_title: t.task_title ?? null,
        description: t.description ?? '',
        hours: parseFloat(t.hours) || 0,
        notes: t.notes ?? '',
        status_update: t.status_update ?? '',
        is_blocked: !!t.is_blocked && t.is_blocked !== '0',
        new_task: Object.assign(blankNewTask(), t.new_task || {}),
    });

    return {
        summary: opts.summary ?? '',
        planForTomorrow: opts.plan ?? '',
        blockers: opts.blockers ?? '',
        myTasks: opts.myTasks,
        tasks: (opts.rows || []).map(normalize),
        totalHours: 0,
        _uid: 0,
        init() {
            this._uid = this.tasks.length;
            if (!this.tasks.length) this.addTask();
            this.calcTotals();
        },
        addTask() {
            this.tasks.push(normalize({}, this._uid++));
        },
        removeTask(idx) {
            this.tasks.splice(idx, 1);
            this.calcTotals();
        },
        // Keep a row's existing link visible even if that task is no longer in "my open tasks".
        optionsFor(row) {
            const opts = this.myTasks.slice();
            if (row.task_id && row.task_id !== '__new__' && !opts.some(o => String(o.id) === row.task_id)) {
                opts.unshift({ id: row.task_id, label: row.task_title || ('Task #' + row.task_id) });
            }
            return opts;
        },
        onTaskPicked(row) {
            if (row.task_id === '__new__') {
                if (!row.new_task.title && row.description) row.new_task.title = row.description;
                return;
            }
            const found = this.myTasks.find(t => String(t.id) === row.task_id);
            if (found && !row._descTouched) row.description = found.title;
        },
        calcTotals() {
            this.totalHours = this.tasks.reduce((sum, t) => sum + (parseFloat(t.hours) || 0), 0);
        },
    };
}
</script>
@endpush
