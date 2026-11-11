<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserAvatar from '@/Components/UserAvatar.vue'

const props = defineProps({
    project: Object,
    notes: Array,
    auth_avatar: String,
    store_url: String,
})

const notesList = ref(props.notes.map(n => ({ ...n, editing: false, editBody: n.body })))
const newNote = ref('')
const submitting = ref(false)

const pendingCount = computed(() => notesList.value.filter(n => !n.is_converted).length)
const convertedCount = computed(() => notesList.value.filter(n => n.is_converted).length)

function csrfToken() {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '')
}

async function addNote() {
    if (!newNote.value.trim() || submitting.value) return
    submitting.value = true
    try {
        const r = await fetch(props.store_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ body: newNote.value }),
        })
        const data = await r.json()
        const n = data.note
        notesList.value.unshift({
            id: n.id,
            body: n.body,
            is_converted: false,
            task_id: null,
            task_url: null,
            user_name: n.user?.name ?? 'You',
            created_at: 'just now',
            store_url: props.store_url,
            update_url: `/project-notes/${n.id}`,
            destroy_url: `/project-notes/${n.id}`,
            convert_url: `/project-notes/${n.id}/convert`,
            editing: false,
            editBody: n.body,
        })
        newNote.value = ''
    } catch (e) {}
    submitting.value = false
}

async function saveEdit(note) {
    if (!note.editBody.trim()) return
    try {
        await fetch(note.update_url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ body: note.editBody }),
        })
        note.body = note.editBody
        note.editing = false
    } catch (e) {}
}

async function deleteNote(note) {
    if (!confirm('Delete this note?')) return
    try {
        await fetch(note.destroy_url, { method: 'DELETE', headers: { 'X-XSRF-TOKEN': csrfToken() } })
        notesList.value = notesList.value.filter(n => n.id !== note.id)
    } catch (e) {}
}

async function convertToTask(note) {
    try {
        const r = await fetch(note.convert_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
        })
        const data = await r.json()
        if (data.error) { alert(data.error); return }
        note.is_converted = true
        note.task_id = data.task.id
        note.task_url = data.task_url
    } catch (e) {}
}
</script>

<template>
    <AppLayout :title="project.name + ' — Quick Notes'">
        <div class="page-header">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                    <a :href="project.show_url" class="hover:text-slate-600">{{ project.name }}</a>
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
                <a :href="project.show_url" class="btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Back to Project</a>
                <a :href="project.kanban_url" class="btn-secondary btn-sm"><i class="bi bi-kanban"></i> Board</a>
            </div>
        </div>

        <div class="max-w-3xl mx-auto space-y-5">
            <!-- Quick Add Input -->
            <div class="card p-5">
                <div class="flex items-start gap-3">
                    <UserAvatar :src="auth_avatar ?? ''" :name="$page.props.auth?.user?.name ?? ''" size="md" class="flex-shrink-0 mt-1" />
                    <div class="flex-1">
                        <textarea v-model="newNote" rows="3"
                                  placeholder="Jot a note... (Enter to add, Shift+Enter for new line)"
                                  class="form-input resize-none w-full text-sm"
                                  @keydown.enter.exact.prevent="addNote"></textarea>
                        <div class="flex items-center justify-between mt-2">
                            <p class="text-xs text-slate-400">Press
                                <kbd class="px-1.5 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600 font-mono">Enter</kbd>
                                to add ·
                                <kbd class="px-1.5 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-700 rounded border border-slate-200 dark:border-slate-600 font-mono">Shift+Enter</kbd>
                                for new line
                            </p>
                            <button @click="addNote" :disabled="!newNote.trim() || submitting"
                                    class="btn-primary btn-sm disabled:opacity-50">
                                <i class="bi bi-plus-lg"></i> Add Note
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes count summary -->
            <div v-if="notesList.length > 0" class="flex items-center justify-between px-1">
                <p class="text-xs text-slate-500">
                    {{ pendingCount }} pending · {{ convertedCount }} converted to tasks
                </p>
                <p class="text-xs text-slate-400">{{ notesList.length }} total notes</p>
            </div>

            <!-- Notes List -->
            <div v-if="notesList.length === 0" class="card p-16 text-center">
                <i class="bi bi-journal-text text-4xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 mt-3">No notes yet — start capturing ideas above</p>
            </div>

            <div v-for="note in notesList" :key="note.id"
                 :class="['card overflow-hidden transition-all', note.is_converted ? 'opacity-70' : '']">
                <div class="flex items-start gap-4 p-4">
                    <!-- Status dot -->
                    <div class="flex-shrink-0 mt-1">
                        <div :class="['w-2.5 h-2.5 rounded-full mt-1.5', note.is_converted ? 'bg-green-400' : 'bg-violet-400']"></div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <!-- Edit mode -->
                        <div v-if="note.editing">
                            <textarea v-model="note.editBody" rows="3"
                                      class="form-input resize-none w-full text-sm mb-2"
                                      @keydown.escape="note.editing = false; note.editBody = note.body"></textarea>
                            <div class="flex gap-2">
                                <button @click="saveEdit(note)" class="btn-primary btn-sm">Save</button>
                                <button @click="note.editing = false; note.editBody = note.body" class="btn-ghost btn-sm">Cancel</button>
                            </div>
                        </div>
                        <!-- View mode -->
                        <div v-else>
                            <p class="text-sm text-slate-800 dark:text-slate-100 whitespace-pre-wrap leading-relaxed">{{ note.body }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[10px] text-slate-400">{{ note.created_at }}</span>
                                <a v-if="note.is_converted && note.task_url" :href="note.task_url"
                                   class="inline-flex items-center gap-1 text-[10px] text-green-600 dark:text-green-400 font-medium hover:underline">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Converted to task
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="!note.editing" class="flex items-center gap-1 flex-shrink-0">
                        <button v-if="!note.is_converted" @click="convertToTask(note)"
                                title="Convert to task"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40 rounded-lg transition-colors">
                            <i class="bi bi-arrow-right-circle"></i>
                            Make Task
                        </button>
                        <a v-else-if="note.task_url" :href="note.task_url"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 rounded-lg transition-colors">
                            <i class="bi bi-box-arrow-up-right"></i>
                            View Task
                        </a>
                        <button v-if="!note.is_converted" @click="note.editing = true; note.editBody = note.body"
                                class="btn-ghost btn-xs" title="Edit note">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button @click="deleteNote(note)" class="btn-ghost btn-xs text-rose-500" title="Delete note">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
