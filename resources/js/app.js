import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;
Alpine.plugin(persist);
Alpine.plugin(collapse);

// ============================================================
// Global Alpine Components / Functions
// ============================================================

window.toastManager = function() {
    return {
        toasts: [],
        add({ message, type = 'success' }) {
            const id = Date.now();
            this.toasts.push({ id, message, type, visible: true });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3500);
        }
    };
};

window.toast = function(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

// Dispatch on window so all header dropdowns can close themselves.
window._hdClose = () => window.dispatchEvent(new CustomEvent('hd:close'));
window._hdToggle = function(current) {
    const opening = !current;
    window._hdClose();
    return opening;   // caller uses this with $nextTick or setTimeout
};

window.notificationsDropdown = function() {
    return {
        open: false,
        count: 0,
        items: [],
        init() {
            this.refresh();
            setInterval(() => this.refresh(), 60000);
        },
        toggle() {
            const opening = !this.open;
            window._hdClose();
            if (opening) {
                this.$nextTick(() => { this.open = true; });
                this.refresh();
            }
        },
        refresh() {
            fetch('/notifications', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.count = data.unread_count ?? 0;
                    this.items = data.items ?? [];
                }).catch(() => {});
        },
        async markRead(n) {
            if (!n.read_at) {
                const tok = document.querySelector('meta[name=csrf-token]').content;
                fetch(`/notifications/${n.id}/read`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': tok, 'Accept': 'application/json' },
                }).then(() => {
                    n.read_at = new Date().toISOString();
                    this.count = Math.max(0, this.count - 1);
                }).catch(() => {});
            }
            if (n.data?.url) window.location.href = n.data.url;
        },
        async markAllRead() {
            const tok = document.querySelector('meta[name=csrf-token]').content;
            fetch('/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': tok, 'Accept': 'application/json' },
            }).then(() => {
                this.items.forEach(n => n.read_at = new Date().toISOString());
                this.count = 0;
            }).catch(() => {});
        }
    };
};

window.multiTimerWidget = function() {
    return {
        open: false,
        entries: [],
        count: 0,
        runningCount: 0,
        pausedCount: 0,
        hasActive: false,
        _tick: null,
        _poll: null,

        _sync() {
            this.count        = this.entries.length;
            this.runningCount = this.entries.filter(e => e.status === 'running').length;
            this.pausedCount  = this.entries.filter(e => e.status === 'paused').length;
            this.hasActive    = this.entries.length > 0;
        },

        init() {
            this.refresh();
            this._poll = setInterval(() => this.refresh(), 30000);
            this._tick = setInterval(() => this.tick(), 1000);
            document.addEventListener('click', e => {
                if (!this.$el.contains(e.target)) this.open = false;
            });
        },

        async refresh() {
            try {
                const r = await fetch('/time-entries/active', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.entries = (d.entries || []).map(e => ({
                    ...e,
                    elapsed_formatted: this.fmt(e.elapsed_seconds),
                    loading: false,
                }));
                this._sync();
            } catch (_) {}
        },

        tick() {
            this.entries = this.entries.map(e => {
                if (e.status !== 'running') return e;
                const now   = Math.floor(Date.now() / 1000);
                const start = Math.floor(new Date(e.started_at).getTime() / 1000);
                const elapsed = Math.max(0, now - start - (e.paused_seconds || 0));
                return { ...e, elapsed_seconds: elapsed, elapsed_formatted: this.fmt(elapsed) };
            });
            this._sync();
        },

        fmt(s) {
            s = Math.max(0, Math.floor(s || 0));
            return [Math.floor(s / 3600), Math.floor((s % 3600) / 60), s % 60]
                .map(n => String(n).padStart(2, '0')).join(':');
        },

        csrf() {
            return document.querySelector('meta[name=csrf-token]')?.content ?? '';
        },

        async pauseEntry(entry) {
            if (entry.loading) return;
            entry.loading = true;
            try {
                const r = await fetch(`/time-entries/${entry.id}/pause`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
                });
                const d = await r.json();
                const idx = this.entries.findIndex(e => e.id === entry.id);
                if (idx >= 0) { this.entries[idx] = { ...this.entries[idx], ...d.entry, elapsed_formatted: this.fmt(d.entry.elapsed_seconds), loading: false }; this._sync(); }
                window.toast?.('Timer paused', 'info');
            } catch (_) { entry.loading = false; }
        },

        async resumeEntry(entry) {
            if (entry.loading) return;
            entry.loading = true;
            try {
                const r = await fetch(`/time-entries/${entry.id}/resume`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
                });
                const d = await r.json();
                const idx = this.entries.findIndex(e => e.id === entry.id);
                if (idx >= 0) { this.entries[idx] = { ...this.entries[idx], ...d.entry, elapsed_formatted: this.fmt(d.entry.elapsed_seconds), loading: false }; this._sync(); }
                window.toast?.('Timer resumed', 'success');
            } catch (_) { entry.loading = false; }
        },

        async stopEntry(entry) {
            if (entry.loading) return;
            entry.loading = true;
            try {
                await fetch(`/time-entries/${entry.id}/stop`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                });
                this.entries = this.entries.filter(e => e.id !== entry.id);
                this._sync();
                window.toast?.('Timer stopped', 'success');
            } catch (_) { entry.loading = false; }
        },

        async stopAll() {
            for (const e of [...this.entries]) {
                await this.stopEntry(e);
            }
        },
    };
};

