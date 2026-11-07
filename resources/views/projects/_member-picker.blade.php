@php
    $pickerUsersJson = Js::from($pickerUsers->map(fn($u) => [
        'id'        => $u->id,
        'name'      => $u->name,
        'job_title' => $u->job_title ?? '',
        'avatar'    => $u->avatar ?? null,
    ])->values());
    $pickerInitialJson = Js::from($pickerInitial ?? []);
    $pickerOwnerIdJson = Js::from($pickerOwnerId);
@endphp

<div class="sm:col-span-2 border-t border-slate-100 dark:border-slate-700 pt-5"
     x-data="memberPicker({{ $pickerUsersJson }}, {{ $pickerInitialJson }}, {{ $pickerOwnerIdJson }})">

    <div class="flex items-center justify-between mb-3">
        <label class="form-label mb-0">Team Members</label>
        <span class="text-xs text-slate-400" x-text="Object.keys(selected).length + ' selected'"></span>
    </div>

    {{-- Search --}}
    <div class="relative mb-3">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" x-model="search" placeholder="Search members…"
               class="form-input pl-8 py-2 text-sm">
    </div>

    {{-- User grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1 mb-4">
        <template x-for="user in filteredUsers" :key="user.id">
            <button type="button"
                    @click="toggle(user)"
                    :disabled="user.id === ownerId"
                    :class="isSelected(user.id)
                        ? 'ring-2 ring-violet-500 bg-violet-50 dark:bg-violet-900/20 border-violet-300 dark:border-violet-700'
                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                    class="flex items-center gap-2.5 p-2.5 rounded-lg border text-left transition-all disabled:cursor-not-allowed">
                <div class="relative flex-shrink-0">
                    <template x-if="user.avatar">
                        <img :src="user.avatar" :alt="user.name" class="w-8 h-8 rounded-full object-cover">
                    </template>
                    <template x-if="!user.avatar">
                        <div class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-xs font-semibold text-violet-600 dark:text-violet-400"
                             x-text="user.name.charAt(0).toUpperCase()"></div>
                    </template>
                    <span x-show="isSelected(user.id)"
                          class="absolute -top-1 -right-1 w-4 h-4 bg-violet-500 rounded-full flex items-center justify-center">
                        <i class="bi bi-check text-white" style="font-size:9px"></i>
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate" x-text="user.name"></p>
                    <p class="text-xs text-slate-400 truncate" x-text="user.job_title || 'Team Member'"></p>
                </div>
                <span x-show="user.id === ownerId"
                      class="text-xs text-violet-500 font-medium flex-shrink-0">Owner</span>
            </button>
        </template>
        <p x-show="filteredUsers.length === 0" class="text-sm text-slate-400 py-4 text-center sm:col-span-2">No members found</p>
    </div>

    {{-- Selected members with roles --}}
    <template x-if="selectedList.length > 0">
        <div>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Roles</p>
            <div class="space-y-2">
                <template x-for="member in selectedList" :key="member.id">
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                        <template x-if="member.avatar">
                            <img :src="member.avatar" :alt="member.name" class="w-7 h-7 rounded-full object-cover flex-shrink-0">
                        </template>
                        <template x-if="!member.avatar">
                            <div class="w-7 h-7 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-xs font-semibold text-violet-600 dark:text-violet-400 flex-shrink-0"
                                 x-text="member.name.charAt(0).toUpperCase()"></div>
                        </template>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 flex-1 truncate" x-text="member.name"></span>
                        <select :name="'member_roles[' + member.id + ']'"
                                x-model="selected[member.id].role"
                                :disabled="member.id === ownerId"
                                class="form-input py-1 px-2 text-xs w-28 flex-shrink-0 disabled:opacity-60">
                            <option value="developer">Developer</option>
                            <option value="manager">Manager</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <button type="button"
                                x-show="member.id !== ownerId"
                                @click="remove(member.id)"
                                class="text-slate-400 hover:text-rose-500 transition-colors flex-shrink-0">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Hidden inputs for form submission --}}
    <template x-for="member in selectedList" :key="'input-'+member.id">
        <input type="hidden" name="member_ids[]" :value="member.id">
    </template>
</div>

<script>
function memberPicker(allUsers, initial, ownerId) {
    return {
        users: allUsers,
        selected: {},
        ownerId: ownerId,
        search: '',

        init() {
            // Pre-select initial members (including owner)
            if (initial && Array.isArray(initial)) {
                initial.forEach(m => {
                    this.selected[m.id] = { ...m };
                });
            }
            // Always ensure owner is selected as manager
            const owner = this.users.find(u => u.id === ownerId);
            if (owner && !this.selected[ownerId]) {
                this.selected[ownerId] = { ...owner, role: 'manager' };
            }
            if (this.selected[ownerId]) {
                this.selected[ownerId].role = 'manager';
            }
        },

        get filteredUsers() {
            const q = this.search.toLowerCase().trim();
            if (!q) return this.users;
            return this.users.filter(u =>
                u.name.toLowerCase().includes(q) ||
                (u.job_title || '').toLowerCase().includes(q)
            );
        },

        isSelected(id) {
            return id in this.selected;
        },

        toggle(user) {
            if (user.id === this.ownerId) return;
            if (this.isSelected(user.id)) {
                this.remove(user.id);
            } else {
                this.selected[user.id] = { ...user, role: 'developer' };
            }
        },

        remove(id) {
            if (id === this.ownerId) return;
            delete this.selected[id];
        },

        get selectedList() {
            return Object.values(this.selected);
        },
    };
}
</script>
