<template>
  <AppLayout title="Dashboard">
    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          Good {{ greeting }}, {{ firstName }}
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ today }}</p>
      </div>
      <div class="flex items-center gap-3">
        <Link v-if="authUser?.roles?.includes('admin')" :href="route('admin.dashboard')" class="btn-ghost text-sm">
          <i class="bi bi-shield-fill-check text-rose-500"></i>
          <span class="hidden sm:inline">Admin Panel</span>
        </Link>
        <Link :href="route('projects.create')" class="btn-primary">
          <i class="bi bi-plus-lg"></i> <span class="hidden sm:inline">New Project</span>
        </Link>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <p class="section-heading mb-2">Projects</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.total_projects }}</p>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
              <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              {{ stats.active_projects }} active
            </p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
            <i class="bi bi-folder2-open text-violet-600 dark:text-violet-400"></i>
          </div>
        </div>
      </div>
      <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <p class="section-heading mb-2">My Tasks</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.my_tasks }}</p>
            <p class="text-xs text-slate-500 mt-1">open tasks</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
            <i class="bi bi-check2-square text-blue-600 dark:text-blue-400"></i>
          </div>
        </div>
      </div>
      <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <p class="section-heading mb-2">Overdue</p>
            <p class="text-3xl font-bold" :class="stats.overdue_tasks > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">
              {{ stats.overdue_tasks }}
            </p>
            <p class="text-xs text-slate-500 mt-1">past due date</p>
          </div>
          <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform"
               :class="stats.overdue_tasks > 0 ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-slate-100 dark:bg-slate-800'">
            <i :class="stats.overdue_tasks > 0 ? 'bi-exclamation-triangle text-rose-500' : 'bi-exclamation-triangle text-slate-400'" class="bi"></i>
          </div>
        </div>
      </div>
      <div class="card p-5 group hover:shadow-card-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <p class="section-heading mb-2">Done Today</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.completed_today }}</p>
            <p class="text-xs text-slate-500 mt-1">tasks completed</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
            <i class="bi bi-check-circle-fill text-emerald-600 dark:text-emerald-400"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Timers Widget -->
    <div v-if="timerEntries.length" class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 rounded-full" :class="timerRunningCount > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400'"></div>
          <span class="text-sm font-semibold text-slate-800 dark:text-white">Active Timers</span>
          <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">
            {{ timerEntries.length }} active
          </span>
        </div>
        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
          <span v-if="timerRunningCount > 0">
            <i class="bi bi-play-circle-fill text-emerald-500 mr-1"></i>{{ timerRunningCount }} running
          </span>
          <span v-if="timerPausedCount > 0">
            <i class="bi bi-pause-circle-fill text-amber-400 mr-1"></i>{{ timerPausedCount }} paused
          </span>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="entry in timerEntries" :key="entry.id"
             class="rounded-2xl border p-4 flex flex-col gap-3 transition-all"
             :class="entry.status === 'running'
               ? 'bg-gradient-to-br from-violet-50 to-indigo-50 dark:from-violet-900/20 dark:to-indigo-900/20 border-violet-200 dark:border-violet-800 shadow-sm'
               : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700'">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-0.5" :style="'background:' + entry.project_color"></span>
              <div class="min-w-0">
                <a :href="entry.task_url ?? '#'"
                   class="text-sm font-semibold text-slate-800 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 truncate block leading-tight">
                  {{ entry.task_title }}
                </a>
                <p class="text-xs text-slate-400 dark:text-slate-500 truncate mt-0.5">{{ entry.project_name }}</p>
              </div>
            </div>
            <div class="flex-shrink-0 text-right">
              <span class="font-mono text-base font-bold tabular-nums"
                    :class="entry.status === 'running' ? 'text-violet-700 dark:text-violet-300' : 'text-slate-500 dark:text-slate-400'">
                {{ entry.elapsed_formatted }}
              </span>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="entry.status === 'running' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'">
              <i :class="entry.status === 'running' ? 'bi bi-play-fill' : 'bi bi-pause-fill'" class="bi"></i>
              {{ entry.status === 'running' ? 'Running' : 'Paused' }}
            </span>
            <div class="flex items-center gap-1.5">
              <button v-if="entry.status === 'running'" @click="pauseTimer(entry)" :disabled="entry.loading"
                      class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-amber-50 dark:hover:bg-amber-900/30 hover:text-amber-600 dark:hover:text-amber-400 border border-slate-200 dark:border-slate-600 transition-colors disabled:opacity-50" title="Pause">
                <i class="bi bi-pause-fill text-sm"></i>
              </button>
              <button v-if="entry.status === 'paused'" @click="resumeTimer(entry)" :disabled="entry.loading"
                      class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 hover:text-emerald-600 dark:hover:text-emerald-400 border border-slate-200 dark:border-slate-600 transition-colors disabled:opacity-50" title="Resume">
                <i class="bi bi-play-fill text-sm"></i>
              </button>
              <button @click="stopTimer(entry)" :disabled="entry.loading"
                      class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 border border-slate-200 dark:border-slate-600 transition-colors disabled:opacity-50" title="Stop">
                <i class="bi bi-stop-fill text-sm"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Daily report reminder -->
    <div v-if="!hasReport" class="mb-6 rounded-2xl border-2 border-dashed border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/10 p-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-journal-plus text-amber-600 dark:text-amber-400 text-xl"></i>
      </div>
      <div class="flex-1">
        <p class="font-semibold text-sm text-amber-800 dark:text-amber-200">Daily standup not submitted</p>
        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Submit today's progress report to keep your team in sync.</p>
      </div>
      <Link :href="route('daily-reports.create')" class="flex-shrink-0 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition-colors">
        Submit now
      </Link>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <!-- My Projects -->
      <div class="xl:col-span-2">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white">My Projects</h2>
          <Link :href="route('projects.index')" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all →</Link>
        </div>
        <div class="space-y-2">
          <div v-for="project in myProjects" :key="project.id" class="card p-4 hover:shadow-card-md transition-shadow group">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center"
                   :style="'background-color:' + project.color + '20'">
                <div class="w-3 h-3 rounded-full" :style="'background-color:' + project.color"></div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <Link :href="route('projects.show', { project: project.id })"
                        class="font-semibold text-slate-900 dark:text-white hover:text-violet-600 text-sm truncate">
                    {{ project.name }}
                  </Link>
                  <span class="badge text-[10px]"
                        :class="project.status === 'active' ? 'badge-success' : project.status === 'on_hold' ? 'badge-warning' : 'badge-neutral'">
                    {{ project.status.replace('_', ' ') }}
                  </span>
                </div>
                <div class="flex items-center gap-3 mt-2">
                  <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full">
                    <div class="h-1.5 rounded-full transition-all" :style="'background-color:' + project.color + '; width:' + project.progress_pct + '%'"></div>
                  </div>
                  <span class="text-xs text-slate-400 flex-shrink-0">{{ project.done_tasks }}/{{ project.total_tasks }}</span>
                </div>
                <div class="flex items-center gap-4 mt-1.5 text-xs text-slate-400">
                  <span v-if="project.due_date" :class="isOverdue(project.due_date) ? 'text-rose-500' : ''">
                    <i class="bi bi-calendar3 mr-0.5"></i>{{ formatDate(project.due_date) }}
                  </span>
                  <span><i class="bi bi-people mr-0.5"></i>{{ project.members_count }} members</span>
                </div>
              </div>
              <Link :href="route('projects.show', { project: project.id })"
                    class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity btn-icon btn-ghost">
                <i class="bi bi-arrow-right text-sm"></i>
              </Link>
            </div>
          </div>
          <div v-if="!myProjects.length" class="empty-state">
            <i class="bi bi-folder-plus text-3xl text-slate-300 dark:text-slate-600 block mb-3"></i>
            <p class="font-medium text-slate-600 dark:text-slate-400">No projects yet</p>
            <p class="text-sm text-slate-400 mt-1">Create your first project to get started</p>
            <Link :href="route('projects.create')" class="btn-primary mt-4">
              <i class="bi bi-plus-lg"></i> New Project
            </Link>
          </div>
        </div>

        <!-- Upcoming Tasks -->
        <div v-if="upcomingTasks.length" class="mt-6">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Upcoming Deadlines</h2>
          </div>
          <div class="card overflow-hidden">
            <div class="divide-y divide-slate-50 dark:divide-slate-800">
              <div v-for="task in upcomingTasks.slice(0, 5)" :key="task.id"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <div class="w-2 h-2 rounded-full flex-shrink-0"
                     :class="statusColors[task.status] ?? 'bg-slate-300'"></div>
                <div class="flex-1 min-w-0">
                  <a :href="'/tasks/' + task.id"
                     class="text-sm text-slate-700 dark:text-slate-300 hover:text-violet-600 truncate block">{{ task.title }}</a>
                  <p class="text-xs text-slate-400">{{ task.project?.name ?? '' }}</p>
                </div>
                <span class="flex-shrink-0 text-xs font-medium" :class="isOverdue(task.due_date) ? 'text-rose-500' : 'text-slate-500'">
                  {{ formatDate(task.due_date) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right column -->
      <div class="space-y-6">
        <!-- Announcements -->
        <div v-if="announcements.length">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Announcements</h2>
            <Link :href="route('announcements.index')" class="text-xs text-violet-600 hover:text-violet-700 font-medium">View all →</Link>
          </div>
          <div class="card overflow-hidden divide-y divide-slate-50 dark:divide-slate-800">
            <a v-for="a in announcements" :key="a.id"
               :href="route('announcements.index') + '#announcement-' + a.id"
               class="flex gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <i class="bi mt-0.5" :class="a.style?.icon" :style="'color:' + a.style?.color_class"></i>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                  <i v-if="a.is_pinned" class="bi bi-pin-angle-fill text-violet-500"></i>
                  {{ a.title }}
                </p>
                <p class="text-[11px] text-slate-500 line-clamp-2">{{ a.body_excerpt }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ a.author?.name }} · {{ a.created_at_human }}</p>
              </div>
            </a>
          </div>
        </div>

        <!-- Team / Off today -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Team</h2>
            <Link :href="route('leave.calendar')" class="text-xs text-violet-600 hover:text-violet-700 font-medium">Leave calendar →</Link>
          </div>
          <div class="card p-4 space-y-4">
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-2">Off today</p>
              <div v-if="offToday.length">
                <div v-for="leave in offToday" :key="leave.id" class="flex items-center gap-2.5 py-1">
                  <img :src="leave.user?.avatar_url ?? '/images/default-avatar.png'" class="w-6 h-6 rounded-full" alt="">
                  <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">{{ leave.user?.name ?? '—' }}</span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ leave.type?.name }}{{ leave.is_half_day ? ' ½' : '' }}
                  </span>
                </div>
              </div>
              <p v-else class="text-xs text-slate-400">Everyone's in today.</p>
            </div>
            <div v-if="celebrations.length">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-2">Coming up</p>
              <div v-for="c in celebrations.slice(0, 5)" :key="c.user?.id + '_' + c.kind" class="flex items-center gap-2.5 py-1">
                <i class="bi" :class="c.kind === 'birthday' ? 'bi-cake2 text-rose-500' : 'bi-award text-amber-500'"></i>
                <span class="text-xs text-slate-700 dark:text-slate-300 flex-1 truncate">
                  <strong class="font-medium">{{ c.user?.name ?? '—' }}</strong> · {{ c.label }}
                </span>
                <span class="text-[10px] text-slate-400">{{ c.date_label }}</span>
              </div>
            </div>
            <div v-if="nextHoliday" class="flex items-center gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
              <i class="bi bi-calendar-heart text-violet-500"></i>
              <span class="text-xs text-slate-700 dark:text-slate-300 flex-1">
                Next holiday: <strong class="font-medium">{{ nextHoliday.name }}</strong>
              </span>
              <span class="text-[10px] text-slate-400">{{ nextHoliday.date_formatted }}</span>
            </div>
          </div>
        </div>

        <!-- Activity Feed -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent Activity</h2>
          </div>
          <div class="card overflow-hidden">
            <div class="divide-y divide-slate-50 dark:divide-slate-800 max-h-[420px] overflow-y-auto">
              <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start gap-3 px-4 py-3">
                <img :src="activity.causer?.avatar_url ?? ''" alt="" class="w-7 h-7 rounded-full flex-shrink-0 mt-0.5">
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-slate-700 dark:text-slate-300 leading-snug">
                    <span class="font-medium text-slate-900 dark:text-white">{{ activity.causer?.name ?? 'System' }}</span>
                    {{ activity.description }}
                  </p>
                  <p class="text-[10px] text-slate-400 mt-0.5">{{ activity.created_at_human }}</p>
                </div>
              </div>
              <div v-if="!recentActivity.length" class="py-8 text-center text-slate-400 text-sm">
                <i class="bi bi-activity text-2xl block mb-2 text-slate-300"></i>
                No recent activity
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div>
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Quick Actions</h2>
          <div class="grid grid-cols-2 gap-2">
            <Link :href="route('projects.create')" class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
              <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                <i class="bi bi-folder-plus text-violet-600 dark:text-violet-400"></i>
              </div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">New Project</span>
            </Link>
            <Link :href="route('daily-reports.create')" class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
              <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                <i class="bi bi-journal-plus text-amber-600 dark:text-amber-400"></i>
              </div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Daily Report</span>
            </Link>
            <Link :href="route('bugs.index')" class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
              <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                <i class="bi bi-bug-fill text-rose-600 dark:text-rose-400"></i>
              </div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Bug Tracker</span>
            </Link>
            <Link :href="route('time-entries.index')" class="card p-3 flex flex-col items-center gap-2 text-center hover:shadow-card-md transition-all group hover:-translate-y-0.5">
              <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                <i class="bi bi-stopwatch text-emerald-600 dark:text-emerald-400"></i>
              </div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Time Tracker</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  stats:          { type: Object, default: () => ({}) },
  upcomingTasks:  { type: Array,  default: () => [] },
  recentActivity: { type: Array,  default: () => [] },
  activeTimers:   { type: Array,  default: () => [] },
  myProjects:     { type: Array,  default: () => [] },
  announcements:  { type: Array,  default: () => [] },
  offToday:       { type: Array,  default: () => [] },
  nextHoliday:    { type: Object, default: null },
  celebrations:   { type: Array,  default: () => [] },
  hasReport:      { type: Boolean,default: false },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

// ── Date helpers ────────────────────────────────────────────────
const now = new Date();
const hour = now.getHours();
const greeting = hour < 12 ? 'morning' : hour < 17 ? 'afternoon' : 'evening';
const firstName = computed(() => authUser.value?.name?.split(' ')[0] ?? '');
const today = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function isOverdue(d) {
  return d && new Date(d) < new Date();
}

const statusColors = {
  todo:        'bg-slate-300',
  in_progress: 'bg-blue-500',
  qa_testing:  'bg-violet-500',
  in_review:   'bg-amber-500',
};

// ── Active timers ───────────────────────────────────────────────
const timerEntries     = ref(props.activeTimers.map(e => ({ ...e, loading: false })));
const timerRunningCount = computed(() => timerEntries.value.filter(e => e.status === 'running').length);
const timerPausedCount  = computed(() => timerEntries.value.filter(e => e.status === 'paused').length);

function fmtTimer(s) {
  s = Math.max(0, Math.floor(s || 0));
  return [Math.floor(s / 3600), Math.floor((s % 3600) / 60), s % 60]
    .map(n => String(n).padStart(2, '0')).join(':');
}

let timerTickInterval = null;

function tickTimers() {
  timerEntries.value = timerEntries.value.map(e => {
    if (e.status !== 'running') return e;
    const start = Math.floor(new Date(e.started_at).getTime() / 1000);
    const elapsed = Math.max(0, Math.floor(Date.now() / 1000) - start - (e.paused_seconds || 0));
    return { ...e, elapsed_seconds: elapsed, elapsed_formatted: fmtTimer(elapsed) };
  });
}

async function pauseTimer(entry) {
  if (entry.loading) return;
  entry.loading = true;
  try {
    const r = await window.axios.patch(entry.pause_url);
    const idx = timerEntries.value.findIndex(e => e.id === entry.id);
    if (idx >= 0) timerEntries.value[idx] = { ...timerEntries.value[idx], ...r.data.entry, elapsed_formatted: fmtTimer(r.data.entry.elapsed_seconds), loading: false };
  } catch (_) { entry.loading = false; }
}

async function resumeTimer(entry) {
  if (entry.loading) return;
  entry.loading = true;
  try {
    const r = await window.axios.patch(entry.resume_url);
    const idx = timerEntries.value.findIndex(e => e.id === entry.id);
    if (idx >= 0) timerEntries.value[idx] = { ...timerEntries.value[idx], ...r.data.entry, elapsed_formatted: fmtTimer(r.data.entry.elapsed_seconds), loading: false };
  } catch (_) { entry.loading = false; }
}

async function stopTimer(entry) {
  if (entry.loading) return;
  entry.loading = true;
  try {
    await window.axios.post(entry.stop_url);
    timerEntries.value = timerEntries.value.filter(e => e.id !== entry.id);
  } catch (_) { entry.loading = false; }
}

onMounted(() => {
  timerTickInterval = setInterval(tickTimers, 1000);
});
onUnmounted(() => clearInterval(timerTickInterval));
</script>
