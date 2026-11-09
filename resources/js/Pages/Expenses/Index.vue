<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    expenses: Object,
    pending_review: Number,
    summary: Object,
    categories: Array,
    store_url: String,
    approvals_url: String,
})

const submitOpen = ref(false)

// Submit form
const form = ref({
    title: '',
    amount: '',
    currency: 'INR',
    category: '',
    expense_date: new Date().toISOString().slice(0, 10),
    description: '',
})
const files = ref([])
const fileErrors = ref([])
const isDragging = ref(false)
const submitting = ref(false)
let fileInputEl = null

function categoryIcon(cat) {
    return { Travel: 'bi-airplane', Meals: 'bi-cup-hot', 'Office Supplies': 'bi-pencil-square', Software: 'bi-laptop', Training: 'bi-mortarboard', Accommodation: 'bi-building' }[cat] ?? 'bi-receipt'
}

function addFiles(fileList) {
    fileErrors.value = []
    const allowed = ['jpg', 'jpeg', 'png', 'pdf', 'webp']
    const maxBytes = 5 * 1024 * 1024
    for (const f of fileList) {
        const ext = f.name.split('.').pop().toLowerCase()
        if (!allowed.includes(ext)) { fileErrors.value.push(f.name + ': unsupported type (JPG, PNG, PDF, WEBP only)'); continue }
        if (f.size > maxBytes) { fileErrors.value.push(f.name + ': exceeds 5 MB limit'); continue }
        if (files.value.length >= 10) { fileErrors.value.push('Max 10 receipts per expense'); break }
        files.value.push({ file: f, name: f.name, size: f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB', fileType: ['jpg', 'jpeg', 'png', 'webp'].includes(ext) ? 'image' : 'pdf' })
    }
}

function removeFile(idx) { files.value.splice(idx, 1) }

function onDropZoneDrop(e) {
    isDragging.value = false
    addFiles(e.dataTransfer?.files ?? [])
}

function openFilePicker() { fileInputEl?.click() }
function onFileInput(e) { addFiles(e.target.files); e.target.value = '' }

async function submitExpense(e) {
    e.preventDefault()
    submitting.value = true
    const fd = new FormData()
    fd.append('title', form.value.title)
    fd.append('amount', form.value.amount)
    fd.append('currency', form.value.currency)
    fd.append('category', form.value.category)
    fd.append('expense_date', form.value.expense_date)
    if (form.value.description) fd.append('description', form.value.description)
    files.value.forEach(f => fd.append('receipts[]', f.file))
    fd.append('_token', document.querySelector('meta[name=csrf-token]')?.content)
    try {
        const res = await fetch(props.store_url, { method: 'POST', headers: { Accept: 'application/json' }, body: fd })
        if (res.ok || res.redirected) {
            router.reload({ only: ['expenses', 'summary', 'pending_review'] })
            submitOpen.value = false
            form.value = { title: '', amount: '', currency: 'INR', category: '', expense_date: new Date().toISOString().slice(0, 10), description: '' }
            files.value = []
        }
    } finally {
        submitting.value = false
    }
}

function cancelExpense(url) {
    if (!confirm('Cancel this expense?')) return
    router.patch(url, {}, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="My Expenses">
        <div>
            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <i class="bi bi-receipt text-emerald-600 dark:text-emerald-400"></i>
                        </span>
                        My Expenses
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Submit and track your expense reimbursements.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a v-if="pending_review > 0" :href="approvals_url"
                       class="btn-secondary">
                        <i class="bi bi-inbox"></i> Approvals
                        <span class="ml-1 px-1.5 rounded-full bg-amber-500 text-white text-[10px]">{{ pending_review }}</span>
                    </a>
                    <button @click="submitOpen = true" class="btn-primary">
                        <i class="bi bi-plus-lg"></i> New Expense
                    </button>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div class="card p-5">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Pending Amount</p>
                    <p class="mt-1.5 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ Number(summary.total_pending).toFixed(2) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ summary.count_pending }} {{ summary.count_pending === 1 ? 'expense' : 'expenses' }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Approved Amount</p>
                    <p class="mt-1.5 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ Number(summary.total_approved).toFixed(2) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ summary.count_approved }} {{ summary.count_approved === 1 ? 'expense' : 'expenses' }}</p>
                </div>
            </div>

            <!-- Expenses list -->
            <div class="card overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Submitted Expenses</h2>
                </div>

                <template v-if="expenses.data?.length">
                    <div v-for="e in expenses.data" :key="e.id"
                         class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-slate-50 dark:border-slate-800/60 last:border-b-0">
                        <!-- Category icon -->
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                            <i class="bi text-slate-500 dark:text-slate-400 text-sm" :class="categoryIcon(e.category)"></i>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ e.title }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">{{ e.category }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ e.expense_date }}</p>
                            <p v-if="e.description" class="text-xs text-slate-400 mt-0.5 truncate">"{{ e.description }}"</p>
                            <p v-if="e.reviewer" class="text-xs text-slate-400 mt-0.5">
                                {{ e.status?.charAt(0).toUpperCase() + e.status?.slice(1) }} by {{ e.reviewer.name }} {{ e.reviewed_at }}
                                <template v-if="e.review_note"> — "{{ e.review_note }}"</template>
                            </p>
                        </div>

                        <!-- Amount + receipt -->
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <a v-for="r in e.receipt_urls" :key="r.url" :href="r.url" target="_blank"
                               class="text-slate-400 hover:text-violet-600 transition-colors" :title="r.name">
                                <i class="bi bi-paperclip text-sm"></i>
                            </a>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ e.currency }} {{ Number(e.amount).toFixed(2) }}</p>
                        </div>

                        <!-- Status badge -->
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium flex-shrink-0 self-start sm:self-center" :class="e.status_class">
                            {{ e.status_label }}
                        </span>

                        <!-- Cancel -->
                        <button v-if="e.cancel_url" @click="cancelExpense(e.cancel_url)"
                                class="btn-ghost btn-sm text-rose-500 flex-shrink-0">Cancel</button>
                    </div>
                </template>

                <div v-else class="py-16 text-center">
                    <i class="bi bi-receipt text-4xl text-slate-200 dark:text-slate-700"></i>
                    <p class="mt-3 text-sm text-slate-400">No expenses submitted yet.</p>
                    <button @click="submitOpen = true" class="mt-4 btn-primary btn-sm">Submit your first expense</button>
                </div>

                <!-- Pagination -->
                <div v-if="expenses.last_page > 1" class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2 flex-wrap">
                    <template v-for="link in expenses.links" :key="link.label">
                        <span v-if="!link.url" class="px-2 py-1 text-sm text-slate-300 dark:text-slate-600" v-html="link.label"></span>
                        <button v-else @click="router.get(link.url)"
                                class="px-2 py-1 text-sm rounded transition-colors"
                                :class="link.active ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                v-html="link.label"></button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Submit Expense Modal -->
        <Teleport to="body">
            <div v-if="submitOpen"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @click.self="submitOpen = false"
                 @keydown.escape.window="submitOpen = false">
                <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="submitOpen = false"></div>
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Submit Expense</h3>
                        <button @click="submitOpen = false" class="btn-ghost btn-sm p-1.5">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <form @submit="submitExpense" enctype="multipart/form-data" class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                        <!-- Title -->
                        <div>
                            <label class="form-label">Title <span class="text-rose-500">*</span></label>
                            <input type="text" v-model="form.title" required class="form-input"
                                   placeholder="e.g. Team lunch, Flight to Bangalore">
                        </div>

                        <!-- Amount + Category -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Amount <span class="text-rose-500">*</span></label>
                                <input type="number" v-model="form.amount" step="0.01" min="0.01" required class="form-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="form-label">Category <span class="text-rose-500">*</span></label>
                                <select v-model="form.category" required class="form-input">
                                    <option value="">Select…</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="form-label">Expense Date <span class="text-rose-500">*</span></label>
                            <input type="date" v-model="form.expense_date" required class="form-input"
                                   :max="new Date().toISOString().slice(0,10)">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description" rows="2" class="form-input resize-none"
                                      placeholder="Purpose or additional details…"></textarea>
                        </div>

                        <!-- Receipts -->
                        <div>
                            <label class="form-label">
                                Receipts
                                <span class="text-xs font-normal text-slate-400">(optional — up to 10 files, JPG/PNG/PDF/WEBP, max 5 MB each)</span>
                            </label>

                            <input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" multiple
                                   class="sr-only" :ref="el => fileInputEl = el"
                                   @change="onFileInput">

                            <!-- Drop zone -->
                            <div @click="openFilePicker"
                                 @dragover.prevent="isDragging = true"
                                 @dragleave.prevent="isDragging = false"
                                 @drop.prevent="onDropZoneDrop"
                                 :class="isDragging ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                                 class="flex flex-col items-center justify-center gap-2 py-5 px-4 border-2 border-dashed rounded-xl cursor-pointer transition-colors">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                                     :class="isDragging ? 'bg-violet-100 dark:bg-violet-900/40' : 'bg-slate-100 dark:bg-slate-800'">
                                    <i class="bi bi-cloud-upload text-lg" :class="isDragging ? 'text-violet-600 dark:text-violet-400' : 'text-slate-400'"></i>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        <span class="text-violet-600 dark:text-violet-400">Click to upload</span> or drag & drop
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5">JPG, PNG, PDF, WEBP · max 5 MB each · up to 10 files</p>
                                </div>
                            </div>

                            <!-- File chips -->
                            <div v-if="files.length" class="mt-2 space-y-1.5">
                                <div v-for="(f, i) in files" :key="i"
                                     class="flex items-center gap-3 px-3 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                         :class="f.fileType === 'pdf' ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-violet-100 dark:bg-violet-900/30'">
                                        <i class="bi text-sm" :class="f.fileType === 'pdf' ? 'bi-file-earmark-pdf text-rose-600 dark:text-rose-400' : 'bi-file-earmark-image text-violet-600 dark:text-violet-400'"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-slate-800 dark:text-white truncate">{{ f.name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ f.size }}</p>
                                    </div>
                                    <button type="button" @click.stop="removeFile(i)"
                                            class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors flex-shrink-0">
                                        <i class="bi bi-x-lg" style="font-size:10px"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- File errors -->
                            <div v-if="fileErrors.length" class="mt-1.5 space-y-0.5">
                                <p v-for="(err, i) in fileErrors" :key="i" class="text-xs text-rose-500">{{ err }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="submitOpen = false" class="btn-ghost">Cancel</button>
                            <button type="submit" :disabled="submitting" class="btn-primary">
                                <i class="bi" :class="submitting ? 'bi-arrow-repeat animate-spin' : 'bi-send'"></i>
                                {{ submitting ? 'Submitting…' : 'Submit for Approval' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
