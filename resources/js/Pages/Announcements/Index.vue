<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    announcements: Object,
    can_manage: Boolean,
    show_expired: Boolean,
})

const categoryStyles = {
    general:    { icon: 'bi-megaphone',          color: 'violet', label: 'General' },
    policy:     { icon: 'bi-file-earmark-text',  color: 'blue',   label: 'Policy' },
    event:      { icon: 'bi-calendar-event',      color: 'emerald',label: 'Event' },
    update:     { icon: 'bi-arrow-up-circle',     color: 'amber',  label: 'Update' },
    alert:      { icon: 'bi-exclamation-triangle',color: 'rose',   label: 'Alert' },
}

function styleFor(category) {
    return categoryStyles[category] ?? categoryStyles.general
}

const showModal = ref(false)
const modalAction = ref('')
const modalMethod = ref('POST')
const modalForm = useForm({
    title: '', body: '', category: 'general', expires_on: '', is_pinned: false, notify: true,
})

function openNew() {
    modalAction.value = route('announcements.store')
    modalMethod.value = 'POST'
    modalForm.reset()
    showModal.value = true
}

function openEdit(a) {
    modalAction.value = a.edit_url
    modalMethod.value = 'PUT'
    modalForm.title     = a.title
    modalForm.body      = a.body
    modalForm.category  = a.category ?? 'general'
    modalForm.expires_on= a.expires_on ?? ''
    modalForm.is_pinned = a.is_pinned
    showModal.value = true
}

function submitModal() {
    const opts = { onSuccess: () => { showModal.value = false } }
    if (modalMethod.value === 'PUT') {
        modalForm.put(modalAction.value, opts)
    } else {
        modalForm.post(modalAction.value, opts)
    }
}

function deleteAnnouncement(a) {
    if (!confirm('Delete this announcement?')) return
    router.delete(a.destroy_url)
}

function toggleExpired() {
    router.get(route('announcements.index'), show_expired ? {} : { expired: 1 })
}
</script>

<template>
    <AppLayout title="Announcements">
        <div class="max-w-3xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Announcements</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Company news, policies and events.</p>
                </div>
                <div v-if="can_manage" class="flex gap-2">
                    <button @click="toggleExpired" class="btn-ghost btn-sm">
                        {{ show_expired ? 'Hide expired' : 'Show expired' }}
                    </button>
                    <button @click="openNew" class="btn-primary"><i class="bi bi-megaphone"></i> New announcement</button>
                </div>
            </div>

            <div class="space-y-4">
                <div v-if="announcements.data.length === 0" class="card py-16 text-center">
                    <i class="bi bi-megaphone text-4xl text-slate-300 dark:text-slate-600 block mb-3"></i>
                    <p class="text-sm text-slate-500">No announcements right now.</p>
                </div>
                <article v-for="a in announcements.data" :key="a.id"
                         class="card p-5"
                         :class="[a.is_pinned ? 'ring-1 ring-violet-300 dark:ring-violet-800' : '', a.is_expired ? 'opacity-60' : '']">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="`bg-${styleFor(a.category).color}-100 dark:bg-${styleFor(a.category).color}-900/30`">
                            <i :class="`bi ${styleFor(a.category).icon} text-${styleFor(a.category).color}-600 dark:text-${styleFor(a.category).color}-400`"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-semibold text-slate-900 dark:text-white">{{ a.title }}</h2>
                                <span v-if="a.is_pinned" class="text-[10px] px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300">
                                    <i class="bi bi-pin-angle-fill"></i> Pinned
                                </span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    {{ styleFor(a.category).label }}
                                </span>
                                <span v-if="a.is_expired" class="text-[10px] text-slate-400">expired</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ a.author.name }} · {{ a.created_at }}
                                <template v-if="a.expires_at && !a.is_expired"> · until {{ a.expires_at }}</template>
                            </p>
                            <div class="mt-3 text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ a.body }}</div>
                        </div>
                        <div v-if="can_manage" class="flex flex-shrink-0">
                            <button @click="openEdit(a)" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i></button>
                            <button @click="deleteAnnouncement(a)" class="btn-ghost btn-sm text-rose-500"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="announcements.last_page > 1" class="mt-4 flex gap-1">
                <template v-for="link in announcements.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-xs"
                          :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-100'" v-html="link.label" />
                    <span v-else class="px-3 py-1 text-xs text-slate-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Create / Edit modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape="showModal = false">
                <div class="absolute inset-0 backdrop-blur-sm bg-slate-950/60" @click="showModal = false"></div>
                <div class="relative card w-full max-w-lg overflow-hidden flex flex-col max-h-[90dvh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            {{ modalMethod === 'PUT' ? 'Edit announcement' : 'New announcement' }}
                        </h3>
                        <button @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i class="bi bi-x-lg text-base"></i>
                        </button>
                    </div>
                    <form @submit.prevent="submitModal" class="px-6 py-5 space-y-4 overflow-y-auto">
                        <div>
                            <label class="form-label">Title <span class="text-rose-500">*</span></label>
                            <input v-model="modalForm.title" type="text" class="form-input" required maxlength="160"
                                   placeholder="e.g. Office closed on Monday">
                            <p v-if="modalForm.errors.title" class="form-error">{{ modalForm.errors.title }}</p>
                        </div>
                        <div>
                            <label class="form-label">Message <span class="text-rose-500">*</span></label>
                            <textarea v-model="modalForm.body" rows="6" class="form-input resize-y" required maxlength="5000"
                                      placeholder="Write the full announcement here…"></textarea>
                            <p v-if="modalForm.errors.body" class="form-error">{{ modalForm.errors.body }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Category</label>
                                <select v-model="modalForm.category" class="form-input">
                                    <option value="general">General</option>
                                    <option value="policy">Policy</option>
                                    <option value="event">Event</option>
                                    <option value="update">Update</option>
                                    <option value="alert">Alert</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Show until <span class="text-slate-400 font-normal">(optional)</span></label>
                                <input v-model="modalForm.expires_on" type="date" class="form-input">
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 text-sm text-slate-700 dark:text-slate-300">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="modalForm.is_pinned" class="rounded text-violet-600">
                                Pin to top
                            </label>
                            <label v-if="modalMethod === 'POST'" class="flex items-center gap-2">
                                <input type="checkbox" v-model="modalForm.notify" class="rounded text-violet-600">
                                Notify everyone
                            </label>
                        </div>
                    </form>
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex justify-end gap-2 flex-shrink-0">
                        <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
                        <button @click="submitModal" class="btn-primary" :disabled="modalForm.processing">
                            <i class="bi bi-send"></i> {{ modalMethod === 'PUT' ? 'Save changes' : 'Publish' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
