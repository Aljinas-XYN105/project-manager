{{--
    Global task quick-edit panel.
    Open from anywhere: window.dispatchEvent(new CustomEvent('open-task-panel', { detail: { taskId: N } }))
--}}
<div x-data="taskQuickPanel()"
     @open-task-panel.window="open($event.detail.taskId)"
     @keydown.escape.window="if(show) close()">

    {{-- Backdrop --}}
    <div x-show="show" x-cloak
         class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm"
         @click="close()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    {{-- Panel --}}
    <div x-show="show" x-cloak
         class="fixed inset-y-0 right-0 z-50 flex flex-col w-full sm:max-w-lg
                bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        {{-- Loading state --}}
        <template x-if="loading">
            <div class="flex-1 flex items-center justify-center">
                <div class="flex flex-col items-center gap-3 text-slate-400">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
                    <span class="text-sm">Loading task…</span>
                </div>
            </div>
        </template>

        <template x-if="!loading && task">

            <div class="flex flex-col flex-1 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-start justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                             :class="{
                                'bg-rose-100 dark:bg-rose-900/30':   task.type==='bug',
                                'bg-purple-100 dark:bg-purple-900/30': task.type==='feature',
                                'bg-blue-100 dark:bg-blue-900/30':   task.type==='story',
                                'bg-orange-100 dark:bg-orange-900/30': task.type==='epic',
                                'bg-slate-100 dark:bg-slate-800':    !['bug','feature','story','epic'].includes(task.type)
                             }">
                            <i class="text-sm" :class="task.type_icon + ' ' + task.type_color"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono text-xs text-slate-400" x-text="task.ticket_key"></span>
                                <span class="text-xs text-slate-400">·</span>
                                <span class="text-xs text-slate-500 truncate" x-text="task.project_name"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                        <a :href="task.show_url" title="View full details"
                           class="btn-ghost btn-sm p-1.5 text-slate-400 hover:text-violet-600" target="_self">
                            <i class="bi bi-box-arrow-up-right text-sm"></i>
                        </a>
                        <a :href="task.edit_url" title="Full edit page"
                           class="btn-ghost btn-sm p-1.5 text-slate-400 hover:text-violet-600">
                            <i class="bi bi-pencil-square text-sm"></i>
                        </a>
                        <button @click="close()" class="btn-ghost btn-sm p-1.5">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                    {{-- Title --}}
                    <div x-data="{ editing: false }">
                        <div x-show="!editing" @click="editing = true"
                             class="group cursor-text">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-snug
                                       group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors"
                                x-text="task.title"></h2>
                            <p class="text-xs text-slate-400 mt-0.5 group-hover:text-violet-400">Click to edit title</p>
                        </div>
                        <div x-show="editing" x-cloak>
                            <textarea x-model="task.title" rows="2"
                                      class="form-input text-base font-semibold resize-none w-full"
                                      x-init="$el.focus()"
                                      @blur="editing = false; patch({ title: task.title })"
                                      @keydown.enter.prevent="editing = false; patch({ title: task.title })"
                                      @keydown.escape="editing = false"></textarea>
                        </div>
                    </div>

                    {{-- Type + Priority --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Type</label>
                            <select x-model="task.type" @change="patch({ type: task.type })" class="form-select">
                                <option value="task">Task</option>
                                <option value="bug">Bug</option>
                                <option value="feature">Feature</option>
                                <option value="story">Story</option>
                                <option value="epic">Epic</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Priority</label>
                            <select x-model="task.priority" @change="patch({ priority: task.priority })" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    {{-- Column + Due Date --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Column / Stage</label>
                            <select x-model.number="task.column_id" @change="patch({ column_id: task.column_id })" class="form-select">
                                <template x-for="col in columns" :key="col.id">
                                    <option :value="col.id" x-text="col.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Due Date</label>
                            <input type="date" x-model="task.due_date"
                                   @change="patch({ due_date: task.due_date })"
                                   class="form-input"
                                   :class="task.is_overdue ? 'border-rose-400 bg-rose-50 dark:bg-rose-900/10' : ''">
                        </div>
                    </div>

                    {{-- Estimated hours --}}
                    <div>
                        <label class="form-label">Estimate (hours)</label>
                        <input type="number" min="0" step="0.5" x-model="task.estimated_hours"
                               @blur="patch({ estimated_hours: task.estimated_hours })"
                               @keydown.enter="$el.blur()"
                               class="form-input w-32" placeholder="0">
                    </div>

                    {{-- Assignees --}}
                    <div>
                        <label class="form-label">Assignees</label>
                        <div class="flex flex-wrap gap-1.5 mb-2" x-show="task.assignee_ids.length > 0">
                            <template x-for="id in task.assignee_ids" :key="id">
                                <span class="inline-flex items-center gap-1 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs px-2 py-0.5 rounded-full">
                                    <span x-text="memberName(id)"></span>
                                    <button type="button" @click="toggleAssignee(id)"
                                            class="hover:text-rose-500 transition-colors">
                                        <i class="bi bi-x text-xs"></i>
                                    </button>
                                </span>
                            </template>
                        </div>
                        <div class="grid grid-cols-2 gap-1 max-h-40 overflow-y-auto p-2
                                    bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                            <template x-for="m in members" :key="m.id">
                                <button type="button" @click="toggleAssignee(m.id)"
                                        class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-left transition-colors"
                                        :class="task.assignee_ids.includes(m.id)
                                            ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300'
                                            : 'hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
                                    <img :src="m.avatar" :alt="m.name" class="w-5 h-5 rounded-full flex-shrink-0">
                                    <span class="text-xs truncate flex-1" x-text="m.name"></span>
                                    <i x-show="task.assignee_ids.includes(m.id)"
                                       class="bi bi-check-lg text-violet-500 text-xs flex-shrink-0"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Labels --}}
                    <div x-show="labels.length > 0">
                        <label class="form-label">Labels</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="lbl in labels" :key="lbl.id">
                                <button type="button" @click="toggleLabel(lbl.id)"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold text-white transition-all"
                                        :style="task.label_ids.includes(lbl.id)
                                            ? 'background-color:' + lbl.color + '; box-shadow: 0 0 0 2px ' + lbl.color
                                            : 'background-color:' + lbl.color + '55'"
                                        x-text="lbl.name">
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Attachments --}}
                    <div x-data="panelAttachments(task.id, task.attachments ?? [])">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="form-label mb-0">Attachments
                                <span class="font-normal text-slate-400" x-text="'(' + atts.length + ')'"></span>
                            </label>
                            <label class="btn-ghost btn-sm text-xs cursor-pointer py-0.5 px-2">
                                <i class="bi bi-plus-lg text-xs"></i> Add
                                <input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                            </label>
                        </div>

                        {{-- Drop zone --}}
                        <div class="rounded-xl border-2 border-dashed transition-colors mb-2"
                             :class="dropping ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/10' : 'border-slate-200 dark:border-slate-700'"
                             @dragover.prevent="dropping = true"
                             @dragleave.prevent="dropping = false"
                             @drop.prevent="dropping = false; upload($event.dataTransfer.files)">
                            <div class="py-3 flex items-center justify-center gap-2 text-center">
                                <i class="bi bi-cloud-upload text-slate-300 text-lg" :class="dropping ? 'text-violet-400' : ''"></i>
                                <p class="text-xs text-slate-400" :class="dropping ? 'text-violet-500' : ''">Drop or <label class="text-violet-500 cursor-pointer font-medium">browse<input type="file" class="sr-only" multiple @change="upload($event.target.files)"></label></p>
                            </div>
                        </div>

                        {{-- Upload bar --}}
                        <template x-if="uploading">
                            <div class="mb-2 flex items-center gap-2 text-xs text-violet-600 dark:text-violet-400">
                                <i class="bi bi-arrow-up animate-pulse"></i>
                                <span x-text="uploadStatus"></span>
                                <div class="flex-1 h-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-500 rounded-full transition-all" :style="'width:'+uploadPct+'%'"></div>
                                </div>
                            </div>
                        </template>

                        {{-- File list --}}
                        <div class="space-y-1">
                            <template x-for="a in atts" :key="a.id">
                                <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg
                                            hover:bg-slate-50 dark:hover:bg-slate-800/50 group transition-colors">
                                    <i class="text-sm flex-shrink-0"
                                       :class="a.is_image ? 'bi bi-image text-sky-500'
                                           : a.is_video ? 'bi bi-play-circle-fill text-violet-500'
                                           : a.mime_type && a.mime_type.includes('pdf') ? 'bi bi-file-earmark-pdf text-rose-500'
                                           : 'bi bi-file-earmark text-slate-400'"></i>
                                    <span class="text-xs text-slate-700 dark:text-slate-300 truncate flex-1" x-text="a.original_name"></span>
                                    <span class="text-[10px] text-slate-400 flex-shrink-0" x-text="a.size_formatted"></span>
                                    <a :href="a.url" target="_blank"
                                       class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-violet-500 transition-all">
                                        <i class="bi bi-download text-xs"></i>
                                    </a>
                                    <button @click="remove(a.id)"
                                            class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 transition-all">
                                        <i class="bi bi-trash text-xs"></i>
                                    </button>
                                </div>
                            </template>
                            <p x-show="atts.length === 0 && !uploading"
                               class="text-xs text-slate-400 text-center py-1">No files attached</p>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div x-data="{ editDesc: false }">
                        <label class="form-label">Description</label>
                        <div x-show="!editDesc"
                             @click="editDesc = true"
                             class="group cursor-text min-h-[60px] p-3 rounded-xl border border-slate-200 dark:border-slate-700
                                    hover:border-violet-300 dark:hover:border-violet-700 transition-colors bg-slate-50 dark:bg-slate-800/50">
                            <template x-if="task.description">
                                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed"
                                   x-text="task.description"></p>
                            </template>
                            <template x-if="!task.description">
                                <p class="text-sm text-slate-400 italic">Click to add a description…</p>
                            </template>
                        </div>
                        <div x-show="editDesc" x-cloak>
                            <textarea x-model="task.description" rows="5"
                                      class="form-input resize-none w-full"
                                      x-init="$el.focus()"
                                      placeholder="Add details, context or acceptance criteria…"
                                      @blur="editDesc = false; patch({ description: task.description })"
                                      @keydown.escape="editDesc = false"></textarea>
                            <p class="text-xs text-slate-400 mt-1">Click outside or press Esc to save.</p>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-slate-50/50 dark:bg-slate-800/20">
                    <div class="flex items-center gap-2">
                        <div x-show="saving" class="flex items-center gap-1.5 text-xs text-slate-400">
                            <i class="bi bi-arrow-repeat animate-spin text-xs"></i> Saving…
                        </div>
                        <div x-show="saved && !saving" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                            <i class="bi bi-check-circle-fill text-xs"></i> Saved
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="task.edit_url" class="btn-ghost btn-sm text-xs">
                            <i class="bi bi-pencil-square"></i> Full edit
                        </a>
                        <button @click="close()" class="btn-secondary btn-sm text-xs">Close</button>
                    </div>
                </div>

            </div>
        </template>
    </div>