// Legacy alias kept for backwards compat (old blade templates reference timerWidget)
window.timerWidget = window.multiTimerWidget;

window.globalSearch = function() {
    return {
        q: '',
        open: false,
        loading: false,
        groups: [],
        active: -1,
        _timer: null,
        _seq: 0,
        get flat() {
            return this.groups.flatMap(g => g.items);
        },
        onInput() {
            clearTimeout(this._timer);
            if (this.q.trim().length < 2) { this.groups = []; this.open = false; return; }
            this._timer = setTimeout(() => this.fetch(), 200);
        },
        async fetch() {
            const seq = ++this._seq;
            this.loading = true;
            try {
                const { data } = await window.axios.get('/search', { params: { q: this.q }, headers: { Accept: 'application/json' } });
                if (seq !== this._seq) return;
                this.groups = [
                    { label: 'Tasks', items: data.tasks },
                    { label: 'Projects', items: data.projects },
                    { label: 'People', items: data.people },
                ].filter(g => g.items.length);
                this.active = -1;
                this.open = true;
            } finally {
                if (seq === this._seq) this.loading = false;
            }
        },
        move(step) {
            if (!this.flat.length) return;
            this.active = (this.active + step + this.flat.length) % this.flat.length;
        },
        go() {
            const item = this.flat[this.active];
            window.location.href = item ? item.url : '/search?q=' + encodeURIComponent(this.q);
        },
    };
};

window.presenceWidget = function(initial) {
    return {
        open: false,
        busy: false,
        statuses: initial.statuses,
        availability: initial.availability,
        clockedIn: initial.clockedIn,
        clockInAt: initial.clockInAt ? new Date(initial.clockInAt) : null,
        workedToday: initial.workedMinutes,
        elapsed: '',
        _tick: null,
        init() {
            this.tick();
            this._tick = setInterval(() => this.tick(), 30000);
        },
        get current() {
            return this.statuses[this.availability] || this.statuses.offline;
        },
        tick() {
            let mins = this.workedToday;
            if (this.clockedIn && this.clockInAt) {
                mins += Math.max(0, Math.floor((Date.now() - this.clockInAt) / 60000));
            }
            this.elapsed = `${Math.floor(mins / 60)}h ${String(mins % 60).padStart(2, '0')}m`;
        },
        async send(url, method, body = {}) {
            this.busy = true;
            try {
                const { data } = await window.axios({ url, method, data: body, headers: { Accept: 'application/json' } });
                this.availability = data.availability;
                if (data.clocked_in !== this.clockedIn) {
                    // Closing a session banks its minutes; opening one starts a fresh counter.
                    if (!data.clocked_in && this.clockInAt) {
                        this.workedToday += Math.max(0, Math.floor((Date.now() - this.clockInAt) / 60000));
                    }
                    this.clockedIn = data.clocked_in;
                    this.clockInAt = data.clock_in_at ? new Date(data.clock_in_at) : null;
                }
                this.tick();
                window.toast(data.message, 'success');
            } catch (e) {
                window.toast('Could not update your status', 'error');
            } finally {
                this.busy = false;
            }
        },
        toggleDropdown() {
            const opening = !this.open;
            window._hdClose();
            if (opening) this.$nextTick(() => { this.open = true; });
        },
        setStatus(key) {
            this.open = false;
            this.send('/me/availability', 'patch', { availability: key });
        },
        toggleClock() {
            this.open = false;
            this.send(this.clockedIn ? '/attendance/clock-out' : '/attendance/clock-in', 'post');
        },
    };
};

window.timeAgo = function(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};

// Kanban drag-drop helpers
window.kanbanBoard = function(projectId) {
    return {
        dragging: null,
        dragOver: null,
        dragStart(taskId) {
            this.dragging = taskId;
        },
        dragEnter(columnId) {
            this.dragOver = columnId;
        },
        async drop(columnId, position) {
            if (!this.dragging) return;
            try {
                await fetch(`/tasks/${this.dragging}/move`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ column_id: columnId, position })
                });
                window.toast('Task moved', 'success');
                window.location.reload();
            } catch(e) {
                window.toast('Failed to move task', 'error');
            }
            this.dragging = null;
            this.dragOver = null;
        }
    };
};

// Start Alpine after all components are registered
Alpine.start();
