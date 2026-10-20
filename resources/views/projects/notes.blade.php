@extends('layouts.app')
@section('title', $project->name.' — Quick Notes')

@section('content')
<div class="page-header">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
            <a href="{{ route('projects.show', $project) }}" class="hover:text-slate-600">{{ $project->name }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span>Quick Notes</span>
        </div>
        <h1 class="page-title flex items-center gap-2">
            <i class="bi bi-journal-text text-violet-500"></i>
            Quick Notes
        </h1>
        <p class="text-sm text-slate-400 mt-0.5">Capture ideas during meetings, then convert each note into a task.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('projects.show', $project) }}" class="btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Back to Project</a>
        <a href="{{ route('projects.tasks.kanban', $project) }}" class="btn-secondary btn-sm"><i class="bi bi-kanban"></i> Board</a>
    </div>
</div>

<div x-data="{
    notes: @js($notes->map(fn($n) => [
        'id'           => $n->id,
        'body'         => $n->body,
        'is_converted' => $n->is_converted,
        'task_id'      => $n->task_id,
        'task_url'     => $n->task ? route('projects.tasks.show', $n->task) : null,
        'user_name'    => $n->user->name,
        'created_at'   => $n->created_at->diffForHumans(),
        'editing'      => false,
        'editBody'     => $n->body,
    ])),
    newNote: '',
    submitting: false,

    async addNote() {
        if (!this.newNote.trim() || this.submitting) return;
        this.submitting = true;
        try {
            const r = await axios.post('/projects/{{ $project->id }}/notes', { body: this.newNote });
            const n = r.data.note;
            this.notes.unshift({
                id: n.id,
                body: n.body,
                is_converted: false,
                task_id: null,
                task_url: null,
                user_name: n.user?.name ?? 'You',
                created_at: 'just now',
                editing: false,
                editBody: n.body,
            });
            this.newNote = '';
        } catch(e) { window.toast('Failed to save note', 'error'); }
        this.submitting = false;
    },

    async saveEdit(note) {
        if (!note.editBody.trim()) return;
        try {
            await axios.patch('/project-notes/' + note.id, { body: note.editBody });
            note.body = note.editBody;
            note.editing = false;
            window.toast('Note updated', 'success');
        } catch(e) { window.toast('Failed to update', 'error'); }
    },

    async deleteNote(note) {
        if (!confirm('Delete this note?')) return;
        try {
            await axios.delete('/project-notes/' + note.id);
            this.notes = this.notes.filter(n => n.id !== note.id);
            window.toast('Note deleted', 'success');
        } catch(e) { window.toast('Failed to delete', 'error'); }
    },

    async convertToTask(note) {
        try {
            const r = await axios.post('/project-notes/' + note.id + '/convert');
            note.is_converted = true;
            note.task_id = r.data.task.id;
            note.task_url = r.data.task_url;
            window.toast('Task created!', 'success');
        } catch(e) {
            window.toast(e.response?.data?.error ?? 'Failed to convert', 'error');
        }
    },
}" class="max-w-3xl mx-auto space-y-5">

    {{-- Quick Add Input --}}
    <div class="card p-5">
        <div class="flex items-start gap-3">
            <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full flex-shrink-0 mt-1">
            <div class="flex-1">
                <textarea
                    x-model="newNote"
                    rows="3"
                    placeholder="Jot a note... (Enter to add, Shift+Enter for new line)"
                    class="form-input resize-none w-full text-sm"
                    @keydown.enter.prevent="if (!$event.shiftKey) addNote()"
                ></textarea>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-xs text-slate-400">Press <kbd class="px-1.5 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600 font-mono">Enter</kbd> to add · <kbd class="px-1.5 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600 font-mono">Shift+Enter</kbd> for new line</p>
                    <button @click="addNote()" :disabled="!newNote.trim() || submitting"
                            class="btn-primary btn-sm disabled:opacity-50">
                        <i class="bi bi-plus-lg"></i> Add Note
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Notes count summary --}}
    <div class="flex items-center justify-between px-1" x-show="notes.length > 0">
        <p class="text-xs text-slate-500">
            <span x-text="notes.filter(n => !n.is_converted).length"></span> pending ·
            <span x-text="notes.filter(n => n.is_converted).length"></span> converted to tasks
        </p>
        <p class="text-xs text-slate-400" x-text="notes.length + ' total notes'"></p>
    </div>

    {{-- Notes List --}}
    <div class="space-y-3 overflow-y-auto" style="max-height: calc(100vh - 380px); min-height: 200px;">
        <template x-if="notes.length === 0">
            <div class="card p-16 text-center">
                <i class="bi bi-journal-text text-4xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 mt-3">No notes yet — start capturing ideas above</p>
            </div>
        </template>

        <template x-for="note in notes" :key="note.id">
            <div class="card overflow-hidden transition-all"
                 :class="note.is_converted ? 'opacity-70' : ''">
                <div class="flex items-start gap-4 p-4">

                    {{-- Status dot --}}
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-2.5 h-2.5 rounded-full mt-1.5"
                             :class="note.is_converted ? 'bg-green-400' : 'bg-violet-400'"></div>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        {{-- Edit mode --}}
                        <div x-show="note.editing">
                            <textarea x-model="note.editBody" rows="3"
                                      class="form-input resize-none w-full text-sm mb-2"
                                      @keydown.escape="note.editing = false; note.editBody = note.body"></textarea>
                            <div class="flex gap-2">
                                <button @click="saveEdit(note)" class="btn-primary btn-sm">Save</button>
                                <button @click="note.editing = false; note.editBody = note.body" class="btn-ghost btn-sm">Cancel</button>
                            </div>
                        </div>

                        {{-- View mode --}}
                        <div x-show="!note.editing">
                            <p class="text-sm text-slate-800 dark:text-slate-100 whitespace-pre-wrap leading-relaxed"
                               x-text="note.body"></p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[10px] text-slate-400" x-text="note.created_at"></span>
                                <template x-if="note.is_converted && note.task_url">
                                    <a :href="note.task_url"
                                       class="inline-flex items-center gap-1 text-[10px] text-green-600 dark:text-green-400 font-medium hover:underline">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Converted to task
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 flex-shrink-0" x-show="!note.editing">

                        {{-- Convert to Task button --}}
                        <template x-if="!note.is_converted">
                            <button @click="convertToTask(note)"
                                    title="Convert to task"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40 rounded-lg transition-colors">
                                <i class="bi bi-arrow-right-circle"></i>
                                Make Task
                            </button>
                        </template>

                        <template x-if="note.is_converted && note.task_url">
                            <a :href="note.task_url"
                               title="View task"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 rounded-lg transition-colors">
                                <i class="bi bi-box-arrow-up-right"></i>
                                View Task
                            </a>
                        </template>

                        <template x-if="!note.is_converted">
                            <button @click="note.editing = true; note.editBody = note.body"
                                    title="Edit note"
                                    class="p-1.5 text-slate-300 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <i class="bi bi-pencil text-xs"></i>
                            </button>
                        </template>

                        <button @click="deleteNote(note)"
                                title="Delete note"
                                class="p-1.5 text-slate-300 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Tip --}}
    <div class="text-center py-2" x-show="notes.length > 0">
        <p class="text-xs text-slate-400">
            <i class="bi bi-lightbulb mr-1"></i>
            Hit <kbd class="px-1.5 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600 font-mono">Make Task</kbd> to turn any note into a real task in the backlog
        </p>
    </div>
</div>
@endsection