</div>

<script>
function taskQuickPanel() {
    return {
        show:    false,
        loading: false,
        saving:  false,
        saved:   false,
        task:    null,
        columns: [],
        members: [],
        labels:  [],
        _saveTimer: null,

        open(taskId) {
            if (!taskId) return;
            this.show    = true;
            this.loading = true;
            this.task    = null;
            document.body.style.overflow = 'hidden';

            axios.get(`/tasks/${taskId}/panel-data`)
                .then(r => {
                    this.task         = { ...r.data.task, attachments: r.data.attachments };
                    this.columns      = r.data.columns;
                    this.members      = r.data.members;
                    this.labels       = r.data.labels;
                    this.loading      = false;
                })
                .catch(() => {
                    this.loading = false;
                    this.show    = false;
                    window.toast?.('Could not load task', 'error');
                });
        },

        close() {
            this.show = false;
            document.body.style.overflow = '';
        },

        patch(data) {
            if (!this.task) return;
            this.saving = true;
            this.saved  = false;
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => {
                axios.patch(`/tasks/${this.task.id}`, data)
                    .then(() => {
                        this.saving = false;
                        this.saved  = true;
                        setTimeout(() => { this.saved = false; }, 2000);
                    })
                    .catch(() => {
                        this.saving = false;
                        window.toast?.('Failed to save', 'error');
                    });
            }, 300);
        },

        toggleAssignee(id) {
            const idx = this.task.assignee_ids.indexOf(id);
            if (idx === -1) {
                this.task.assignee_ids.push(id);
                axios.post(`/tasks/${this.task.id}/assignees`, { user_id: id });
            } else {
                this.task.assignee_ids.splice(idx, 1);
                axios.delete(`/tasks/${this.task.id}/assignees/${id}`);
            }
        },

        toggleLabel(id) {
            const idx = this.task.label_ids.indexOf(id);
            if (idx === -1) this.task.label_ids.push(id);
            else this.task.label_ids.splice(idx, 1);
            this.patch({ label_ids: this.task.label_ids });
        },

        memberName(id) {
            return this.members.find(m => m.id === id)?.name ?? '';
        },
    };
}

function panelAttachments(taskId, initialAtts) {
    return {
        atts: initialAtts || [],
        uploading: false,
        uploadPct: 0,
        uploadStatus: '',
        dropping: false,

        async upload(fileList) {
            if (!fileList?.length) return;
            this.uploading = true;
            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                this.uploadStatus = `Uploading ${file.name}… (${i+1}/${fileList.length})`;
                const form = new FormData();
                form.append('file', file);
                form.append('_token', document.querySelector('meta[name=csrf-token]').content);
                try {
                    const res = await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', `/tasks/${taskId}/attachments`);
                        xhr.upload.onprogress = e => {
                            if (e.lengthComputable) this.uploadPct = Math.round(e.loaded/e.total*100);
                        };
                        xhr.onload  = () => xhr.status < 300 ? resolve(JSON.parse(xhr.responseText)) : reject();
                        xhr.onerror = () => reject();
                        xhr.send(form);
                    });
                    const a = res.attachment;
                    this.atts.push({
                        id: a.id, original_name: a.original_name, mime_type: a.mime_type,
                        size_formatted: a.size_formatted, is_image: a.is_image, is_video: a.is_video,
                        url: res.url,
                    });
                    window.toast?.(`${file.name} uploaded`, 'success');
                } catch {
                    window.toast?.(`Failed to upload ${file.name}`, 'error');
                }
            }
            this.uploading = false; this.uploadPct = 0;
        },

        async remove(id) {
            if (!confirm('Delete this attachment?')) return;
            const token = document.querySelector('meta[name=csrf-token]').content;
            try {
                const res = await fetch(`/attachments/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                });
                if (res.ok) {
                    this.atts = this.atts.filter(a => a.id !== id);
                    window.toast?.('Attachment deleted', 'success');
                }
            } catch { window.toast?.('Could not delete', 'error'); }
        },
    };
}
</script>
